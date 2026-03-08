<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use Exception;
use stdClass;
use WP;
use WP_Error;
use WP_Http;
use WP_HTTP_Response;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

use function Asset_Loader\enqueue_asset;

const GUEST_ROLE = 'guest-author';
const POSTS_PARAM = 'authorship';
const REST_CURIE_TEMPLATE = 'https://authorship.hmn.md/{rel}';
const REST_LINK_ID = 'wp:authorship';
const REST_PARAM = 'authorship';
const REST_REL_LINK_ID = 'https://authorship.hmn.md/action-assign-authorship';
const SCRIPT_HANDLE = 'authorship-js';
const STYLE_HANDLE = 'authorship-css';
const TAXONOMY = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap() : void {
	$insert_post_handler = new InsertPostHandler();

	// Actions.
	add_action( 'init', __NAMESPACE__ . '\\init_taxonomy', 99 );
	add_action( 'init', __NAMESPACE__ . '\\register_roles_and_caps', 1 );
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_api_fields' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_assets' );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\action_pre_get_posts', 9999 );
	add_action( 'wp', __NAMESPACE__ . '\\action_wp' );
	add_action( 'wp_insert_post', [ $insert_post_handler, 'action_wp_insert_post' ], 10, 3 );
	add_action( 'deleted_user', __NAMESPACE__ . '\\action_deleted_user', 10, 3 );

	// Filters.
	add_filter( 'wp_insert_post_data', [ $insert_post_handler, 'filter_wp_insert_post_data' ], 10, 3 );
	add_filter( 'rest_request_after_callbacks', __NAMESPACE__ . '\\filter_rest_request_after_callbacks', 10, 3 );
	add_filter( 'map_meta_cap', __NAMESPACE__ . '\\filter_map_meta_cap_for_editing', 10, 4 );
	add_filter( 'user_has_cap', __NAMESPACE__ . '\\filter_user_has_cap', 10, 4 );
	add_filter( 'rest_response_link_curies', __NAMESPACE__ . '\\filter_rest_response_link_curies' );
	add_filter( 'the_author', __NAMESPACE__ . '\\filter_the_author_for_rss' );
	add_filter( 'comment_moderation_recipients', __NAMESPACE__ . '\\filter_comment_moderation_recipients', 10, 2 );
	add_filter( 'comment_notification_recipients', __NAMESPACE__ . '\\filter_comment_notification_recipients', 10, 2 );
	add_filter( 'quick_edit_dropdown_authors_args', __NAMESPACE__ . '\\hide_quickedit_authors' );
}

/**
 * Return list of supported post types, defaulting to those supporting 'author'.
 *
 * @return string[] List of post types to support.
 */
function get_supported_post_types() : array {
	$post_types = get_post_types_by_support( 'author' );

	/**
	 * Filters the list of supported post types
	 *
	 * @return array $post_types List of post types that support authorship
	 */
	return apply_filters( 'authorship_supported_post_types', $post_types );
}

/**
 * Check if a post type is supported by Authorship.
 *
 * @param string $post_type Post type to check.
 * @return boolean
 */
function is_post_type_supported( string $post_type ) : bool {
	return in_array( $post_type, get_supported_post_types(), true );
}

/**
 * Filters the display name of the current post's author for RSS feeds.
 *
 * @param string|null $display_name The author's display name.
 * @return string|null The author's display name.
 */
function filter_the_author_for_rss( ?string $display_name ) : ?string {
	if ( ! is_feed( 'rss2' ) ) {
		return $display_name;
	}

	$post = get_post();

	if ( ! $post ) {
		return $display_name;
	}

	return get_author_names( $post );
}

/**
 * Filters the primitive capabilities required of the given user to perform the action given in `$cap`.
 *
 * @param string[] $caps    Array of the user's capabilities.
 * @param string   $cap     Capability being checked.
 * @param int      $user_id The user ID.
 * @param mixed[]  $args    The context for the cap, typically with the object ID as the first element.
 * @return string[] Array of the user's capabilities.
 */
function filter_map_meta_cap_for_editing( array $caps, string $cap, int $user_id, array $args ) : array {
	$concerns = [
		'delete_post',
		'delete_page',
		'edit_post',
		'edit_page',
		'read_post',
		'read_page',
		// 'publish_post',
	];

	if ( ! in_array( $cap, $concerns, true ) ) {
		return $caps;
	}

	if ( empty( $user_id ) || ! isset( $args[0] ) || ! is_numeric( $args[0] ) ) {
		return $caps;
	}
	$post_id = (int) $args[0];

	if ( $post_id <= 0 ) {
		return $caps;
	}

	$user = get_userdata( $user_id );
	$post = get_post( $post_id );

	if ( empty( $user ) || empty( $post ) ) {
		return $caps;
	}

	$post_type  = get_post_type_object( $post->post_type );
	$status_obj = get_post_status_object( $post->post_status );

	if ( empty( $post_type ) || empty( $status_obj ) ) {
		return $caps;
	}

	if ( ! user_is_author( $user, $post ) ) {
		return $caps;
	}

	/** @var stdClass */
	$post_type_cap = $post_type->cap;

	// Remove the following from `$caps`.
	$remove = [
		'delete' => [
			$post_type_cap->delete_others_posts,
			$post_type_cap->delete_published_posts,
			$post_type_cap->delete_private_posts,
		],
		'edit' => [
			$post_type_cap->edit_others_posts,
			$post_type_cap->edit_published_posts,
			$post_type_cap->edit_private_posts,
		],
		'read' => [
			$post_type_cap->read_private_posts,
		],
	];

	switch ( $cap ) {
		case 'delete_post':
		case 'delete_page':
			$caps = array_diff( $caps, $remove['delete'] );

			// If the post is published or scheduled...
			if ( in_array( $post->post_status, [ 'publish', 'future' ], true ) ) {
				$caps[] = $post_type_cap->delete_published_posts;
			} elseif ( 'trash' === $post->post_status ) {
				$status = get_post_meta( $post->ID, '_wp_trash_meta_status', true );
				if ( in_array( $status, [ 'publish', 'future' ], true ) ) {
					$caps[] = $post_type_cap->delete_published_posts;
				} else {
					$caps[] = $post_type_cap->delete_posts;
				}
			} else {
				// If the post is draft...
				$caps[] = $post_type_cap->delete_posts;
			}
			break;
		case 'edit_post':
		case 'edit_page':
			$caps = array_diff( $caps, $remove['edit'] );

			// If the post is published or scheduled...
			if ( in_array( $post->post_status, [ 'publish', 'future' ], true ) ) {
				$caps[] = $post_type_cap->edit_published_posts;
			} elseif ( 'trash' === $post->post_status ) {
				$status = get_post_meta( $post->ID, '_wp_trash_meta_status', true );
				if ( in_array( $status, [ 'publish', 'future' ], true ) ) {
					$caps[] = $post_type_cap->edit_published_posts;
				} else {
					$caps[] = $post_type_cap->edit_posts;
				}
			} else {
				// If the post is draft...
				$caps[] = $post_type_cap->edit_posts;
			}
			break;
		case 'read_post':
		case 'read_page':
			$caps = array_diff( $caps, $remove['read'] );

			$caps[] = $post_type_cap->read;
			break;
	}//end switch

	return $caps;
}

/**
 * Filters a user's capabilities so they can be altered at runtime.
 *
 * @param bool[]   $user_caps     Array of key/value pairs where keys represent a capability name and boolean values
 *                                represent whether the user has that capability.
 * @param string[] $required_caps Array of required primitive capabilities for the requested capability.
 * @param mixed[]  $args {
 *     Arguments that accompany the requested capability check.
 *
 *     @type string    $0 Requested capability.
 *     @type int       $1 Concerned user ID.
 *     @type mixed  ...$2 Optional second and further parameters.
 * }
 * @param WP_User  $user          Concerned user object.
 * @return bool[] Array of concerned user's capabilities.
 */
function filter_user_has_cap( array $user_caps, array $required_caps, array $args, WP_User $user ) : array {
	$cap = isset( $args[0] ) && is_string( $args[0] ) ? $args[0] : '';

	if ( '' === $cap ) {
		return $user_caps;
	}

	switch ( $cap ) {

		case 'create_guest_authors':
			if ( ! array_key_exists( $cap, $user_caps ) ) {
				$user_caps[ $cap ] = user_can( $user->ID, 'edit_others_posts' );
			}
			break;

		case 'attribute_post_type':
			if ( empty( $args[2] ) || ! is_string( $args[2] ) ) {
				$user_caps[ $cap ] = false;
				break;
			}

			$post_type_object = get_post_type_object( $args[2] );

			if ( ! $post_type_object ) {
				$user_caps[ $cap ] = false;
				break;
			}

			if ( array_key_exists( $cap, $user_caps ) ) {
				break;
			}

			/** @var stdClass */
			$post_type_caps = $post_type_object->cap;

			$user_caps[ $cap ] = user_can( $user->ID, $post_type_caps->edit_others_posts );
			break;

	}//end switch

	return $user_caps;
}

/**
 * Fires once the WordPress environment has been set up.
 *
 * This is used to correct the `$authordata` global on author archives.
 *
 * @link https://core.trac.wordpress.org/ticket/44183
 *
 * @param WP $wp Current WordPress environment instance.
 */
function action_wp( WP $wp ) : void {
	if ( is_author() ) {
		$author_id = get_query_var( 'author' );
		$author_id = is_numeric( $author_id ) ? (int) $author_id : 0;

		$GLOBALS['authordata'] = get_userdata( $author_id );
	}
}

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 */
function register_roles_and_caps() : void {
	add_role( GUEST_ROLE, __( 'Guest Author', 'authorship' ), [] );
}

/**
 * Synchronizes authorship data after a user is deleted.
 *
 * This updates attributed-author lists across all relevant sites, replacing the
 * deleted author with the reassigned user when provided, or removing the author
 * entirely otherwise.
 *
 * @param int     $deleted_user_id Deleted user ID.
 * @param mixed   $reassign        User ID to reassign content to, if provided.
 * @param WP_User $_user           Deleted user object.
 */
function action_deleted_user( int $deleted_user_id, $reassign, WP_User $_user ) : void {
	$replacement_user_id = is_numeric( $reassign ) ? (int) $reassign : 0;

	if ( $replacement_user_id > 0 && ! get_userdata( $replacement_user_id ) ) {
		$replacement_user_id = 0;
	}

	if ( $replacement_user_id === $deleted_user_id ) {
		$replacement_user_id = 0;
	}

	$site_ids = [ get_current_blog_id() ];

	if ( is_multisite() ) {
		$network_site_ids = get_sites( [
			'fields' => 'ids',
			'number' => 0,
		] );

		if ( is_array( $network_site_ids ) ) {
			$site_ids = array_map( 'intval', $network_site_ids );
		}
	}

	foreach ( $site_ids as $site_id ) {
		if ( is_multisite() ) {
			switch_to_blog( $site_id );
		}

		sync_deleted_user_authorship_for_current_site( $deleted_user_id, $replacement_user_id );

		if ( is_multisite() ) {
			restore_current_blog();
		}
	}
}

/**
 * Synchronizes deleted-user authorship state for the current site.
 *
 * @param int $deleted_user_id Deleted user ID.
 * @param int $replacement_user_id Replacement user ID, if any.
 */
function sync_deleted_user_authorship_for_current_site( int $deleted_user_id, int $replacement_user_id ) : void {
	if ( ! taxonomy_exists( TAXONOMY ) ) {
		return;
	}

	$post_types = get_supported_post_types();

	if ( empty( $post_types ) ) {
		return;
	}

	$post_ids = get_posts( [
		'fields'             => 'ids',
		'nopaging'           => true,
		'post_status'        => 'any',
		'post_type'          => $post_types,
		'suppress_filters'   => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'          => [
			[
				'taxonomy' => TAXONOMY,
				'field'    => 'slug',
				'terms'    => [ (string) $deleted_user_id ],
			],
		],
	] );

	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}

		$author_terms = wp_get_post_terms( $post->ID, TAXONOMY );

		if ( is_wp_error( $author_terms ) ) {
			continue;
		}

		$author_ids = array_map(
			static function( \WP_Term $term ) : int {
				return (int) $term->slug;
			},
			$author_terms
		);

		if ( ! in_array( $deleted_user_id, $author_ids, true ) ) {
			continue;
		}

		$updated_author_ids = [];

		foreach ( $author_ids as $author_id ) {
			if ( $author_id === $deleted_user_id ) {
				$author_id = $replacement_user_id;
			}

			if ( $author_id <= 0 || in_array( $author_id, $updated_author_ids, true ) ) {
				continue;
			}

			$updated_author_ids[] = $author_id;
		}

		if ( empty( $updated_author_ids ) ) {
			wp_set_post_terms( $post->ID, [], TAXONOMY );
			continue;
		}

		$terms = wp_set_post_terms( $post->ID, array_map( 'strval', $updated_author_ids ), TAXONOMY );

		if ( is_wp_error( $terms ) ) {
			continue;
		}
	}//end foreach

	$term = get_term_by( 'slug', (string) $deleted_user_id, TAXONOMY );

	if ( ! ( $term instanceof \WP_Term ) ) {
		return;
	}

	$term = get_term( $term->term_id, TAXONOMY );

	if ( $term instanceof \WP_Term && 0 === $term->count ) {
		wp_delete_term( $term->term_id, TAXONOMY );
	}
}

/**
 * Filters the response immediately after executing any REST API callbacks.
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $result  Result to send to the client. Usually a WP_REST_Response or WP_Error.
 * @param mixed[]                                          $handler Route handler used for the request.
 * @param WP_REST_Request                                  $request Request used to generate the response.
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed Result to send to the client. Usually a WP_REST_Response or WP_Error.
 */
function filter_rest_request_after_callbacks( $result, array $handler, WP_REST_Request $request ) {
	if ( ! ( $result instanceof WP_REST_Response ) ) {
		return $result;
	}

	$data = $result->get_data();

	if ( ! is_array( $data ) || ! isset( $data[ REST_PARAM ] ) ) {
		return $result;
	}

	// Avoid embedding-author link lookups that non-admin editors cannot resolve in edit context.
	if ( 'edit' === $request->get_param( 'context' ) && ! current_user_can( 'list_users' ) ) {
		return $result;
	}

	/** @var int $author */
	foreach ( $data[ REST_PARAM ] as $author ) {
		$result->add_link(
			REST_LINK_ID,
			sprintf(
				rest_url( 'wp/v2/users/%d' ),
				$author
			),
			[
				'embeddable' => true,
			]
		);
	}

	return $result;
}

/**
 * Adds the authorship field to the REST API for post objects.
 *
 * @param WP_REST_Server $server Server object.
 */
function register_rest_api_fields( WP_REST_Server $server ) : void {
	$post_types = get_supported_post_types();

	array_walk( $post_types, __NAMESPACE__ . '\\register_rest_api_field' );

	$users_controller = new Users_Controller;
	$users_controller->register_routes();
}

/**
 * Validates a passed REST API argument for the list of authors.
 *
 * @param mixed           $authors   The passed value.
 * @param WP_REST_Request $request   The REST API request object.
 * @param string          $param     The param name.
 * @param string          $post_type The post type name.
 * @return WP_Error|null Null if the validation passes, `WP_Error` instance otherwise.
 */
function validate_authors( $authors, WP_REST_Request $request, string $param, string $post_type ) :? WP_Error {
	$schema_validation = rest_validate_request_arg( $authors, $request, $param );

	if ( is_wp_error( $schema_validation ) ) {
		return $schema_validation;
	}

	if ( ! current_user_can( 'attribute_post_type', $post_type ) ) {
		return new WP_Error( 'authorship', __( 'You are not allowed to set the attributed authors of this post.', 'authorship' ), [
			'status' => WP_Http::FORBIDDEN,
		] );
	}

	if ( ! is_post_type_supported( $post_type ) ) {
		return new WP_Error( 'authorship', __( 'This post type does not support attributed authors.', 'authorship' ), [
			'status' => WP_Http::BAD_REQUEST,
		] );
	}

	// The REST API accepts and coerces a comma-separated string as an array, so
	// we need to allow for that here.
	if ( ! is_array( $authors ) && ! is_string( $authors ) ) {
		return new WP_Error( 'authorship', __( 'The authors payload must be an array or comma-separated string.', 'authorship' ), [
			'status' => WP_Http::BAD_REQUEST,
		] );
	}

	$authors = wp_parse_id_list( $authors );

	/** @var WP_User[] */
	$users = get_users( [
		// Check all sites.
		'blog_id' => 0,
		'include' => $authors,
		'orderby' => 'include',
	] );

	if ( count( $users ) !== count( $authors ) ) {
		return new WP_Error( 'authorship', __( 'One or more user IDs are not valid for this site.', 'authorship' ), [
			'status' => WP_Http::BAD_REQUEST,
		] );
	}

	return null;
}

/**
 * Register the Authorship REST API field for the given post type.
 *
 * @param string $post_type The post type name.
 */
function register_rest_api_field( string $post_type ) : void {
	$validate_callback = function( $authors, WP_REST_Request $request, string $param ) use ( $post_type ) :? WP_Error {
		return validate_authors( $authors, $request, $param, $post_type );
	};

	register_rest_field( $post_type, REST_PARAM, [
		'get_callback' => function( array $post ) : array {
			$post = get_post( $post['id'] );

			if ( ! $post ) {
				return [];
			}

			return array_map( function( WP_User $user ) : int {
				return $user->ID;
			}, get_authors( $post ) );
		},
		'update_callback' => function( $value, WP_Post $post, string $field, WP_REST_Request $request, string $post_type ) :? WP_Error {
			try {
				set_authors( $post, wp_parse_id_list( $value ) );
			} catch ( Exception $e ) {
				return new WP_Error( 'authorship', $e->getMessage(), [
					'status' => WP_Http::BAD_REQUEST,
				] );
			}

			return null;
		},
		'schema'       => [
			'description' => __( 'Authors', 'authorship' ),
			'type'        => 'array',
			'items'       => [
				'type' => 'integer',
			],
			'arg_options' => [
				'validate_callback' => $validate_callback,
			],
		],
	] );

	add_filter( "rest_prepare_{$post_type}", __NAMESPACE__ . '\\rest_prepare_post', 10, 3 );
}

/**
 * Filters the post data for a REST API response.
 *
 * This removes the `wp:action-assign-author` rel from the response so the default post author
 * control doesn't get shown on the block editor post editing screen.
 *
 * This also adds a new `authorship:action-assign-authorship` rel so custom clients can refer to this.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     Post object.
 * @param WP_REST_Request  $request  Request object.
 * @return WP_REST_Response The response object.
 */
function rest_prepare_post( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) : WP_REST_Response {
	$links = $response->get_links();

	if ( isset( $links['https://api.w.org/action-assign-author'] ) ) {
		$response->remove_link( 'https://api.w.org/action-assign-author' );
		$response->add_link( REST_REL_LINK_ID, $links['self'][0]['href'] );
	}

	return $response;
}

/**
 * Filters extra CURIEs available on REST API responses.
 *
 * @param array<int,array{name:string,href:string,templated:bool}> $additional Additional CURIEs to register with the API.
 * @return array<int,array{name:string,href:string,templated:bool}> Additional CURIEs to register with the API.
 */
function filter_rest_response_link_curies( array $additional ) : array {
	$additional[] = [
		'name'      => REST_PARAM,
		'href'      => REST_CURIE_TEMPLATE,
		'templated' => true,
	];

	return $additional;
}

/**
 * Fires after block assets have been enqueued for the editing interface.
 */
function enqueue_assets() : void {
	/** @var WP_Post|null */
	$post = get_post();

	if ( ! $post || ! is_post_type_supported( $post->post_type ) ) {
		return;
	}//end if

	enqueue_assets_for_post();
	preload_author_data( $post );
}

/**
 * Enqueues the JS and CSS assets for the author selection control.
 */
function enqueue_assets_for_post() : void {
	$plugin_dir = plugin_dir_path( __DIR__ );
	$plugin_url = plugin_dir_url( __DIR__ );
	$manifest   = $plugin_dir . 'build/asset-manifest.json';
	$script_relative_path = 'build/main.js';
	$script_path          = $plugin_dir . $script_relative_path;
	$style_relative_path  = 'build/style.css';
	$style_path           = $plugin_dir . $style_relative_path;
	$asset_file           = $plugin_dir . 'build/main.asset.php';

	if ( ! file_exists( $style_path ) ) {
		$style_relative_path = 'build/style-style.css';
		$style_path          = $plugin_dir . $style_relative_path;
	}

	$script_dependencies = [
		'lodash',
		'react',
		'wp-api-fetch',
		'wp-block-editor',
		'wp-blocks',
		'wp-compose',
		'wp-components',
		'wp-data',
		'wp-edit-post',
		'wp-element',
		'wp-i18n',
		'wp-plugins',
		'wp-polyfill',
		'wp-url',
	];

	if ( file_exists( $manifest ) ) {
		enqueue_asset(
			$manifest,
			'main.js',
			[
				'handle'       => SCRIPT_HANDLE,
				'dependencies' => $script_dependencies,
			]
		);

		enqueue_asset(
			$manifest,
			'style.css',
			[
				'handle' => STYLE_HANDLE,
			]
		);

		return;
	}//end if

	if ( file_exists( $asset_file ) && file_exists( $script_path ) ) {
		$asset_metadata = require $asset_file;
		$dependencies   = $script_dependencies;
		$version        = null;

		if ( is_array( $asset_metadata ) ) {
			if ( isset( $asset_metadata['dependencies'] ) && is_array( $asset_metadata['dependencies'] ) ) {
				$dependencies = $asset_metadata['dependencies'];
			}

			if ( isset( $asset_metadata['version'] ) && is_scalar( $asset_metadata['version'] ) ) {
				$version = (string) $asset_metadata['version'];
			}
		}

		wp_enqueue_script(
			SCRIPT_HANDLE,
			$plugin_url . $script_relative_path,
			$dependencies,
			$version,
			true
		);

		if ( file_exists( $style_path ) ) {
			$style_version = $version;

			if ( null === $style_version ) {
				$style_file_mtime = filemtime( $style_path );
				$style_version    = false !== $style_file_mtime ? (string) $style_file_mtime : null;
			}

			wp_enqueue_style(
				STYLE_HANDLE,
				$plugin_url . $style_relative_path,
				[],
				$style_version
			);
		}

		return;
	}//end if

	if ( file_exists( $script_path ) ) {
		$script_version = filemtime( $script_path );

		wp_enqueue_script(
			SCRIPT_HANDLE,
			$plugin_url . $script_relative_path,
			$script_dependencies,
			false !== $script_version ? (string) $script_version : null,
			true
		);
	}

	if ( file_exists( $style_path ) ) {
		$style_version = filemtime( $style_path );

		wp_enqueue_style(
			STYLE_HANDLE,
			$plugin_url . $style_relative_path,
			[],
			false !== $style_version ? (string) $style_version : null
		);
	}
}

/**
 * Preloads author data for the post editing screen.
 *
 * @param WP_Post $post The post being edited.
 */
function preload_author_data( WP_Post $post ) : void {
	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		$authors = [
			wp_get_current_user(),
		];
	}

	$authors = array_map( function( WP_User $user ) {
		$avatar = get_avatar_url( $user->ID );

		return [
			'value'  => $user->ID,
			'label'  => $user->display_name,
			'avatar' => $avatar ? $avatar : null,
		];
	}, $authors );

	wp_localize_script(
		SCRIPT_HANDLE,
		'authorshipData',
		[
			'authors' => $authors,
		]
	);
}

/**
 * Fires after the query variable object is created, but before the actual query is run.
 *
 * This is used to override author-related query vars with a corresponding taxonomy query and
 * then add a second filter that resets the vars after the query has run.
 *
 * @param WP_Query $query The WP_Query instance.
 */
function action_pre_get_posts( WP_Query $query ) : void {
	$post_type = $query->get( 'post_type' );
	$supported_post_types = get_supported_post_types();

	if ( empty( $post_type ) ) {
		$post_type = 'post';
	}

	if ( 'any' === $post_type ) {
		$post_type = $supported_post_types;
		$query->set( 'post_type', $post_type );
	} else {
		$requested_post_types = (array) $post_type;
		$matching_post_types = array_values( array_intersect( $requested_post_types, $supported_post_types ) );

		if ( empty( $matching_post_types ) ) {
			return;
		}

		if ( count( $matching_post_types ) !== count( $requested_post_types ) ) {
			// Mixed supported/unsupported post-type queries cannot preserve both
			// Authorship taxonomy semantics and core post_author semantics at once,
			// so narrow author-filtered queries to the supported set explicitly.
			$post_type = $matching_post_types;
			$query->set( 'post_type', $post_type );
		}
	}

	$stored_values = [];

	// Different query args and their default values.
	$concerns = [
		'author_name' => '',
		'author' => '',
		'author__in' => [],
		'author__not_in' => [],
	];

	// Record the original values of concerned query vars and remove them from the query.
	foreach ( $concerns as $concern => $concern_default_value ) {
		$value = $query->get( $concern );
		if ( ! empty( $value ) ) {
			$stored_values[ $concern ] = $value;
			$query->set( $concern, $concern_default_value );
		}//end if
	}

	// None of the set query vars concern us? Then we have nothing more to do.
	if ( empty( $stored_values ) ) {
		return;
	}

	$user_ids = [ 0 ];

	// Get a user ID from either `author` or `author_name`. The ID doesn't have to be valid
	// as WP_Query will handle the validation before constructing its query.
	if ( ! empty( $stored_values['author'] ) ) {
		if ( is_string( $stored_values['author'] ) ) {
			$user_ids = array_map(
				static function( $id ) : int {
					return (int) $id;
				},
				explode( ',', $stored_values['author'] )
			);
		} elseif ( is_numeric( $stored_values['author'] ) ) {
			$user_ids = [ (int) $stored_values['author'] ];
		}//end if
	} elseif ( ! empty( $stored_values['author_name'] ) ) {
		if ( is_string( $stored_values['author_name'] ) ) {
			$user = get_user_by( 'slug', $stored_values['author_name'] );

			if ( $user ) {
				$user_ids = [ $user->ID ];
			}
		}//end if
	} elseif ( ! empty( $stored_values['author__in'] ) ) {
		if ( is_array( $stored_values['author__in'] ) ) {
			$user_ids = array_map(
				static function( $id ) : int {
					return (int) $id;
				},
				$stored_values['author__in']
			);
		}
	} elseif ( ! empty( $stored_values['author__not_in'] ) ) {
		if ( is_array( $stored_values['author__not_in'] ) ) {
			$user_ids = array_map(
				static function( int $id ) : int {
					return $id * -1;
				},
				array_map(
					static function( $id ) : int {
						return (int) $id;
					},
					$stored_values['author__not_in']
				)
			);
		}
	}//end if

	$tax_query = $query->get( 'tax_query' );

	// Record the value of an existing tax query, if there is one.
	$stored_values['tax_query'] = $tax_query;

	if ( ! is_array( $tax_query ) ) {
		$tax_query = [];
	}

	// Add a corresponding tax query that queries for posts with terms with a slug matching the requested user ID.
	$tax_query[] = [
		'taxonomy' => TAXONOMY,
		'terms'    => array_map( 'absint', $user_ids ),
		'field'    => 'slug',
		// negative values meant NOT IN rather than IN.
		'operator' => current( $user_ids ) >= 0 ? 'IN' : 'NOT IN',
	];

	$query->set( 'tax_query', $tax_query );

	/**
	 * Filters the posts array before the query takes place.
	 *
	 * This allows the query vars to be reset to their original values.
	 *
	 * @param WP_Post[]|null $posts Array of post objects. Passed by reference.
	 * @param WP_Query       $query The WP_Query instance.
	 */
	add_filter( 'posts_pre_query', function( ?array $posts, WP_Query $query ) use ( &$stored_values, $user_ids ) : ?array {
		if ( empty( $stored_values ) ) {
			return $posts;
		}

		// Reset the query vars to their original values.
		foreach ( $stored_values as $concern => $value ) {
			$query->set( $concern, $value );
		}

		// Specifically set `author` when `author_name` is in use as WP_Query also sets `author` internally.
		if ( ! empty( $stored_values['author_name'] ) ) {
			$query->set( 'author', $user_ids[0] );
		}

		// Clear the recorded values so subsequent queries are not affected.
		$stored_values = [];

		return $posts;
	}, 999, 2 );
}

/**
 * Filters the list of recipients for comment moderation emails.
 *
 * @param string[] $emails     List of email addresses to notify for comment moderation.
 * @param int      $comment_id Comment ID.
 * @return string[] List of email addresses to notify for comment moderation.
 */
function filter_comment_moderation_recipients( array $emails, int $comment_id ) : array {
	/** @var \WP_Comment */
	$comment = get_comment( $comment_id );
	$post_id = (int) $comment->comment_post_ID;

	if ( ! $post_id ) {
		return $emails;
	}

	$post = get_post( $post_id );

	if ( ! ( $post instanceof WP_Post ) ) {
		return $emails;
	}

	$authors = get_authors( $post );

	$moderators = array_filter( $authors, function( WP_User $user ) use ( $comment ) : bool {
		return user_can( $user->ID, 'edit_comment', $comment->comment_ID );
	} );

	$additional_emails = array_filter( array_map( function( WP_User $user ) : string {
		return $user->user_email;
	}, $moderators ) );

	return array_unique( array_merge( $emails, $additional_emails ) );
}

/**
 * Filters the list of email addresses to receive a comment notification.
 *
 * @param string[] $emails     An array of email addresses to receive a comment notification.
 * @param int      $comment_id The comment ID.
 * @return string[] An array of email addresses to receive a comment notification.
 */
function filter_comment_notification_recipients( array $emails, int $comment_id ) : array {
	/** @var \WP_Comment */
	$comment = get_comment( $comment_id );
	$post_id = (int) $comment->comment_post_ID;

	if ( ! $post_id ) {
		return $emails;
	}

	$post = get_post( $post_id );

	if ( ! ( $post instanceof WP_Post ) ) {
		return $emails;
	}

	$authors = get_authors( $post );

	/** @var string[] */
	$additional_emails = array_filter( array_map( function( WP_User $user ) : string {
		return $user->user_email;
	}, $authors ) );

	return array_unique( array_merge( $emails, $additional_emails ) );
}

/**
 * Hide author select from quick edit.
 *
 * Bit of a hack, but filter filter_quickedit_authors and include only author with ID 0.
 * Also hide if only one author just in case someone someone has created author with 0.
 *
 * @param array<string, mixed> $options Options.
 * @return array<string, mixed> Filtered options.
 */
function hide_quickedit_authors( array $options ) : array {
	$options['hide_if_only_one_author'] = true;
	$options['include'] = [ 0 ];
	return $options;
}

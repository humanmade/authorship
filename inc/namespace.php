<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Error;
use WP_Http;
use WP_HTTP_Response;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Term;
use WP_User;

const COLUMN_NAME = 'authorship';
const POSTS_PARAM = 'authorship';
const REST_LINK_ID = 'wp:authorship';
const REST_PARAM = 'authorship';
const ROLE = 'guest-author';
const SCRIPT_HANDLE = 'authorship-js';
const STYLE_HANDLE = 'authorship-css';
const TAXONOMY = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap() : void {
	// Actions.
	add_action( 'init', __NAMESPACE__ . '\\init_taxonomy', 99 );
	add_action( 'init', __NAMESPACE__ . '\\register_roles_and_caps', 1 );
	add_action( 'admin_init', __NAMESPACE__ . '\\init_admin_cols', 99 );
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_api_fields' );
	add_action( 'wp_insert_post', __NAMESPACE__ . '\\action_wp_insert_post', 10, 3 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_assets' );

	// Filters.
	add_filter( 'rest_pre_dispatch', __NAMESPACE__ . '\\filter_rest_pre_dispatch', 10, 3 );
	add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\filter_wp_insert_post_data', 10, 3 );
	add_filter( 'rest_post_dispatch', __NAMESPACE__ . '\\filter_rest_post_dispatch' );
}

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 */
function register_roles_and_caps() : void {
	add_role( ROLE, __( 'Guest Author', 'authorship' ), [] );
}

/**
 * Fires as an admin screen or script is being initialized.
 */
function init_admin_cols() : void {
	$post_types = get_post_types_by_support( 'author' );

	array_map( function( string $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", __NAMESPACE__ . '\\filter_post_columns' );
		add_action( "manage_{$post_type}_posts_custom_column", __NAMESPACE__ . '\\action_author_column', 10, 2 );
	}, $post_types );
}

/**
 * Fires for each custom column of a specific post type in the Posts list table.
 *
 * @param string $column_name The name of the column to display.
 * @param int    $post_id     The current post ID.
 */
function action_author_column( string $column_name, int $post_id ) : void {
	if ( COLUMN_NAME !== $column_name ) {
		return;
	}

	/**
	 * The post for this row.
	 *
	 * @var \WP_Post
	 */
	$post = get_post( $post_id );

	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		return;
	}

	echo '<ul style="margin:0">';

	foreach ( $authors as $user ) {
		$url = add_query_arg( [
			'post_type' => $post->post_type,
			'author'    => $user->ID,
		], admin_url( 'edit.php' ) );
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( $url ),
			esc_html( $user->display_name )
		);
	}

	echo '</ul>';
}

/**
 * Filters the columns displayed in the Posts list table for a specific post type.
 *
 * @param string[] $post_columns An associative array of column headings.
 * @return string[] An associative array of column headings.
 */
function filter_post_columns( array $post_columns ) : array {
	$new_columns = [];

	foreach ( $post_columns as $key => $value ) {
		if ( 'author' === $key ) {
			$new_columns[ COLUMN_NAME ] = __( 'Authors', 'authorship' );
		} else {
			$new_columns[ $key ] = $value;
		}
	}

	return $new_columns;
}

/**
 * Filters the REST API response.
 *
 * @param WP_HTTP_Response $result Result to send to the client. Usually a `WP_REST_Response`.
 * @return WP_HTTP_Response Result to send to the client. Usually a `WP_REST_Response`.
 */
function filter_rest_post_dispatch( WP_HTTP_Response $result ) : WP_HTTP_Response {
	if ( ! ( $result instanceof WP_REST_Response ) ) {
		return $result;
	}

	$data = $result->get_data();

	if ( ! isset( $data[ REST_PARAM ] ) ) {
		return $result;
	}

	foreach ( $data[ REST_PARAM ] as $author ) {
		$result->add_link( REST_LINK_ID, sprintf(
			rest_url( 'wp/v2/users/%d' ),
			$author
		) );
	}

	return $result;
}

/**
 * Filters slashed post data just before it is inserted into the database.
 *
 * @param mixed[] $data                An array of slashed, sanitized, and processed post data.
 * @param mixed[] $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
 * @param mixed[] $unsanitized_postarr An array of slashed yet _unsanitized_ and unprocessed post data as
 *                                     originally passed to wp_insert_post().
 * @return mixed[] An array of slashed, sanitized, and processed post data.
 */
function filter_wp_insert_post_data( array $data, array $postarr, array $unsanitized_postarr ) : array {
	global $authorship_postarr;

	$authorship_postarr = $unsanitized_postarr;

	return $data;
}

/**
 * Fires once a post has been saved.
 *
 * @param int     $post_ID Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 */
function action_wp_insert_post( int $post_ID, WP_Post $post, bool $update ) : void {
	global $authorship_postarr;

	if ( isset( $authorship_postarr['tax_input'] ) && ! empty( $authorship_postarr['tax_input'][ TAXONOMY ] ) ) {
		return;
	}

	if ( isset( $authorship_postarr[ POSTS_PARAM ] ) ) {
		$authors = $authorship_postarr[ POSTS_PARAM ];
	} elseif ( ! empty( $authorship_postarr['post_author'] ) ) {
		$authors = [
			$authorship_postarr['post_author'],
		];
	}

	if ( ! isset( $authors ) ) {
		return;
	}

	try {
		set_authors( $post, $authors );
	} catch ( \Exception $e ) {
		// Nothing at the moment.
	}
}

/**
 * Allows the `author` field to be used in the REST API in place of `authorship` for compatibility.
 *
 * @param mixed           $result  Response to replace the requested version with.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return mixed Response to replace the requested version with.
 */
function filter_rest_pre_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {
	$author     = $request->get_param( 'author' );
	$authorship = $request->get_param( REST_PARAM );

	if ( ( null === $authorship ) && ! empty( $author ) ) {
		$request->set_param( REST_PARAM, [ intval( $author ) ] );
	}

	return $result;
}

/**
 * Adds the authorship field to the REST API for post objects.
 *
 * @param WP_REST_Server $server Server object.
 */
function register_rest_api_fields( WP_REST_Server $server ) : void {
	$post_types = get_post_types_by_support( 'author' );

	array_map( __NAMESPACE__ . '\\register_rest_api_field', $post_types );
}

/**
 * Gets the user objects for the authors of the given post.
 *
 * @param WP_Post $post The post object.
 * @return WP_User[] Array of user objects.
 */
function get_authors( WP_Post $post ) : array {
	if ( ! post_type_supports( $post->post_type, 'author' ) ) {
		return [];
	}

	/**
	 * Term objects.
	 *
	 * @var WP_Term[] $authors
	 */
	$authors = wp_get_post_terms( $post->ID, TAXONOMY, [
		'orderby' => 'term_order',
		'order'   => 'ASC',
	] );

	if ( empty( $authors ) ) {
		return [];
	}

	/**
	 * User objects.
	 *
	 * @var WP_User[] $users
	 */
	$users = get_users( [
		'include' => array_map( function( WP_Term $term ) : int {
			return intval( $term->name );
		}, $authors ),
		'orderby' => 'include',
	] );

	return $users;
}

/**
 * Sets the authors for the given post.
 *
 * @param WP_Post $post    The post object.
 * @param int[]   $authors Array of user IDs.
 * @throws \Exception If any of the users do not exist.
 * @return WP_User[] Array of user objects.
 */
function set_authors( WP_Post $post, array $authors ) : array {
	if ( ! post_type_supports( $post->post_type, 'author' ) ) {
		throw new \Exception( __( 'This post type does not support authorship.', 'authorship' ) );
	}

	/**
	 * Author IDs mapped to integers.
	 *
	 * @var int[] $authors
	 */
	$authors = array_filter( array_map( 'intval', $authors ) );

	/**
	 * User objects.
	 *
	 * @var WP_User[] $users
	 */
	$users = get_users( [
		'include' => $authors,
		'orderby' => 'include',
	] );

	if ( count( $users ) !== count( $authors ) ) {
		throw new \Exception( __( 'One or more user IDs are not valid for this site.', 'authorship' ) );
	}

	// Author IDs must be mapped to strings before passing to `wp_set_post_terms()`.
	$terms = wp_set_post_terms( $post->ID, array_map( 'strval', $authors ), TAXONOMY );

	if ( is_wp_error( $terms ) ) {
		throw new \Exception( $terms->get_error_message() );
	}

	return $users;
}

/**
 * Validates a passed argument for the list of authors.
 *
 * @param mixed           $authors   The passed value.
 * @param WP_REST_Request $request   The REST API request object.
 * @param string          $param     The param name.
 * @param string          $post_type The post type name.
 * @return WP_Error True if the validation passes, `WP_Error` instance otherwise.
 */
function validate_authors( $authors, WP_REST_Request $request, string $param, string $post_type ) :? WP_Error {
	$schema_validation = rest_validate_request_arg( $authors, $request, $param );

	if ( is_wp_error( $schema_validation ) ) {
		return $schema_validation;
	}

	$post_type_object = get_post_type_object( $post_type );

	if ( ! $post_type_object ) {
		return null;
	}

	/**
	 * Post type capabilities.
	 *
	 * @var \stdClass
	 */
	$caps = $post_type_object->cap;

	if ( ! current_user_can( $caps->edit_others_posts ) ) {
		return new WP_Error( 'authorship', __( 'You are not allowed to set the authors for this post.', 'authorship' ) );
	}

	// The REST API accepts and coerces a comma-separated string as an array, so
	// we need to allow for that here.
	$authors = wp_parse_id_list( $authors );

	/**
	 * User objects.
	 *
	 * @var WP_User[] $users
	 */
	$users = get_users( [
		'include' => $authors,
		'orderby' => 'include',
	] );

	if ( count( $users ) !== count( $authors ) ) {
		return new WP_Error( 'authorship', __( 'One or more user IDs are not valid for this site.', 'authorship' ) );
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
		'update_callback' => function( array $value, WP_Post $post, string $field, WP_REST_Request $request, string $post_type ) :? WP_Error {
			try {
				set_authors( $post, $value );
			} catch ( \Exception $e ) {
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
}

/**
 * Registers the taxonomy that creates a connection between posts and users.
 */
function init_taxonomy() : void {
	$post_types = get_post_types_by_support( 'author' );

	register_taxonomy(
		TAXONOMY,
		$post_types,
		[
			'hierarchical'      => false,
			'sort'              => true,
			'public'            => false,
			'show_in_rest'      => false,
			'capabilities'      => [
				'manage_terms' => 'edit_posts',
				'edit_terms'   => 'edit_posts',
				'delete_terms' => 'edit_posts',
				'assign_terms' => 'edit_posts',
			],
			'labels'            => [
				'name'                       => __( 'Authors', 'authorship' ),
				'singular_name'              => _x( 'Author', 'taxonomy general name', 'authorship' ),
				'search_items'               => __( 'Search authors', 'authorship' ),
				'popular_items'              => __( 'Popular authors', 'authorship' ),
				'all_items'                  => __( 'All authors', 'authorship' ),
				'parent_item'                => __( 'Parent Author', 'authorship' ),
				'parent_item_colon'          => __( 'Parent Author:', 'authorship' ),
				'edit_item'                  => __( 'Edit Author', 'authorship' ),
				'update_item'                => __( 'Update Author', 'authorship' ),
				'view_item'                  => __( 'View Author', 'authorship' ),
				'add_new_item'               => __( 'Add New Author', 'authorship' ),
				'new_item_name'              => __( 'New Author', 'authorship' ),
				'separate_items_with_commas' => __( 'Separate authors with commas', 'authorship' ),
				'add_or_remove_items'        => __( 'Add or remove authors', 'authorship' ),
				'choose_from_most_used'      => __( 'Choose from the most used authors', 'authorship' ),
				'not_found'                  => __( 'No authors found.', 'authorship' ),
				'no_terms'                   => __( 'No authors', 'authorship' ),
				'menu_name'                  => __( 'Authors', 'authorship' ),
				'items_list_navigation'      => __( 'Authors list navigation', 'authorship' ),
				'items_list'                 => __( 'Authors list', 'authorship' ),
				'most_used'                  => _x( 'Most Used', 'author', 'authorship' ),
				'back_to_items'              => __( '&larr; Back to authors', 'authorship' ),
			],
		]
	);
}

/**
 * Fires after block assets have been enqueued for the editing interface.
 */
function enqueue_assets() : void {
	/**
	 * The post being edited.
	 *
	 * @var \WP_Post
	 */
	$post = get_post();

	enqueue_assets_for_post();
	preload_author_data( $post );
}

/**
 * Enqueues the JS and CSS assets for the author selection control.
 */
function enqueue_assets_for_post() : void {
	$manifest = plugin_dir_path( __DIR__ ) . 'build/asset-manifest.json';

	\Asset_Loader\enqueue_asset(
		$manifest,
		'main.js',
		[
			'handle'       => SCRIPT_HANDLE,
			// @TODO check:
			'dependencies' => [
				'react',
				'wp-block-editor',
				'wp-blocks',
				'wp-components',
				'wp-element',
				'wp-i18n',
				'wp-polyfill',
			],
		]
	);
	\Asset_Loader\enqueue_asset(
		$manifest,
		'style.css',
		[
			'handle' => STYLE_HANDLE,
		]
	);
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
		return [
			'value'  => $user->ID,
			'label'  => $user->display_name,
			'avatar' => get_avatar_url( $user->ID ),
		];
	}, $authors );

	// @TODO replace this with data from the preloaded REST API response for the post
	// that's included on the post editing screen. Need to enable the user objects to
	// be embedded for that, we've only got the list of user IDs at the moment.
	wp_localize_script(
		SCRIPT_HANDLE,
		'authorshipData',
		[
			'authors' => $authors,
		]
	);
}

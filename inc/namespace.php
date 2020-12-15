<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP;
use WP_Error;
use WP_Http;
use WP_HTTP_Response;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Term;
use WP_User;

const POSTS_PARAM = 'authorship';
const REST_LINK_ID = 'wp:authorship';
const REST_REL_LINK_ID = 'https://authorship.hmn.md/action-assign-authorship';
const REST_PARAM = 'authorship';
const GUEST_ROLE = 'guest-author';
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
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_api_fields' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_assets' );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\action_pre_get_posts', 9999 );
	add_action( 'wp', __NAMESPACE__ . '\\action_wp' );

	// Filters.
	add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\filter_wp_insert_post_data', 10, 3 );
	add_filter( 'rest_post_dispatch', __NAMESPACE__ . '\\filter_rest_post_dispatch' );
	add_filter( 'map_meta_cap', __NAMESPACE__ . '\\filter_map_meta_cap_for_editing', 10, 4 );
	add_filter( 'map_meta_cap', __NAMESPACE__ . '\\filter_map_meta_cap_for_users', 10, 4 );
	add_filter( 'rest_response_link_curies', __NAMESPACE__ . '\\filter_rest_response_link_curies' );
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

	if ( empty( $user_id ) || empty( $args[0] ) ) {
		return $caps;
	}
	$user = get_userdata( $user_id );
	$post = get_post( $args[0] );

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

	/** @var \stdClass */
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
			$caps = array_filter( $caps, function( string $cap ) use ( $remove ) : bool {
				return ! in_array( $cap, $remove['delete'], true );
			} );

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
			$caps = array_filter( $caps, function( string $cap ) use ( $remove ) : bool {
				return ! in_array( $cap, $remove['edit'], true );
			} );

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
			$caps = array_filter( $caps, function( string $cap ) use ( $remove ) : bool {
				return ! in_array( $cap, $remove['read'], true );
			} );

			$caps[] = $post_type_cap->read;
			break;
	}//end switch

	return $caps;
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
function filter_map_meta_cap_for_users( array $caps, string $cap, int $user_id, array $args ) : array {
	$concerns = [
		'create_guest_authors',
	];

	if ( ! in_array( $cap, $concerns, true ) ) {
		return $caps;
	}

	$caps = [
		'edit_others_posts',
	];

	return $caps;
}

/**
 * Fires once the WordPress environment has been set up.
 *
 * This is used to correct the `$authordata` global on author archives.
 *
 * @link https://core.trac.wordpress.org/ticket/44183
 *
 * @param \WP $wp Current WordPress environment instance.
 */
function action_wp( WP $wp ) : void {
	global $wp_query;

	if ( $wp_query->is_author() ) {
		$GLOBALS['authordata'] = get_userdata( $wp_query->get( 'author' ) );
	}
}
/**
 * Fires after WordPress has finished loading but before any headers are sent.
 */
function register_roles_and_caps() : void {
	add_role( GUEST_ROLE, __( 'Guest Author', 'authorship' ), [] );
}

/**
 * Filters the REST API response.
 *
 * @param \WP_HTTP_Response $result Result to send to the client. Usually a `\WP_REST_Response`.
 * @return \WP_HTTP_Response Result to send to the client. Usually a `\WP_REST_Response`.
 */
function filter_rest_post_dispatch( WP_HTTP_Response $result ) : WP_HTTP_Response {
	if ( ! ( $result instanceof WP_REST_Response ) ) {
		return $result;
	}

	$data = $result->get_data();

	if ( ! isset( $data[ REST_PARAM ] ) ) {
		return $result;
	}

	/** @var int $author */
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
	/**
	 * Fires once a post has been saved.
	 *
	 * @param int      $post_ID Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	add_action( 'wp_insert_post', function( int $post_ID, WP_Post $post, bool $update ) use ( $unsanitized_postarr ) : void {
		if ( isset( $unsanitized_postarr['tax_input'] ) && ! empty( $unsanitized_postarr['tax_input'][ TAXONOMY ] ) ) {
			return;
		}

		$existing_authors = get_authors( $post );

		if ( $update && ! isset( $unsanitized_postarr[ POSTS_PARAM ] ) && $existing_authors ) {
			return;
		}

		if ( isset( $unsanitized_postarr[ POSTS_PARAM ] ) ) {
			$authors = $unsanitized_postarr[ POSTS_PARAM ];
		} elseif ( ! empty( $unsanitized_postarr['post_author'] ) ) {
			$authors = [
				$unsanitized_postarr['post_author'],
			];
		}

		if ( ! isset( $authors ) ) {
			return;
		}

		try {
			set_authors( $post, wp_parse_id_list( $authors ) );
		} catch ( \Exception $e ) {
			// Nothing at the moment.
		}
	}, 10, 3 );

	return $data;
}

/**
 * Adds the authorship field to the REST API for post objects.
 *
 * @param \WP_REST_Server $server Server object.
 */
function register_rest_api_fields( WP_REST_Server $server ) : void {
	$post_types = get_post_types_by_support( 'author' );

	array_map( __NAMESPACE__ . '\\register_rest_api_field', $post_types );

	$users_controller = new Users_Controller;
	$users_controller->register_routes();
}

/**
 * Validates a passed argument for the list of authors.
 *
 * @param mixed            $authors   The passed value.
 * @param \WP_REST_Request $request   The REST API request object.
 * @param string           $param     The param name.
 * @param string           $post_type The post type name.
 * @return \WP_Error True if the validation passes, `\WP_Error` instance otherwise.
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

	/** @var \stdClass */
	$caps = $post_type_object->cap;

	if ( ! current_user_can( $caps->edit_others_posts ) ) {
		return new WP_Error( 'authorship', __( 'You are not allowed to set the authorship for this post.', 'authorship' ), [
			'status' => WP_Http::FORBIDDEN,
		] );
	}

	if ( ! post_type_supports( $post_type, 'author' ) ) {
		return new WP_Error( 'authorship', __( 'This post type does not support specifying authorship.', 'authorship' ), [
			'status' => WP_Http::BAD_REQUEST,
		] );
	}

	// The REST API accepts and coerces a comma-separated string as an array, so
	// we need to allow for that here.
	$authors = wp_parse_id_list( $authors );

	/** @var \WP_User[] */
	$users = get_users( [
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
 * @param \WP_REST_Response $response The response object.
 * @param \WP_Post          $post     Post object.
 * @param \WP_REST_Request  $request  Request object.
 * @return \WP_REST_Response The response object.
 */
function rest_prepare_post( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) : \WP_REST_Response {
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
 * @param array[] $additional Additional CURIEs to register with the API.
 * @return array[] Additional CURIEs to register with the API.
 */
function filter_rest_response_link_curies( array $additional ) : array {
	$additional[] = [
		'name'      => REST_PARAM,
		'href'      => 'https://authorship.hmn.md/{rel}',
		'templated' => true,
	];

	return $additional;
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
			'args'              => [
				'orderby' => 'term_order',
				'order'   => 'ASC',
			],
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

	array_map( function( string $post_type ) {
		/**
		 * Filters the list of available list table views.
		 *
		 * @param string[] $views An array of available list table views.
		 * @return string[] An array of available list table views.
		 */
		add_filter( "views_edit-{$post_type}", function( array $views ) use ( $post_type ) : array {
			unset( $views['mine'] );

			$user_id = get_current_user_id();
			$term = get_term_by( 'slug', $user_id, TAXONOMY );

			if ( ! ( $term instanceof WP_Term ) ) {
				return $views;
			}

			$count = $term->count;
			$args = [
				'post_type' => $post_type,
				'author'    => $user_id,
			];
			$link = add_query_arg( $args, admin_url( 'edit.php' ) );
			$text = sprintf(
				/* translators: %s: Number of posts. */
				_nx(
					'Mine <span class="count">(%s)</span>',
					'Mine <span class="count">(%s)</span>',
					$count,
					'posts',
					'authorship'
				),
				number_format_i18n( $count )
			);
			$mine = sprintf(
				'<a href="%1$s">%2$s</a>',
				$link,
				$text
			);
			$new_views = [];

			foreach ( $views as $key => $value ) {
				$new_views[ $key ] = $value;

				if ( 'all' === $key ) {
					// Always insert the 'Mine' view after the 'All' view.
					$new_views['mine'] = $mine;
				}
			}

			return $new_views;
		} );
	}, $post_types );
}

/**
 * Fires after block assets have been enqueued for the editing interface.
 */
function enqueue_assets() : void {
	/** @var \WP_Post */
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
 * @param \WP_Post $post The post being edited.
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

/**
 * Fires after the query variable object is created, but before the actual query is run.
 *
 * This is used to override author-related query vars with a corresponding taxonomy query and
 * then add a second filter that resets the vars after the query has run.
 *
 * @param \WP_Query $query The \WP_Query instance.
 */
function action_pre_get_posts( WP_Query $query ) : void {
	$post_type = $query->get( 'post_type' );

	if ( empty( $post_type ) ) {
		// @TODO this needs more work so it matches the behaviour of the internals of `WP_Query`.
		$post_type = 'post';
	}

	if ( array_diff( (array) $post_type, get_post_types_by_support( 'author' ) ) ) {
		// If _any_ of the requested post types don't support `author`, let the default query run.
		// @TODO I don't think anything can be done about a query for multiple post types where one or
		// more support `author` and one or more don't.
		return;
	}

	$stored_values = [];

	$concerns = [
		'author_name',
		'author',
	];

	// Record the original values of concerned query vars and remove them from the query.
	foreach ( $concerns as $concern ) {
		$value = $query->get( $concern );
		if ( '' !== $value ) {
			$stored_values[ $concern ] = $value;
			$query->set( $concern, '' );
		}
	}

	// None of the set query vars concern us? Then we have nothing more to do.
	if ( empty( $stored_values ) ) {
		return;
	}

	$user_id = 0;

	// Get a user ID from either `author` or `author_name`. The ID doesn't have to be valid
	// as \WP_Query will handle the validation before constructing its query.
	if ( ! empty( $stored_values['author'] ) ) {
		$user_id = (int) $stored_values['author'];
	} elseif ( ! empty( $stored_values['author_name'] ) ) {
		$user = get_user_by( 'slug', $stored_values['author_name'] );

		if ( $user ) {
			$user_id = $user->ID;
		}
	}

	$tax_query = $query->get( 'tax_query' );

	// Record the value of an existing tax query, if there is one.
	$stored_values['tax_query'] = $tax_query;

	if ( empty( $tax_query ) ) {
		$tax_query = [];
	}

	// Add a corresponding tax query that queries for posts with terms with a slug matching the requested user ID.
	$tax_query[] = [
		'taxonomy' => TAXONOMY,
		'terms'    => $user_id,
		'field'    => 'slug',
	];

	$query->set( 'tax_query', $tax_query );

	/**
	 * Filters the posts array before the query takes place.
	 *
	 * This allows the query vars to be reset to their original values.
	 *
	 * @param \WP_Post[]|null $posts Array of post objects. Passed by reference.
	 * @param \WP_Query       $query The \WP_Query instance.
	 */
	add_filter( 'posts_pre_query', function( ?array $posts, WP_Query $query ) use ( &$stored_values, $user_id ) : ?array {
		if ( empty( $stored_values ) ) {
			return $posts;
		}

		// Reset the query vars to their original values.
		foreach ( $stored_values as $concern => $value ) {
			$query->set( $concern, $value );
		}

		// Specifically set `author` when `author_name` is in use as WP_Query also sets `author` internally.
		if ( ! empty( $stored_values['author_name'] ) ) {
			$query->set( 'author', $user_id );
		}

		// Clear the recorded values so subsequent queries are not affected.
		$stored_values = [];

		return $posts;
	}, 999, 2 );
}

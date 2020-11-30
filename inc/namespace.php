<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Post;
use WP_Term;
use WP_User;

const TAXONOMY = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap() : void {
	// Actions.
	add_action( 'init', __NAMESPACE__ . '\\init_taxonomy', 99 );
	add_action( 'wp_insert_post', __NAMESPACE__ . '\\action_wp_insert_post', 10, 3 );

	// Filters.
	add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\filter_wp_insert_post_data', 10, 3 );
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

	if ( isset( $authorship_postarr['tax_input'] ) && ! empty( $authorship_postarr['tax_input']['authorship'] ) ) {
		return;
	}

	if ( isset( $authorship_postarr['authorship'] ) ) {
		$authors = $authorship_postarr['authorship'];
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
 * Gets the user objects for the authors of the given post.
 *
 * @param WP_Post $post The post object.
 * @return WP_User[] Array of user objects.
 */
function get_authors( WP_Post $post ) : array {
	/**
	 * Term objects.
	 *
	 * @var WP_Term[] $authors
	 */
	$authors = wp_get_post_terms( $post->ID, TAXONOMY );

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
		'orderby' => 'ID',
		'order'   => 'ASC',
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
		'orderby' => 'ID',
		'order'   => 'ASC',
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
 * Registers the taxonomy that creates a connection between posts and users.
 */
function init_taxonomy() : void {
	$post_types = get_post_types_by_support( 'author' );

	register_taxonomy(
		TAXONOMY,
		$post_types,
		[
			'hierarchical'      => false,
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

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

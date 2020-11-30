<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

const TAXONOMY = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap() : void {
	// Actions.
	add_action( 'init', __NAMESPACE__ . '\\init_taxonomy', 99 );
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

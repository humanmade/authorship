<?php
/**
 * Taxonomy-related functionality for Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Term;

const TAXONOMY = 'authorship';

/**
 * Registers the taxonomy that creates a connection between posts and users.
 */
function init_taxonomy() : void {
	$post_types = get_supported_post_types();

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

	$user_id = get_current_user_id();

	$term = get_term_by( 'slug', $user_id, TAXONOMY );
	if ( $term instanceof WP_Term ) {
		$count = $term->count;

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
	} else {
		$text = '';
	}

	foreach ( $post_types as $post_type ) {
		/**
		 * Filters the list of available list table views.
		 *
		 * @param string[] $views An array of available list table views.
		 * @return string[] An array of available list table views.
		 */
		add_filter( "views_edit-{$post_type}", function( array $views ) use ( $post_type, $text, $user_id ) : array {
			if ( ! $text ) {
				unset( $views['mine'] );

				return $views;
			}

			$args = [
				'post_type' => $post_type,
				'author'    => $user_id,
			];
			$link = add_query_arg( $args, admin_url( 'edit.php' ) );

			$views['mine'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				$link,
				$text
			);

			return $views;
		} );
	}//end foreach
}

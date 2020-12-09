<?php
/**
 * Template functions for Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use WP_Post;
use WP_Term;

/**
 * Gets the user objects for the authors of the given post.
 *
 * @param \WP_Post $post The post object.
 * @return \WP_User[] Array of user objects.
 */
function get_authors( WP_Post $post ) : array {
	if ( ! post_type_supports( $post->post_type, 'author' ) ) {
		return [];
	}

	/** @var \WP_Term[] */
	$authors = wp_get_post_terms( $post->ID, TAXONOMY );

	if ( empty( $authors ) ) {
		return [];
	}

	/** @var \WP_User[] */
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
 * @param \WP_Post $post    The post object.
 * @param int[]    $authors Array of user IDs.
 * @throws \Exception If any of the users do not exist.
 * @return \WP_User[] Array of user objects.
 */
function set_authors( WP_Post $post, array $authors ) : array {
	if ( ! post_type_supports( $post->post_type, 'author' ) ) {
		throw new \Exception( __( 'This post type does not support authorship.', 'authorship' ) );
	}

	/** @var int[] $authors */
	$authors = array_filter( array_map( 'intval', $authors ) );

	/** @var \WP_User[] */
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

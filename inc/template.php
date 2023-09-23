<?php
/**
 * Template functions for Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use Exception;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Returns the user IDs for the attributed author(s) of the given post.
 *
 * @param WP_Post $post The post object.
 * @return int[] Array of user IDs.
 */
function get_author_ids( WP_Post $post ) : array {
	if ( ! is_post_type_supported( $post->post_type ) ) {
		if ( post_type_supports( $post->post_type, 'author' ) ) {
			return [ intval( $post->post_author ) ];
		}

		return [];
	}

	$authors = wp_get_post_terms( $post->ID, TAXONOMY );
	if ( is_wp_error( $authors ) ) {
		return [];
	}

	return array_map( function ( WP_Term $term ) : int {
		return intval( $term->slug );
	}, $authors );
}

/**
 * Returns the user objects for the attributed author(s) of the given post.
 *
 * @param WP_Post $post The post object.
 * @return WP_User[] Array of user objects.
 */
function get_authors( WP_Post $post ) : array {
	$author_ids = get_author_ids( $post );
	if ( empty( $author_ids ) ) {
		return [];
	}

	/** @var WP_User[] */
	$users = get_users( [
		// Check all sites.
		'blog_id' => 0,
		'include' => $author_ids,
		'orderby' => 'include',
	] );

	return $users;
}

/**
 * Returns a comma-separated list of the names of the attributed author(s) of the given post.
 *
 * Example:
 *
 *     John Lennon, Paul McCartney, George Harrison, Ringo Starr
 *
 * @param WP_Post $post The post object.
 * @return string List of the names of the authors.
 */
function get_author_names( WP_Post $post ) : string {
	$authors = get_authors( $post );

	return implode( ', ', array_column( $authors, 'display_name' ) );
}

/**
 * Returns a sentence stating the names of the attributed author(s) of the given post, localised
 * to the current language.
 *
 * Example:
 *
 *     Mick Jagger, Keith Richards, Charlie Watts, and Ronnie Wood
 *
 * @param WP_Post $post The post object.
 * @return string List of the names of the authors.
 */
function get_author_names_sentence( WP_Post $post ) : string {
	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		return '';
	}

	return wp_sprintf(
		'%l',
		array_column( $authors, 'display_name' )
	);
}

/**
 * Returns an unordered HTML list of the names of the attributed author(s) of the given post,
 * linked to their author archive.
 *
 * Example:
 *
 *     <ul>
 *         <li><a href="/author/annie-lennox/">Annie Lennox</a></li>
 *         <li><a href="/author/dave-stewart/">Dave Stewart</a></li>
 *     </ul>
 *
 * @param WP_Post $post The post object.
 * @return string List of the names of the authors.
 */
function get_author_names_list( WP_Post $post ) : string {
	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		return '';
	}

	$list = array_reduce( $authors, function( string $carry, WP_User $author ) {
		return "{$carry}\n\t" . sprintf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( get_author_posts_url( $author->ID ) ),
			esc_html( $author->display_name )
		);
	}, '' );

	$output = <<<HTML
<ul>{$list}
</ul>
HTML;

	return $output;
}

/**
 * Sets the attributed authors for the given post.
 *
 * @param WP_Post $post    The post object.
 * @param int[]   $authors Array of user IDs.
 * @throws Exception If any of the users do not exist.
 * @return WP_User[] Array of user objects.
 */
function set_authors( WP_Post $post, array $authors ) : array {
	if ( ! is_post_type_supported( $post->post_type ) ) {
		throw new Exception( __( 'This post type does not support authorship.', 'authorship' ) );
	}

	/** @var int[] $authors */
	$authors = array_filter( array_map( 'intval', $authors ) );

	/** @var WP_User[] */
	$users = get_users( [
		// Check all sites.
		'blog_id' => 0,
		'include' => $authors,
		'orderby' => 'include',
	] );

	if ( count( $users ) !== count( $authors ) ) {
		throw new Exception( __( 'One or more user IDs are not valid for this site.', 'authorship' ) );
	}

	// Author IDs must be mapped to strings before passing to `wp_set_post_terms()`.
	$terms = wp_set_post_terms( $post->ID, array_map( 'strval', $authors ), TAXONOMY );

	if ( is_wp_error( $terms ) ) {
		throw new Exception( $terms->get_error_message() );
	}

	return $users;
}

/**
 * Determines if the given user is an attributed author of the given post.
 *
 * @param WP_User $user The user object.
 * @param WP_Post $post The post object.
 * @return bool Whether the user is an attributed author of the post.
 */
function user_is_author( WP_User $user, WP_Post $post ) : bool {
	if ( ! is_post_type_supported( $post->post_type ) ) {
		return ( intval( $post->post_author ) === $user->ID );
	}

	$author_ids = get_author_ids( $post );

	return in_array( $user->ID, $author_ids, true );
}

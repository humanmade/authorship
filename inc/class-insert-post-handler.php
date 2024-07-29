<?php
/**
 * Handler for encapsulating hook callbacks called when inserting posts.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

use Exception;
use WP_Post;

/**
 * Core class for encapsulating hook callbacks called when inserting posts.
 *
 * This is needed because state is passed between the callbacks.
 */
class InsertPostHandler {
	/**
	 * @var array<mixed>
	 */
	private $postarr = [];

	/**
	 * Filters slashed post data just before it is inserted into the database.
	 *
	 * @param mixed[] $data                An array of slashed, sanitized, and processed post data.
	 * @param mixed[] $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param mixed   $unsanitized_postarr An array (or object that implements array access, like a WP_Post) of
	 *                                     slashed yet _unsanitized_ and unprocessed post data as originally passed
	 *                                     to wp_insert_post().
	 * @return mixed[] An array of slashed, sanitized, and processed post data.
	 */
	function filter_wp_insert_post_data( array $data, array $postarr, $unsanitized_postarr ) : array {
		// Make sure the unsanitized post array is actually an array. Core sometimes passes it as a WP_Post object.
		$this->postarr = (array) $unsanitized_postarr;

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
		$unsanitized_postarr = $this->postarr;

		$this->postarr = [];

		if ( isset( $unsanitized_postarr['tax_input'] ) && ! empty( $unsanitized_postarr['tax_input'][ TAXONOMY ] ) ) {
			return;
		}

		$existing_authors = get_authors( $post );

		if ( $update && ! isset( $unsanitized_postarr[ POSTS_PARAM ] ) && $existing_authors ) {
			return;
		}

		if ( isset( $unsanitized_postarr[ POSTS_PARAM ] ) ) {
			$authors = $unsanitized_postarr[ POSTS_PARAM ];
		} else {
			/**
			 * Set the default authorship author. Defaults to the original post author.
			 *
			 * @param array $authors Authors to add to a post on insert if none have been passed. Default to post author.
			 * @param WP_Post $post Post object.
			 */
			$authors = array_filter( apply_filters(
				'authorship_default_author',
				[ isset( $unsanitized_postarr['post_author'] ) ? $unsanitized_postarr['post_author'] : null ],
				$post
			) );
		}

		if ( empty( $authors ) ) {
			return;
		}

		try {
			set_authors( $post, wp_parse_id_list( $authors ) );
		} catch ( Exception $e ) {
			// Nothing at the moment.
		}
	}
}

<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_Term;

use const Authorship\GUEST_ROLE;

/**
 * Authorship utility and migration commands.
 */
class Commands extends WP_CLI_Command {

	/**
	 * Migrates PublishPress Authors data to Authorship.
	 *
	 * Running this creates new Authorship data, but does not remove PPA data
	 * or perform cleanup. If the new Authorship data is not good, delete it
	 * and re-run as PPA's data will still be there.
	 *
	 * Guest authors that aren't mapped to a user will have a user created
	 * with the guest author role and the same name.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run=<dry-run>]
	 * : Perform a test run if true, make changes to the database if false.
	 * ---
	 * default: true
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp authorship migrateppa --dry-run=true
	 *
	 * @when after_wp_load
	 *
	 * @param array $args CLI arguments
	 * @param array $assoc_args CLI arguments
	 */
	function migrateppa( $args, $assoc_args ) {

		WP_CLI::log( 'To perform this migration you may need to activate the publishpress authors plugin' );

		$posts_per_page = 100;
		$paged = 1;
		$count = 0;

		// If --dry-run is not set, then it will default to true.
		// Must set --dry-run explicitly to false to run this command.
		if ( isset( $assoc_args['dry-run'] ) ) {
			// Passing `--dry-run=false` to the command leads to the `false`
			// value being set to string `'false'`, but casting `'false'`
			// to bool produces `true`. Thus the special handling.
			if ( 'false' === $assoc_args['dry-run'] ) {
				$dry_run = false;
			} else {
				$dry_run = (bool) $assoc_args['dry-run'];
			}
		} else {
			$dry_run = true;
		}

		do {
			$posts = get_posts( [
				'posts_per_page'   => $posts_per_page,
				'paged'            => $paged,
				'post_status'      => 'any',
				'post_type'        => 'post',
				'suppress_filters' => 'false',
			] );

			// Exit early if there are no more posts to avoid a final sleep call.
			if ( empty( $posts ) ) {
				continue;
			}

			foreach ( $posts as $post ) {
				$authorship_authors = \Authorship\get_authors( $post );

				if ( ! empty( $authorship_authors ) ) {
					// skip posts that already have authorship data.
					continue;
				}

				// check for PPA data.
				$ppa_terms = wp_get_object_terms( $post->ID, [ 'author' ] );

				// if there are no Publish Press Authors data then there's
				// nothing to migrate, skip!
				if ( empty( $ppa_terms ) ) {
					continue;
				}

				/**
				 * We're going to loop through all the Publishpress authors,
				 * and get a list of user IDs to set in Authorship at the end.
				 * If no user exists for the PPA guest author we create one.
				 */
				$new_authors = [];

				foreach ( $ppa_terms as $ppa_author ) {
					// add this user ID to the list of authors.
					$user_id = $this->get_ppa_user_id( $ppa_author, $dry_run );

					// on dry runs the ID might be -1 if a user needed to be created.
					if ( $user_id !== -1 ) {
						$new_authors[] = $user_id;
					}
				}

				if ( ! $dry_run ) {
					\Authorship\set_authors( $post, $new_authors );
				}

				$count++;
			}//end foreach

			// Avoid memory exhaustion issues.
			$this->reset_local_object_cache();

			// Pause for a moment to let the database catch up.
			WP_CLI::line( sprintf( 'Processed %d posts, pausing for a breath...', $count ) );
			sleep( 2 );

			$paged++;
		} while ( count( $posts ) );

		if ( false === $dry_run ) {
			WP_CLI::success( sprintf( '%d posts would have had Authorship data added if this was not a dry run.', $count ) );
		} else {
			WP_CLI::success( sprintf( '%d posts have had Authorship data added.', $count ) );
		}
	}

	/**
	 * Takes a PublishPress Author term and returns a user ID
	 * for Authorship.
	 *
	 * @param WP_Term $ppa_author The term for the PPA author
	 * @param boolean $dry_run If true no users will be created if they are missing
	 *
	 * @return integer a user ID for this term, or -1 if not resolvable
	 */
	private function get_ppa_user_id( WP_Term $ppa_author, bool $dry_run ) : int {
		/**
		 * We need to get the user for Authorship so check if a
		 * user is already mapped in PPA.
		 */
		$ppa_user_id = get_term_meta( $ppa_author->term_id, 'user_id', true );

		// If there is no mapped PPA user then resolve that.
		if ( ! empty( $ppa_user_id ) ) {
			return $ppa_user_id;
		}

		/**
		 * Look for one with the same username, our guest authors
		 * will also use this slug to avoid duplication.
		 *
		 * @var WP_User|false
		 */
		$ppa_user = get_user_by( 'slug', $ppa_author->slug );

		if ( ! empty( $ppa_user ) ) {
			return $ppa_user_id->ID;
		}

		// Don't try to create a user if it's a dry run, return -1 instead.
		if ( $dry_run ) {
			return -1;
		}

		// If we still don't have a user, create a guest author, and
		// use the same approach as the REST API user controller for
		// the parameters.
		$args = [
			'user_login'    => $ppa_author->slug,
			'user_nicename' => $ppa_author->name,
			'user_email'    => '',
			'user_pass'     => wp_generate_password( 24 ),
			'role'          => GUEST_ROLE,
		];
		$ppa_user_id = wp_insert_user( $args );

		// If this fails we want the debug data, so print out the
		// arguments so we can reproduce later.
		if ( is_wp_error( $ppa_user_id ) ) {
			WP_CLI::error( 'Could not create Authorship user with these arguments:' );
			WP_CLI::error( wp_json_encode( $args ) );
			return -1;
		}

		return $ppa_user_id;
	}

	/**
	 * Reset the local WordPress object cache.
	 *
	 * This only cleans the local cache in WP_Object_Cache, without
	 * affecting memcache.
	 *
	 * Taken from VIP Go MU Plugins `vip_reset_local_object_cache`.
	 *
	 * @see https://github.com/Automattic/vip-go-mu-plugins/blob/master/vip-helpers/vip-caching.php#L733-L747
	 */
	private function reset_local_object_cache() : void {
		global $wp_object_cache;

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = [];
		$wp_object_cache->memcache_debug = [];
		$wp_object_cache->cache          = [];

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			// important!
			$wp_object_cache->__remoteset();
		}
	}
}

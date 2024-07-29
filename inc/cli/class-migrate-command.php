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
use WP_Object_Cache;
use WP_Post;
use WP_Term;
use WP_User;

use const Authorship\GUEST_ROLE;

/**
 * Authorship migration commands.
 */
class Migrate_Command extends WP_CLI_Command {

	/**
	 * Set Authorship authors for existing WordPress posts.
	 *
	 * If you enable Authorship on an site that already has content, user archive pages will be broken because posts do not have any authorship authors set.
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
	 * [--overwrite-authors=<overwrite-authors>]
	 * : If true overwrite Authorship data with WP data, if false
	 * skip posts that already have Authorship data.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * [--post-type=<post-type>]
	 * : Post type, or comma separated list of post types.
	 * ---
	 * default: post
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp authorship migrate wp-authors --dry-run=true
	 *
	 * @when after_wp_load
	 * @subcommand wp-authors
	 *
	 * @param array<string> $args CLI arguments
	 * @param array<string> $assoc_args CLI arguments
	 */
	public function wp_authors( $args, $assoc_args ) : void {
		$posts_per_page = 100;
		$paged = 1;
		$count = 0;

		// If --dry-run is not set, then it will default to true.
		// Must set --dry-run explicitly to false to run this command.
		$dry_run = filter_var( $assoc_args['dry-run'] ?? true, FILTER_VALIDATE_BOOLEAN );

		if ( ! $dry_run ) {
			WP_CLI::warning( 'Dry run is disabled, data will be modified.' );
		}

		// If --overwrite-authors is not set, then it will default to false.
		$overwrite = filter_var( $assoc_args['overwrite-authors'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( $overwrite ) {
			WP_CLI::warning( 'Overwriting of previous Authorship data is set to true.' );
		}

		$post_types = explode( ',', $assoc_args['post-type'] );
		WP_CLI::line( sprintf( 'Updating post types: %s', implode( ', ', $post_types ) ) );

		$tax_query = $overwrite ? [] : [
			[
				'taxonomy' => 'authorship',
				'operator' => 'NOT EXISTS',
			],
		];

		do {
			/**
			 * @var array<WP_Post>
			 */
			$posts = get_posts( [
				'posts_per_page'      => $posts_per_page,
				'paged'               => $paged,
				'post_type'           => $post_types,
				'post_status'         => 'any',
				'ignore_sticky_posts' => true,
				'suppress_filters'    => false,
				'tax_query'           => $tax_query,
			] );

			// Exit early if there are no more posts to avoid a final sleep call.
			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$authorship_authors = \Authorship\get_authors( $post );

				if ( ! empty( $authorship_authors ) && ! $overwrite ) {
					// skip posts that already have authorship data.
					WP_CLI::line( 'Skipping post that already has Authorship data' );
					continue;
				}

				// Set post author as Authorship author.
				if ( ! $dry_run ) {
					\Authorship\set_authors( $post, [ intval( $post->post_author ) ] );
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

		if ( true === $dry_run ) {
			WP_CLI::success( sprintf( '%d posts would have had Authorship data added if this was not a dry run.', $count ) );
		} else {
			WP_CLI::success( sprintf( '%d posts have had Authorship data added.', $count ) );
		}
	}

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
	 * [--overwrite-authors=<overwrite-authors>]
	 * : If true overwrite Authorship data with publishpress data, if false
	 * skip posts that already have Authorship data.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp authorship migrate ppa --dry-run=true
	 *
	 * @when after_wp_load
	 *
	 * @param array<string> $args CLI arguments
	 * @param array<string> $assoc_args CLI arguments
	 */
	function ppa( $args, $assoc_args ) : void {
		if ( ! taxonomy_exists( 'author' ) ) {
			// register the `author` taxonomy so that we can query for PPA author terms.
			register_taxonomy( 'author', 'post' );
		}

		$posts_per_page = 100;
		$paged = 1;
		$count = 0;

		// If --dry-run is not set, then it will default to true.
		// Must set --dry-run explicitly to false to run this command.
		$dry_run = filter_var( $assoc_args['dry-run'] ?? true, FILTER_VALIDATE_BOOLEAN );

		if ( ! $dry_run ) {
			WP_CLI::warning( 'Dry run is disabled, data will be modified.' );
		}

		// If --overwrite-authors is not set, then it will default to false.
		$overwrite = filter_var( $assoc_args['overwrite-authors'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( $overwrite ) {
			WP_CLI::warning( 'Overwriting of previous Authorship data is set to true.' );
		}

		do {
			/**
			 * @var array<WP_Post>
			 */
			$posts = get_posts( [
				'posts_per_page'      => $posts_per_page,
				'paged'               => $paged,
				'post_status'         => 'any',
				'ignore_sticky_posts' => true,
				'suppress_filters'    => false,
				'tax_query' => [
					[
						'taxonomy' => 'author',
						'operator' => 'EXISTS',
					],
				],
			] );

			// Exit early if there are no more posts to avoid a final sleep call.
			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$authorship_authors = \Authorship\get_authors( $post );

				if ( ! empty( $authorship_authors ) && ! $overwrite ) {
					// skip posts that already have authorship data.
					WP_CLI::line( 'Skipping post that already has Authorship data' );
					continue;
				}

				// check for PPA data.
				$ppa_terms = wp_get_object_terms( $post->ID, [ 'author' ] );

				// if there are no Publish Press Authors data then there's
				// nothing to migrate, skip!
				if ( empty( $ppa_terms ) ) {
					WP_CLI::line( 'Skipping post with no PPA data' );
					continue;
				}

				// If PPA is deactivated the terms can be an error object.
				// Usually invalid taxonomy, lets catch and report this.
				if ( is_wp_error( $ppa_terms ) ) {
					WP_CLI::error( 'There was an error fetching the PublishPress Author data, is the plugin activated?', false );
					WP_CLI::error( $ppa_terms, false );
					exit( 1 );
				}

				/**
				 * We're going to loop through all the PublishPress authors,
				 * and get a list of user IDs to set in Authorship at the end.
				 * If no user exists for the PPA guest author we create one.
				 */
				$new_authors = [];
				foreach ( $ppa_terms as $ppa_author ) {
					// add this user ID to the list of authors.
					$user_id = $this->get_ppa_user_id( $ppa_author, ! $dry_run );

					// on dry runs the ID might be -1 if a user needed to be created.
					if ( $user_id !== -1 ) {
						$new_authors[] = $user_id;
					}
				}

				if ( empty( $new_authors ) ) {
					WP_CLI::line( 'Skipping post that has no multi-author data' );
					continue;
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

		if ( true === $dry_run ) {
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
	 * @param boolean $create_users If false no users will be created if they are missing
	 *
	 * @return integer a user ID for this term, or -1 if not resolvable
	 */
	private function get_ppa_user_id( WP_Term $ppa_author, bool $create_users = false ) : int {
		/**
		 * We need to get the user for Authorship so check if a
		 * user is already mapped in PPA.
		 */
		$ppa_user_id = get_term_meta( $ppa_author->term_id, 'user_id', true );

		// If there is no mapped PPA user then resolve that.
		if ( ! empty( $ppa_user_id ) ) {
			return intval( $ppa_user_id );
		}

		/**
		 * Look for one with the same username, our guest authors
		 * will also use this slug to avoid duplication.
		 *
		 * @var WP_User|false
		 */
		$ppa_user = get_user_by( 'slug', $ppa_author->slug );

		if ( ! empty( $ppa_user ) ) {
			return $ppa_user->ID;
		}

		// Return -1 unless we're allowed to create users.
		if ( $create_users !== true ) {
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
			WP_CLI::error( 'Could not create Authorship user with these arguments:', false );
			WP_CLI::error( $ppa_user_id, false );
			exit( 1 );
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
		/**
		 * @var WP_Object_Cache
		 */
		global $wp_object_cache;

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		if ( isset( $wp_object_cache->group_ops ) ) {
			$wp_object_cache->group_ops = [];
		}

		if ( isset( $wp_object_cache->memcache_debug ) ) {
			$wp_object_cache->memcache_debug = [];
		}

		if ( isset( $wp_object_cache->cache ) ) {
			$wp_object_cache->cache = [];
		}

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			// important!
			$wp_object_cache->__remoteset();
		}
	}
}

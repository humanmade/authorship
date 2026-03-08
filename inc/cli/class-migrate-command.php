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
	 * [--batch-pause=<batch-pause>]
	 * : Seconds to pause between processed batches.
	 * ---
	 * default: 2
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

		$post_types = $this->get_migration_post_types( $assoc_args );
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

			$processed_in_batch = 0;

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
				$processed_in_batch++;
			}//end foreach

			// Avoid memory exhaustion issues.
			$this->reset_local_object_cache();

			$this->pause_between_batches( $assoc_args, 'wp-authors', $count, $processed_in_batch );

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
	 * [--batch-pause=<batch-pause>]
	 * : Seconds to pause between processed batches.
	 * ---
	 * default: 2
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
			register_taxonomy(
				'author',
				'post',
				[
					'public'    => false,
					'query_var' => false,
					'rewrite'   => false,
				]
			);
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

			$processed_in_batch = 0;

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
					WP_CLI::error(
						sprintf(
							'There was an error fetching the PublishPress Author data, is the plugin activated? (%s)',
							$ppa_terms->get_error_message()
						)
					);
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
				$processed_in_batch++;
			}//end foreach

			// Avoid memory exhaustion issues.
			$this->reset_local_object_cache();

			$this->pause_between_batches( $assoc_args, 'ppa', $count, $processed_in_batch );

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
			if ( is_scalar( $ppa_user_id ) ) {
				return (int) $ppa_user_id;
			}
		}

		/**
		 * Look for an existing user with the same login first.
		 *
		 * PublishPress stores the guest-author slug separately from the linked
		 * WordPress user's nicename, so matching by login avoids duplicate-user
		 * failures when nicename and login diverge.
		 *
		 * @var WP_User|false
		 */
		$ppa_user = get_user_by( 'login', $ppa_author->slug );

		if ( empty( $ppa_user ) ) {
			$ppa_user = get_user_by( 'slug', $ppa_author->slug );
		}

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
			WP_CLI::error(
				sprintf(
					'Could not create Authorship user with these arguments: %s',
					$ppa_user_id->get_error_message()
				)
			);
		}

		return $ppa_user_id;
	}

	/**
	 * Pause between migration batches.
	 *
	 * @param array<string,mixed> $assoc_args CLI assoc args.
	 * @param string              $migration Migration subcommand identifier.
	 * @param int                 $count Number of processed posts so far.
	 * @param int                 $processed_in_batch Number of posts processed in current batch.
	 */
	private function pause_between_batches( array $assoc_args, string $migration, int $count, int $processed_in_batch ) : void {
		if ( $processed_in_batch <= 0 ) {
			return;
		}

		$pause_seconds = $this->get_batch_pause_seconds( $assoc_args, $migration );
		/**
		 * Fires when migration batch pause duration is resolved.
		 *
		 * @param float               $pause_seconds Resolved pause length in seconds.
		 * @param string              $migration Migration subcommand identifier.
		 * @param array<string,mixed> $assoc_args Original CLI assoc args.
		 * @param int                 $count Number of processed posts at pause point.
		 */
		do_action(
			'authorship_migrate_batch_pause_resolved',
			$pause_seconds,
			$migration,
			$assoc_args,
			$count
		);

		if ( $pause_seconds <= 0 ) {
			WP_CLI::line( sprintf( 'Processed %d posts, continuing without pause.', $count ) );
			return;
		}

		WP_CLI::line(
			sprintf(
				'Processed %1$d posts, pausing for %2$s second(s)...',
				$count,
				$pause_seconds
			)
		);

		usleep( (int) round( $pause_seconds * 1000000 ) );
	}

	/**
	 * Resolve pause seconds between migration batches.
	 *
	 * @param array<string,mixed> $assoc_args CLI assoc args.
	 * @param string              $migration Migration subcommand identifier.
	 *
	 * @return float
	 */
	private function get_batch_pause_seconds( array $assoc_args, string $migration ) : float {
		$pause_seconds = 2.0;
		if ( isset( $assoc_args['batch-pause'] ) && is_numeric( $assoc_args['batch-pause'] ) ) {
			$pause_seconds = floatval( $assoc_args['batch-pause'] );
		}
		$pause_seconds = max( 0.0, $pause_seconds );

		/**
		 * Filter the pause duration between migration batches.
		 *
		 * @param float               $pause_seconds Pause length in seconds.
		 * @param string              $migration Migration subcommand identifier.
		 * @param array<string,mixed> $assoc_args Original CLI assoc args.
		 */
		$pause_seconds = apply_filters(
			'authorship_migrate_batch_pause_seconds',
			$pause_seconds,
			$migration,
			$assoc_args
		);

		if ( ! is_numeric( $pause_seconds ) ) {
			return 0.0;
		}

		return max( 0.0, floatval( $pause_seconds ) );
	}

	/**
	 * Resolve and normalize target post types for wp-authors migration.
	 *
	 * @param array<string,mixed> $assoc_args CLI assoc args.
	 *
	 * @return array<int,string>
	 */
	private function get_migration_post_types( array $assoc_args ) : array {
		$post_type_arg = $assoc_args['post-type'] ?? 'post';

		if ( ! is_string( $post_type_arg ) ) {
			return [ 'post' ];
		}

		$post_types = array_values(
			array_filter(
				array_unique(
					array_map(
						'strtolower',
						array_map( 'trim', explode( ',', $post_type_arg ) )
					)
				)
			)
		);

		if ( empty( $post_types ) ) {
			return [ 'post' ];
		}

		if ( in_array( 'any', $post_types, true ) ) {
			return [ 'any' ];
		}

		$registered_post_types = array_values(
			array_filter(
				$post_types,
				'post_type_exists'
			)
		);

		if ( empty( $registered_post_types ) ) {
			return [ 'post' ];
		}

		return $registered_post_types;
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

			$wp_object_cache->cache = [];

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			// important!
			$wp_object_cache->__remoteset();
		}
	}
}

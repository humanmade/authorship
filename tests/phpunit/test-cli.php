<?php
/**
 * Plugin CLI command tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\CLI;
use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

class TestCLI extends TestCase {
	/**
	 * Captured pause-resolution events.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected $pause_events = [];

	public function set_up() {
		parent::set_up();
		require_once dirname( __DIR__, 2 ) . '/inc/cli/namespace.php';
		require_once dirname( __DIR__, 2 ) . '/inc/cli/class-migrate-command.php';
		CLI\bootstrap();
		$this->pause_events = [];
	}

	public function testMigratePostTypePost() : void {
		$factory = self::factory()->post;

		// Post. Owned by editor, attributed to nobody.
		$post1 = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		// Unset author data.
		wp_set_post_terms( $post1->ID, [], TAXONOMY );

		// Check initial authorship data unset.
		$authorship_authors = \Authorship\get_authors( $post1 );
		$this->assertCount( 0, $authorship_authors );

		$command = new CLI\Migrate_Command;
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => 'post', // Note, have to set default values manually.
			'batch-pause' => '0',
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $post1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypePage() : void {
		$factory = self::factory()->post;

		// Page. Owned by editor, attributed to nobody.
		$page1 = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			'post_type' => 'page',
		] );

		// Unset author data.
		wp_set_post_terms( $page1->ID, [], TAXONOMY );

		// Check initial authorship data unset.
		$authorship_authors = \Authorship\get_authors( $page1 );
		$this->assertCount( 0, $authorship_authors );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => 'post,page',
			'batch-pause' => '0',
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $page1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypeDefaultsToPostWhenNotProvided() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$authorship_authors = \Authorship\get_authors( $post );
		$this->assertCount( 0, $authorship_authors );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'batch-pause' => '0',
		] );

		$authorship_authors = \Authorship\get_authors( $post );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypeListIsTrimmedAndEmptyValuesIgnored() : void {
		$factory = self::factory()->post;

		$page = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			'post_type' => 'page',
		] );

		wp_set_post_terms( $page->ID, [], TAXONOMY );

		$authorship_authors = \Authorship\get_authors( $page );
		$this->assertCount( 0, $authorship_authors );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => ' post, page , , ',
			'batch-pause' => '0',
		] );

		$authorship_authors = \Authorship\get_authors( $page );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypeFallsBackToPostWhenUnknownTypeProvided() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$authorship_authors = \Authorship\get_authors( $post );
		$this->assertCount( 0, $authorship_authors );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => 'not-a-real-post-type',
			'batch-pause' => '0',
		] );

		$authorship_authors = \Authorship\get_authors( $post );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypeIncludesRegisteredCustomType() : void {
		$factory = self::factory()->post;
		$post_type = 'book';
		$post_type_preexisting = post_type_exists( $post_type );

		if ( ! $post_type_preexisting ) {
			register_post_type(
				$post_type,
				[
					'public' => true,
					'supports' => [ 'author' ],
				]
			);
			register_taxonomy_for_object_type( TAXONOMY, $post_type );
		}

		try {
			$book = $factory->create_and_get( [
				'post_author' => self::$users['editor']->ID,
				'post_type' => $post_type,
			] );

			wp_set_post_terms( $book->ID, [], TAXONOMY );

			$authorship_authors = \Authorship\get_authors( $book );
			$this->assertCount( 0, $authorship_authors );

			$command = new CLI\Migrate_Command();
			$command->wp_authors( [], [
				'dry-run' => false,
				'post-type' => $post_type,
				'batch-pause' => '0',
			] );

			$authorship_authors = \Authorship\get_authors( $book );
			$this->assertCount( 1, $authorship_authors );
			$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
		} finally {
			if ( ! $post_type_preexisting && post_type_exists( $post_type ) ) {
				unregister_post_type( $post_type );
			}
		}
	}

	public function testMigrateRespectsZeroBatchPause() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$command = new CLI\Migrate_Command();

		$start_time = microtime( true );
		$command->wp_authors( [], [
			'dry-run' => true,
			'post-type' => 'post',
			'batch-pause' => '0',
		] );
		$elapsed = microtime( true ) - $start_time;

		$this->assertLessThan(
			1.5,
			$elapsed,
			'Expected wp authors migration to skip fixed delay when batch-pause is zero.'
		);
	}

	public function testMigratePauseCanBeOverriddenByFilter() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$command = new CLI\Migrate_Command();

		add_filter( 'authorship_migrate_batch_pause_seconds', [ $this, 'disableMigrationPause' ], 10, 3 );

		try {
			$start_time = microtime( true );
			$command->wp_authors( [], [
				'dry-run' => true,
				'post-type' => 'post',
			] );
			$elapsed = microtime( true ) - $start_time;
		} finally {
			remove_filter( 'authorship_migrate_batch_pause_seconds', [ $this, 'disableMigrationPause' ], 10 );
		}

		$this->assertLessThan(
			1.5,
			$elapsed,
			'Expected filter override to disable migration batch pause.'
		);
	}

	public function testMigrateNegativeBatchPauseIsClampedToZero() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$command = new CLI\Migrate_Command();

		$start_time = microtime( true );
		$command->wp_authors( [], [
			'dry-run' => true,
			'post-type' => 'post',
			'batch-pause' => '-5',
		] );
		$elapsed = microtime( true ) - $start_time;

		$this->assertLessThan(
			1.5,
			$elapsed,
			'Expected negative batch-pause values to be clamped to zero pause.'
		);
	}

	/**
	 * Disable migration pause for testing filter overrides.
	 *
	 * @param float               $pause_seconds Current pause value.
	 * @param string              $migration Migration subcommand.
	 * @param array<string,mixed> $assoc_args CLI assoc args.
	 *
	 * @return float
	 */
	public function disableMigrationPause( float $pause_seconds, string $migration, array $assoc_args ) : float {
		$this->assertSame( 'wp-authors', $migration );
		$this->assertArrayHasKey( 'post-type', $assoc_args );

		return 0.0;
	}

	public function testMigratePauseResolutionActionFiresForWpAuthors() : void {
		$factory = self::factory()->post;

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		wp_set_post_terms( $post->ID, [], TAXONOMY );

		$command = new CLI\Migrate_Command();

		add_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10, 4 );

		try {
			$command->wp_authors( [], [
				'dry-run' => true,
				'post-type' => 'post',
				'batch-pause' => '-5',
			] );
		} finally {
			remove_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10 );
		}

		$this->assertNotEmpty( $this->pause_events );
		$this->assertSame( 'wp-authors', $this->pause_events[0]['migration'] );
		$this->assertSame( 0.0, $this->pause_events[0]['pause_seconds'] );
		$this->assertSame( '-5', $this->pause_events[0]['assoc_args']['batch-pause'] );
	}

	public function testMigratePauseResolutionActionFiresForPpa() : void {
		$factory = self::factory()->post;
		$author_taxonomy_preexisting = taxonomy_exists( 'author' );
		$term_id = 0;

		if ( ! $author_taxonomy_preexisting ) {
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

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		$term = wp_insert_term( 'PPA Test Author', 'author', [
			'slug' => 'ppa-test-author',
		] );
		$this->assertIsArray( $term );
		$this->assertArrayHasKey( 'term_id', $term );

		$term_id = (int) $term['term_id'];
		wp_set_object_terms( $post->ID, [ $term_id ], 'author' );
		update_term_meta( $term_id, 'user_id', self::$users['author']->ID );

		$command = new CLI\Migrate_Command();

		add_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10, 4 );

		try {
			$command->ppa( [], [
				'dry-run' => true,
				'overwrite-authors' => true,
				'batch-pause' => '0',
			] );
		} finally {
			remove_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10 );

			if ( $term_id > 0 && taxonomy_exists( 'author' ) ) {
				wp_delete_term( $term_id, 'author' );
			}

			if ( ! $author_taxonomy_preexisting && taxonomy_exists( 'author' ) ) {
				unregister_taxonomy( 'author' );
			}
		}

		$ppa_event = array_filter(
			$this->pause_events,
			function ( array $event ) : bool {
				return $event['migration'] === 'ppa';
			}
		);

		$this->assertNotEmpty( $ppa_event );
		$event = array_values( $ppa_event )[0];
		$this->assertSame( 0.0, $event['pause_seconds'] );
		$this->assertSame( '0', $event['assoc_args']['batch-pause'] );
	}

	public function testMigratePauseResolutionActionFiresPerProcessedBatch() : void {
		$factory = self::factory()->post;

		$baseline_count = count(
			get_posts( [
				'fields' => 'ids',
				'posts_per_page' => -1,
				'post_type' => 'post',
				'post_status' => 'any',
				'ignore_sticky_posts' => true,
				'suppress_filters' => false,
				'tax_query' => [
					[
						'taxonomy' => TAXONOMY,
						'operator' => 'NOT EXISTS',
					],
				],
			] )
		);

		for ( $i = 0; $i < 101; $i++ ) {
			$post = $factory->create_and_get( [
				'post_author' => self::$users['editor']->ID,
			] );
			wp_set_post_terms( $post->ID, [], TAXONOMY );
		}

		$expected_total = $baseline_count + 101;
		$expected_batches = (int) ceil( $expected_total / 100 );

		$command = new CLI\Migrate_Command();

		add_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10, 4 );

		try {
			$command->wp_authors( [], [
				'dry-run' => true,
				'post-type' => 'post',
				'batch-pause' => '0',
			] );
		} finally {
			remove_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10 );
		}

		$events = array_values(
			array_filter(
				$this->pause_events,
				function ( array $event ) : bool {
					return $event['migration'] === 'wp-authors';
				}
			)
		);

		$this->assertCount( $expected_batches, $events );
		$this->assertSame( min( 100, $expected_total ), $events[0]['count'] );
		$this->assertSame( $expected_total, $events[ $expected_batches - 1 ]['count'] );

		foreach ( $events as $event ) {
			$this->assertSame( 0.0, $event['pause_seconds'] );
			$this->assertSame( '0', $event['assoc_args']['batch-pause'] );
		}
	}

	public function testMigratePauseResolutionActionSkipsEmptyPpaBatches() : void {
		$factory = self::factory()->post;
		$author_taxonomy_preexisting = taxonomy_exists( 'author' );
		$term_id = 0;

		if ( ! $author_taxonomy_preexisting ) {
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

		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		$term = wp_insert_term( 'PPA Skip Author', 'author', [
			'slug' => 'ppa-skip-author',
		] );
		$this->assertIsArray( $term );
		$this->assertArrayHasKey( 'term_id', $term );

		$term_id = (int) $term['term_id'];
		wp_set_object_terms( $post->ID, [ $term_id ], 'author' );
		\Authorship\set_authors( $post, [ self::$users['editor']->ID ] );

		$command = new CLI\Migrate_Command();

		add_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10, 4 );

		try {
			$command->ppa( [], [
				'dry-run' => true,
				'batch-pause' => '0',
			] );
		} finally {
			remove_action( 'authorship_migrate_batch_pause_resolved', [ $this, 'capturePauseResolution' ], 10 );

			if ( $term_id > 0 && taxonomy_exists( 'author' ) ) {
				wp_delete_term( $term_id, 'author' );
			}

			if ( ! $author_taxonomy_preexisting && taxonomy_exists( 'author' ) ) {
				unregister_taxonomy( 'author' );
			}
		}

		$ppa_events = array_filter(
			$this->pause_events,
			function ( array $event ) : bool {
				return $event['migration'] === 'ppa';
			}
		);

		$this->assertEmpty( $ppa_events );
	}

	/**
	 * Capture pause-resolution action payloads.
	 *
	 * @param float               $pause_seconds Pause in seconds.
	 * @param string              $migration Migration subcommand identifier.
	 * @param array<string,mixed> $assoc_args CLI assoc args.
	 * @param int                 $count Processed count at pause point.
	 */
	public function capturePauseResolution( float $pause_seconds, string $migration, array $assoc_args, int $count ) : void {
		$this->pause_events[] = [
			'pause_seconds' => $pause_seconds,
			'migration' => $migration,
			'assoc_args' => $assoc_args,
			'count' => $count,
		];
	}
}

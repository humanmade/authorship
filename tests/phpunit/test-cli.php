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
	public function set_up() {
		parent::set_up();
		require_once dirname( __DIR__, 2 ) . '/inc/cli/namespace.php';
		require_once dirname( __DIR__, 2 ) . '/inc/cli/class-migrate-command.php';
		CLI\bootstrap();
	}

	public function testMigrateWpAuthorsPagination() : void {
		$factory = self::factory()->post;
		$posts = [];

		for ( $i = 0; $i < 200; $i++ ) {
			$posts[] = $factory->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				POSTS_PARAM   => [
					self::$users['editor']->ID,
				],
			] );
		}

		$paged_post = $posts[100]; // Any post from second page of results.
		$authorship_authors = \Authorship\get_authors( $paged_post );

		// Asset initial authorship authors set correctly.
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );

		// Migrate, overwriting authorship data with WP Author data.
		$command = new CLI\Migrate_Command;
		$command->wp_authors( [], [
			'dry-run' => false,
			'overwrite' => true,
		] );

		// Verify author data migrated correctly.
		$authorship_authors = \Authorship\get_authors( $paged_post->ID );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['admin']->ID, $authorship_authors[0]->ID );
	}
}

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
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $post1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0] );
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
			'post-type' => 'page',
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $page1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0] );
	}
}

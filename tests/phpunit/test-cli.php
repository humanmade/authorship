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

	public function testMigratePostTypePost(): void {
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

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => 'post', // Note, have to set default values manually.
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $post1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	public function testMigratePostTypePage(): void {
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
		] );

		// Check migration command has correctly set the author.
		$authorship_authors = \Authorship\get_authors( $page1 );
		$this->assertCount( 1, $authorship_authors );
		$this->assertSame( self::$users['editor']->ID, $authorship_authors[0]->ID );
	}

	/**
	 * Creates more posts than the command processes in one batch, all owned by Admin.
	 *
	 * @param int[] $authors Authorship authors to attribute each post to, or an empty array for none.
	 * @return int[] Post IDs.
	 */
	private static function create_batch_of_posts( array $authors ): array {
		$post_ids = self::factory()->post->create_many( 150, [
			'post_author' => self::$users['admin']->ID,
		] );

		foreach ( $post_ids as $post_id ) {
			wp_set_post_terms( $post_id, array_map( 'strval', $authors ), TAXONOMY );
		}

		return $post_ids;
	}

	/**
	 * @param int[] $post_ids
	 * @return int[][] Authorship author IDs for each post.
	 */
	private static function get_author_ids_for_posts( array $post_ids ): array {
		return array_map( function ( int $post_id ): array {
			return \Authorship\get_author_ids( get_post( $post_id ) );
		}, $post_ids );
	}

	public function testMigrateWpAuthorsDryRunProcessesEveryBatchWithoutChanges(): void {
		$post_ids = self::create_batch_of_posts( [] );

		$this->expectOutputRegex( '/Processed 150 posts/' );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => true,
			'post-type' => 'post',
		] );

		$this->assertSame( array_fill( 0, 150, [] ), self::get_author_ids_for_posts( $post_ids ) );
	}

	public function testMigrateWpAuthorsMigratesEveryBatch(): void {
		$post_ids = self::create_batch_of_posts( [] );

		$this->expectOutputRegex( '/Processed 150 posts/' );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'post-type' => 'post',
		] );

		$this->assertSame(
			array_fill( 0, 150, [ self::$users['admin']->ID ] ),
			self::get_author_ids_for_posts( $post_ids )
		);
	}

	public function testMigrateWpAuthorsOverwritesEveryBatch(): void {
		$post_ids = self::create_batch_of_posts( [ self::$users['editor']->ID ] );

		$this->expectOutputRegex( '/Processed 150 posts/' );

		$command = new CLI\Migrate_Command();
		$command->wp_authors( [], [
			'dry-run' => false,
			'overwrite-authors' => true,
			'post-type' => 'post',
		] );

		$this->assertSame(
			array_fill( 0, 150, [ self::$users['admin']->ID ] ),
			self::get_author_ids_for_posts( $post_ids )
		);
	}
}

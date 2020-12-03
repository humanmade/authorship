<?php
/**
 * Author archive tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

class TestArchive extends TestCase {
	public function testAuthorArchiveQueryIsCorrect() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$post1 = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Author, owned by Editor.
		$post2 = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => [
				self::$users['author']->ID,
			],
		] );

		$this->go_to( get_author_posts_url( self::$users['editor']->ID ) );

		/** @var \WP_Query */
		global $wp_query, $authordata;

		$this->assertQueryTrue( 'is_author', 'is_archive' );
		$this->assertTrue( is_author( self::$users['editor']->ID ) );
		$this->assertSame( [ $post1->ID ], wp_list_pluck( $wp_query->posts, 'ID' ) );
		$this->assertSame( self::$users['editor']->ID, $authordata->ID );
	}

	public function testAuthorArchiveTitleIsCorrect() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$this->go_to( get_author_posts_url( self::$users['editor']->ID ) );

		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

		$title = get_the_archive_title();

		$this->assertSame( self::$users['editor']->display_name, $title );
	}
}

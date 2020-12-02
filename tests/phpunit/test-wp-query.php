<?php
/**
 * WP_Query tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

use WP_Query;

class TestWPQuery extends TestCase {
	public function testQueryForAuthorNameReturnsPostsAttributedToAuthor() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$yes1 = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Editor, owned by nobody.
		$yes2 = $factory->create_and_get( [
			POSTS_PARAM => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to and owned by Author.
		$no1 = $factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		// Attributed to Admin, owned by Editor.
		$no2 = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => [
				self::$users['admin']->ID,
			],
		] );

		$args = [
			'author_name' => self::$users['editor']->user_nicename,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		];

		$query = new WP_Query();
		$posts = $query->query( $args );

		$this->assertCount( 2, $posts );
		$this->assertSame( [ $yes1->ID, $yes2->ID ], $posts );
	}

	public function testQueryForAuthorNameQueriedObjectIsRetained() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$args = [
			'post_type'   => 'post',
			'author_name' => self::$users['editor']->user_nicename,
			'fields'      => 'ids',
			'post_status' => 'publish',
		];

		$query = new WP_Query();
		$posts = $query->query( $args );

		$this->assertCount( 1, $posts );
		$this->assertSame( self::$users['editor']->ID, $query->get_queried_object_id() );
		$this->assertInstanceOf( 'WP_User', $query->get_queried_object() );
	}

	public function testQueryForInvalidAuthorNameReturnsNoResults() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$args = [
			'post_type'   => 'post',
			'author_name' => 'thisusernamedoesnotexist',
			'fields'      => 'ids',
			'post_status' => 'publish',
		];

		$query = new WP_Query();
		$posts = $query->query( $args );

		$this->assertCount( 0, $posts );
		$this->assertSame( 0, $query->get_queried_object_id() );
		$this->assertFalse( $query->get_queried_object() );
	}
}

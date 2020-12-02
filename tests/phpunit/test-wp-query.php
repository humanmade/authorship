<?php
/**
 * WP_Query tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use WP_Query;

class TestWPQuery extends TestCase {
	public function testQueryForAuthorReturnsPostsAttributedToAuthor() {
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

		$common_args = [
			'post_type'   => 'post',
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		];

		$test_args = [
			'author_name' => self::$users['editor']->user_nicename,
			'author'      => self::$users['editor']->ID,
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_Query();
			$posts = $query->query( $args );

			$this->assertCount(
				2,
				$posts,
				"Post count for {$test_key} query is incorrect."
			);
			$this->assertSame(
				[ $yes1->ID, $yes2->ID ],
				$posts,
				"Post IDs for {$test_key} query are incorrect."
			);
		}
	}

	public function testQueryForAuthorQueriedObjectIsRetained() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$common_args = [
			'post_type'   => 'post',
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		];

		$test_args = [
			'author_name' => self::$users['editor']->user_nicename,
			'author'      => self::$users['editor']->ID,
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_Query();
			$posts = $query->query( $args );

			$this->assertSame(
				self::$users['editor']->ID,
				$query->get_queried_object_id(),
				"Queried object ID for {$test_key} query is incorrect."
			);
			$this->assertInstanceOf(
				'WP_User',
				$query->get_queried_object(),
				"Queried object for {$test_key} query is incorrect."
			);
		}
	}

	public function testQueryForInvalidAuthorReturnsNoResults() {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$common_args = [
			'post_type'   => 'post',
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		];

		$test_args = [
			'author_name' => 'thisusernamedoesnotexist',
			'author'      => 99999,
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_Query();
			$posts = $query->query( $args );

			$this->assertCount(
				0,
				$posts,
				"Post count for {$test_key} query is incorrect."
			);
			$this->assertFalse(
				$query->get_queried_object(),
				"Queried object for {$test_key} query is incorrect."
			);
		}
	}

	public function testQueryOverridesDoNotAffectPostTypesThatDoNotSupportAuthor() {
		$factory = self::factory()->post;

		register_post_type( 'testing', [
			'public' => true,
		] );
		remove_post_type_support( 'testing', 'author' );

		// Owned by Editor.
		$editor_post = $factory->create_and_get( [
			'post_type'   => 'testing',
			'post_author' => self::$users['editor']->ID,
		] );
		// Owned by Author.
		$author_post = $factory->create_and_get( [
			'post_type'   => 'testing',
			'post_author' => self::$users['author']->ID,
		] );

		$common_args = [
			'post_type'   => 'testing',
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		];

		$test_args = [
			'author_name' => self::$users['author']->user_nicename,
			'author'      => self::$users['author']->ID,
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_Query();
			$posts = $query->query( $args );

			$this->assertCount(
				1,
				$posts,
				"Post count for {$test_key} query is incorrect."
			);
			$this->assertSame(
				[ $author_post->ID ],
				$posts,
				"Post IDs for {$test_key} query are incorrect."
			);
		}
	}
}

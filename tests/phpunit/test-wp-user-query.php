<?php
/**
 * WP_Query tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;

use WP_User_Query;

class TestWPUserQuery extends TestCase {
	public function testQueryForAuthorWithPublishedPostsReturnsAttributedAuthors() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Editor, owned by nobody.
		$factory->create_and_get( [
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Guest, owned by Author.
		$factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users[ GUEST_ROLE ]->ID,
			],
		] );

		// Attributed to Guest, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users[ GUEST_ROLE ]->ID,
			],
		] );

		// Owned by Author.
		$factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
			'post_status' => 'publish',
			'post_type'   => 'test_cpt_no_author',
		] );

		// Owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'publish',
			'post_type'   => 'test_cpt_no_author',
		] );

		$common_args = [
			'fields'  => 'ID',
			'orderby' => 'ID',
			'order'   => 'ASC',
		];

		// Queries for attributed published post types.
		$test_args = [
			'has_published_posts' => [ 'post' ],
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_User_Query( $args );
			$users = (array) $query->get_results();
			$users = array_map( 'absint', $users );

			$this->assertSame(
				[ self::$users['editor']->ID, self::$users[ GUEST_ROLE ]->ID ],
				$users,
				"User IDs for attributed {$test_key} query are incorrect."
			);
		}

		// Queries for non attributed published post types.
		$test_args = [
			'has_published_posts' => [ 'test_cpt_no_author' ],
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_User_Query( $args );
			$users = (array) $query->get_results();
			$users = array_map( 'absint', $users );

			$this->assertSame(
				[ self::$users['admin']->ID, self::$users['author']->ID ],
				$users,
				"User IDs for non attributed {$test_key} query are incorrect."
			);
		}

		// Queries for non attributed published post types.
		$test_args = [
			'has_published_posts' => [ 'post', 'test_cpt_no_author' ],
		];

		foreach ( $test_args as $test_key => $test_value ) {
			$args = array_merge( $common_args, [
				$test_key => $test_value,
			] );

			$query = new WP_User_Query( $args );
			$users = (array) $query->get_results();
			$users = array_map( 'absint', $users );

			$this->assertSame(
				[
					self::$users['admin']->ID,
					self::$users['editor']->ID,
					self::$users['author']->ID,
					self::$users[ GUEST_ROLE ]->ID,
				],
				$users,
				"User IDs for combined attributed and non-attributed {$test_key} query are incorrect."
			);
		}
	}

}

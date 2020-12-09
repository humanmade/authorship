<?php
/**
 * Tests for template functions.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

use function Authorship\user_is_author;

use WP_User;

class TestTemplate extends TestCase {
	/**
	 * @dataProvider dataAuthorsYes
	 * @dataProvider dataAuthorsNo
	 */
	public function testUserIsAuthor( bool $expected, callable $user, ?callable $post_author, callable ...$authorship ) : void {
		$factory = self::factory()->post;

		if ( is_callable( $post_author ) ) {
			/** @var int */
			$post_author = call_user_func( $post_author )->ID;
		}

		/** @var \WP_User */
		$user = call_user_func( $user );

		/** @var \WP_User[] */
		$post_authorship = array_map( 'call_user_func', $authorship );

		/** @var int[] */
		$post_authorship = array_map( function( WP_User $user ) : int {
			return $user->ID;
		}, $post_authorship );

		$post = $factory->create_and_get( [
			'post_author' => $post_author,
			POSTS_PARAM   => $post_authorship,
		] );

		$this->assertSame( $expected, user_is_author( $user, $post ) );
	}

	public function dataAuthorsYes() : array {
		return [
			[
				true,
				function() : WP_User {
					return self::$users['admin'];
				},
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['admin'];
				},
			],
			[
				true,
				function() : WP_User {
					return self::$users['author'];
				},
				null,
				function() : WP_User {
					return self::$users['author'];
				},
			],
			[
				true,
				function() : WP_User {
					return self::$users['editor'];
				},
				null,
				function() : WP_User {
					return self::$users['admin'];
				},
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['author'];
				},
			],
		];
	}

	public function dataAuthorsNo() : array {
		return [
			[
				false,
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['admin'];
				},
			],
			[
				false,
				function() : WP_User {
					return self::$users['author'];
				},
				null,
			],
			[
				false,
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['editor'];
				},
			],
		];
	}
}

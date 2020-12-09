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

	/**
	 * @return mixed[]
	 */
	public function dataAuthorsYes() : array {
		return [
			// Checking Admin, owned by Editor, attributed to Admin:
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
			// Checking Author, owned by nobody, attributed to Author:
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
			// Checking Editor, owned by nobody, attributed to Admin, Editor, and Author:
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

	/**
	 * @return mixed[]
	 */
	public function dataAuthorsNo() : array {
		return [
			// Checking Editor, owned by Editor, attributed to Admin:
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
			// Checking Author, owned by nobody, attributed to nobody:
			[
				false,
				function() : WP_User {
					return self::$users['author'];
				},
				null,
			],
			// Checking Editor, owned by Editor, attributed to nobody (inherits Editor):
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

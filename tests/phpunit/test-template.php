<?php
/**
 * Tests for template functions.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

use function Authorship\get_author_ids;
use function Authorship\get_author_names;
use function Authorship\get_author_names_list;
use function Authorship\get_author_names_sentence;
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

		$args = [];

		if ( $post_author ) {
			$args['post_author'] = $post_author;
		}
		if ( $post_authorship ) {
			$args[ POSTS_PARAM ] = $post_authorship;
		}

		$post = $factory->create_and_get( $args );

		$this->assertSame( $expected, user_is_author( $user, $post ) );
	}

	/**
	 * @dataProvider dataAuthorsYes
	 */
	public function testAuthorIDs( bool $is_author, callable $user, ?callable $post_author, callable ...$authorship ) : void {
		$factory = self::factory()->post;

		if ( is_callable( $post_author ) ) {
			/** @var int */
			$post_author = call_user_func( $post_author )->ID;
		}

		/** @var \WP_User[] */
		$post_authorship = array_map( 'call_user_func', $authorship );

		/** @var int[] */
		$post_authorship = array_map( function( WP_User $user ) : int {
			return $user->ID;
		}, $post_authorship );

		$args = [];
		$expected = [];

		if ( $post_author ) {
			$args['post_author'] = $post_author;
			$expected = [ $post_author ];
		}
		if ( $post_authorship ) {
			$args[ POSTS_PARAM ] = $post_authorship;
			$expected = $post_authorship;
		}

		$post = $factory->create_and_get( $args );

		$this->assertSame( $expected, get_author_ids( $post ) );
	}

	public function testAuthorNames() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, Author, and Admin.
		$post = $factory->create_and_get( [
			POSTS_PARAM   => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users['admin']->ID,
			],
		] );

		$expected = sprintf(
			'%1$s, %2$s, %3$s',
			self::$users['editor']->display_name,
			self::$users['author']->display_name,
			self::$users['admin']->display_name
		);

		$this->assertSame( $expected, get_author_names( $post ) );
	}

	public function testAuthorNamesInSentence() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, Author, and Admin.
		$post = $factory->create_and_get( [
			POSTS_PARAM   => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users['admin']->ID,
			],
		] );

		$expected = sprintf(
			'%1$s, %2$s, and %3$s',
			self::$users['editor']->display_name,
			self::$users['author']->display_name,
			self::$users['admin']->display_name
		);

		$this->assertSame( $expected, get_author_names_sentence( $post ) );
	}

	public function testAuthorNamesInList() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, Author, and Admin.
		$post = $factory->create_and_get( [
			POSTS_PARAM   => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users['admin']->ID,
			],
		] );

		$output = <<<'HTML'
<ul>
	<li>%1$s</li>
	<li>%2$s</li>
	<li>%3$s</li>
</ul>
HTML;

		$expected = sprintf(
			$output,
			self::$users['editor']->display_name,
			self::$users['author']->display_name,
			self::$users['admin']->display_name
		);

		$this->assertSame( $expected, get_author_names_list( $post ) );
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
			// Checking Editor, owned by Editor, attributed to nobody (inherits Editor):
			[
				true,
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['editor'];
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
			// Checking Editor, owned by Admin, attributed to nobody:
			[
				false,
				function() : WP_User {
					return self::$users['editor'];
				},
				function() : WP_User {
					return self::$users['admin'];
				},
			],
		];
	}
}

<?php
/**
 * General post saving tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\get_authors;

class TestPostSaving extends TestCase {
	public function testPostAuthorTermDoesNotGetSavedOnPostTypeThatDoesNotSupportAuthor() : void {
		$factory = self::factory()->post;

		register_post_type( 'testing', [
			'public' => true,
		] );
		remove_post_type_support( 'testing', 'author' );

		// Owned by Editor.
		$post = $factory->create_and_get( [
			'post_type'   => 'testing',
			'post_author' => self::$users['editor']->ID,
		] );

		$terms = wp_get_post_terms( $post->ID, TAXONOMY );
		$authors = get_authors( $post );

		$this->assertCount( 0, $terms );
		$this->assertCount( 0, $authors );
	}

	public function testPostAuthorsAreRetainedWhenUpdatingPostWithNoAuthorParameter() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		wp_update_post( [
			'ID'          => $post->ID,
			'post_status' => 'draft',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['editor']->ID ], $author_ids );
	}

	public function testPostAuthorsAreRetainedWhenUpdatingPostWithPostAuthorParameter() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		wp_update_post( [
			'ID'          => $post->ID,
			'post_author' => self::$users['author']->ID,
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['editor']->ID ], $author_ids );
	}
}

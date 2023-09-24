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
	public function testPostAuthorshipDoesNotGetSavedOnPostTypeThatDoesNotSupportAuthor() : void {
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

		/** @var \WP_Term[] */
		$terms = wp_get_post_terms( $post->ID, TAXONOMY );
		$authors = get_authors( $post );

		$this->assertCount( 0, $terms );
		$this->assertCount( 0, $authors );
	}

	public function testPostAuthorshipIsRetainedWhenUpdatingPostWithNoAuthorshipParameter() : void {
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

	public function testPostAuthorshipIsRetainedWhenUpdatingPostWithPostAuthorParameter() : void {
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

	public function testPostAuthorshipIsSetToAuthorWhenCreatingPost() : void {
		/** @var int */
		$post_id = wp_insert_post( [
			'post_title'  => 'Testing',
			'post_author' => self::$users['author']->ID,
		], true );
		/** @var \WP_Post */
		$post = get_post( $post_id );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['author']->ID ], $author_ids );
	}

	public function testPostAuthorshipIsSetToAuthorWhenUpdatingPostWithNoExistingAuthorship() : void {
		$factory = self::factory()->post;

		// Owned by Author.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		wp_update_post( [
			'ID'         => $post->ID,
			'post_title' => 'Updated Title',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['author']->ID ], $author_ids );
	}

	public function testPostAuthorshipTermNameIsSetToAuthorDisplaynameWhenCreatingPost() : void {
		/** @var int */
		$post_id = wp_insert_post( [
			'post_title'  => 'Testing',
			'post_author' => self::$users['author']->ID,
		], true );
		/** @var \WP_Post */
		$post = get_post( $post_id );

		/** @var \WP_Term[] */
		$author_terms = wp_get_post_terms( $post->ID, TAXONOMY );

		$this->assertEquals( self::$users['author']->display_name, $author_terms[0]->name );
	}

	public function testPostAuthorshipIsSetToEmptyWhenUpdatingPostWithNoExistingAuthorshipAndFiltered() : void {
		$factory = self::factory()->post;

		add_filter( 'authorship_default_author', '__return_empty_array' );

		// Owned by Author.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		wp_update_post( [
			'ID'         => $post->ID,
			'post_title' => 'Updated Title',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertEmpty( $author_ids );

		remove_filter( 'authorship_default_author', '__return_empty_array' );
	}

	public function testMultiplePostInsertionDoesNotCompoundActions() : void {
		global $wp_filter;

		$before = count( $wp_filter['wp_insert_post']->callbacks );

		for ( $i = 0; $i < 3; $i++ ) {
			wp_insert_post( [
				'post_title' => "Testing $i",
			] );
		}

		$after = count( $wp_filter['wp_insert_post']->callbacks );

		$this->assertSame( $before, $after );
	}
}

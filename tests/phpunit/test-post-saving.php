<?php
/**
 * General post saving tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\TAXONOMY;

use function Authorship\get_authors;

class TestPostSaving extends TestCase {
	public function testPostAuthorTermDoesNotGetSavedOnPostTypeThatDoesNotSupportAuthor() {
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
}

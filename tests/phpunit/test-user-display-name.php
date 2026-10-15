<?php
/**
 * Author term name tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_Term;

use function Authorship\get_author_ids;
use function Authorship\set_authors;

use const Authorship\TAXONOMY;

class TestUserDisplayName extends TestCase {
	public function testPostAuthorshipUpdatesTermNameWhenUserDisplayNameIsUpdated(): void {
		$author = self::$users['author'];
		$post = self::factory()->post->create_and_get();
		set_authors( $post, [ $author->ID ] );

		wp_update_user( [
			'ID' => $author->ID,
			'display_name' => 'New Author Name',
		] );

		$term = get_term_by( 'slug', (string) $author->ID, TAXONOMY );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( 'New Author Name', $term->name );
		$this->assertSame( (string) $author->ID, $term->slug );
	}

	public function testTermNameMatchesSavedDisplayNameWithSpecialCharacters(): void {
		$author = self::$users['author'];
		$post = self::factory()->post->create_and_get();
		set_authors( $post, [ $author->ID ] );

		wp_update_user( wp_slash( [
			'ID' => $author->ID,
			'display_name' => 'Tom & Jerry \\ O\'Brien',
		] ) );

		$user = get_userdata( $author->ID );
		$term = get_term_by( 'slug', (string) $author->ID, TAXONOMY );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( $user->display_name, $term->name );
	}

	public function testTermNameIsKeptWhenUserIsUpdatedWithoutDisplayName(): void {
		$author = self::$users['author'];
		$post = self::factory()->post->create_and_get();
		set_authors( $post, [ $author->ID ] );

		$name_before = get_term_by( 'slug', (string) $author->ID, TAXONOMY )->name;

		wp_update_user( [
			'ID' => $author->ID,
			'description' => 'Updated bio',
		] );

		$term = get_term_by( 'slug', (string) $author->ID, TAXONOMY );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( $name_before, $term->name );
	}

	public function testLegacyTermNamedAfterUserIdIsRenamedWhenAuthorIsSet(): void {
		$author = self::$users['author'];

		// Simulate a term created before names were synced.
		remove_filter( 'wp_insert_term_data', 'Authorship\\filter_wp_insert_term_data' );
		wp_insert_term( (string) $author->ID, TAXONOMY, [
			'slug' => (string) $author->ID,
		] );
		add_filter( 'wp_insert_term_data', 'Authorship\\filter_wp_insert_term_data', 10, 3 );

		$term = get_term_by( 'slug', (string) $author->ID, TAXONOMY );
		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( (string) $author->ID, $term->name );

		$post = self::factory()->post->create_and_get();
		set_authors( $post, [ $author->ID ] );

		$term = get_term_by( 'slug', (string) $author->ID, TAXONOMY );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( $author->display_name, $term->name );
		$this->assertSame( [ $author->ID ], get_author_ids( $post ) );
	}

	public function testNumericDisplayNameDoesNotHijackAnotherAuthorsTerm(): void {
		$victim = self::factory()->user->create_and_get( [
			'role' => 'author',
		] );
		$attacker = self::$users['editor'];

		// The attacker's term name becomes the victim's user ID.
		wp_update_user( [
			'ID' => $attacker->ID,
			'display_name' => (string) $victim->ID,
		] );
		set_authors( self::factory()->post->create_and_get(), [ $attacker->ID ] );

		$attacker_term = get_term_by( 'slug', (string) $attacker->ID, TAXONOMY );
		$this->assertInstanceOf( WP_Term::class, $attacker_term );
		$this->assertSame( (string) $victim->ID, $attacker_term->name );

		// The victim has no term yet, so one must be created rather than matched by name.
		$post = self::factory()->post->create_and_get();
		set_authors( $post, [ $victim->ID ] );

		$this->assertSame( [ $victim->ID ], get_author_ids( $post ) );
	}
}

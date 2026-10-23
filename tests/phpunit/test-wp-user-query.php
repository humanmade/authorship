<?php
/**
 * WP_User_Query tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_User_Query;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;

class TestWPUserQuery extends TestCase {
	public function setUp(): void {
		parent::setUp();

		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$factory->create( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Editor, owned by nobody.
		$factory->create( [
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Attributed to Guest, owned by Author.
		$factory->create( [
			'post_author' => self::$users['author']->ID,
			'post_status' => 'publish',
			POSTS_PARAM   => [
				self::$users[ GUEST_ROLE ]->ID,
			],
		] );

		// Attributed to Contributor, but not published.
		$factory->create( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'draft',
			POSTS_PARAM   => [
				self::$users['contributor']->ID,
			],
		] );

		// Owned by Author, in a post type that Authorship doesn't support.
		$factory->create( [
			'post_author' => self::$users['author']->ID,
			'post_status' => 'publish',
			'post_type'   => 'test_cpt_no_author',
		] );

		// Owned by Admin, in a post type that Authorship doesn't support.
		$factory->create( [
			'post_author' => self::$users['admin']->ID,
			'post_status' => 'publish',
			'post_type'   => 'test_cpt_no_author',
		] );
	}

	/**
	 * @param bool|string[] $has_published_posts
	 * @return int[]
	 */
	private static function get_user_ids( $has_published_posts ): array {
		$query = new WP_User_Query( [
			'fields'              => 'ID',
			'orderby'             => 'ID',
			'order'               => 'ASC',
			'has_published_posts' => $has_published_posts,
		] );

		return array_map( 'intval', $query->get_results() );
	}

	public function testQueryForSupportedPostTypeReturnsAttributedAuthors(): void {
		$this->assertSame(
			[
				self::$users['editor']->ID,
				self::$users[ GUEST_ROLE ]->ID,
			],
			self::get_user_ids( [ 'post' ] )
		);
	}

	public function testQueryForUnsupportedPostTypeReturnsPostAuthors(): void {
		$this->assertSame(
			[
				self::$users['admin']->ID,
				self::$users['author']->ID,
			],
			self::get_user_ids( [ 'test_cpt_no_author' ] )
		);
	}

	public function testQueryForMixedPostTypesReturnsBoth(): void {
		$this->assertSame(
			[
				self::$users['admin']->ID,
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users[ GUEST_ROLE ]->ID,
			],
			self::get_user_ids( [ 'post', 'test_cpt_no_author' ] )
		);
	}

	public function testQueryForAllPublicPostTypesReturnsBoth(): void {
		$this->assertSame(
			[
				self::$users['admin']->ID,
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users[ GUEST_ROLE ]->ID,
			],
			self::get_user_ids( true )
		);
	}

	public function testTermsFromOtherTaxonomiesAreIgnored(): void {
		$subscriber_id = self::$users['subscriber']->ID;

		// A tag whose slug matches a user ID.
		self::factory()->post->create( [
			'post_status' => 'publish',
			'tags_input'  => [ (string) $subscriber_id ],
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		$this->assertNotContains( $subscriber_id, self::get_user_ids( [ 'post' ] ) );
	}

	public function testAuthorTermNamesAreIgnored(): void {
		$editor_id = self::$users['editor']->ID;
		$subscriber_id = self::$users['subscriber']->ID;

		// Author term names follow the display name, which can look like another user's ID.
		wp_update_user( [
			'ID'           => $editor_id,
			'display_name' => (string) $subscriber_id,
		] );

		$user_ids = self::get_user_ids( [ 'post' ] );

		$this->assertContains( $editor_id, $user_ids );
		$this->assertNotContains( $subscriber_id, $user_ids );
	}
}

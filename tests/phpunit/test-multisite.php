<?php
/**
 * Multisite tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

/**
 * @group ms-required
 */
class TestMultisite extends TestCase {
	/**
	 * Super admin.
	 *
	 * @var \WP_User
	 */
	protected static $super_admin;

	/**
	 * Sub site.
	 *
	 * @var \WP_Site
	 */
	protected static $sub_site;

	/**
	 * Set up class test fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		// Create a super admin user.
		self::$super_admin = $factory->user->create_and_get( [
			'role' => 'administrator',
			'display_name' => 'Super Admin',
			'user_email' => "super-admin.role@example.org",
		] );

		grant_super_admin( self::$super_admin->ID );

		// Create a subsite.
		self::$sub_site = $factory->blog->create_and_get( [
			'domain' => 'example.org',
			'path' => '/subsite',
			'title' => 'Authorship Sub Site',
			'user_id' => self::$users['admin']->ID,
		] );
	}

	public function testSuperAdminWithNoRoleOnSite() {
		// Change site.
		switch_to_blog( self::$sub_site->blog_id );

		// Confirm no role on current site.
		$super_admin = get_user_by( 'ID', self::$super_admin->ID );
		$this->assertEmpty( $super_admin->roles );

		$factory = self::factory()->post;

		// Attributed to Super Admin, owned by Admin.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$super_admin->ID,
			],
		] );

		// Check super admin ID is stored.
		$author_ids = \Authorship\get_author_ids( $post );
		$this->assertSame( [ self::$super_admin->ID ], $author_ids );

		$author_url = get_author_posts_url( self::$super_admin->ID );
		$this->assertTrue( strpos( $author_url, '/subsite/' ) !== false );
		$this->go_to( $author_url );

		/** @var \WP_Query */
		global $wp_query, $authordata;

		$this->assertQueryTrue( 'is_author', 'is_archive' );
		$this->assertTrue( is_author( self::$super_admin->ID ) );
		$this->assertSame( [ $post->ID ], wp_list_pluck( $wp_query->posts, 'ID' ) );
		$this->assertSame( self::$super_admin->ID, $authordata->ID );

		restore_current_blog();
	}

}

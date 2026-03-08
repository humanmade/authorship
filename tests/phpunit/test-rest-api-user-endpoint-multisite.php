<?php
/**
 * Multisite REST API user endpoint tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\Users_Controller;

use WP_Http;
use WP_REST_Request;

/**
 * @group ms-required
 */
class TestRESTAPIUserEndpointMultisite extends RESTAPITestCase {
	/**
	 * Route under test.
	 *
	 * @var string
	 */
	protected static $route = '/' . Users_Controller::_NAMESPACE . '/' . Users_Controller::BASE;

	/**
	 * Sub site.
	 *
	 * @var \WP_Site
	 */
	protected static $sub_site;

	/**
	 * Cross-site author with no role on the sub site.
	 *
	 * @var \WP_User
	 */
	protected static $cross_site_author;

	/**
	 * Set up class test fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$sub_site = $factory->blog->create_and_get( [
			'domain'  => 'rest-users.example.org',
			'path'    => '/rest-users',
			'title'   => 'Authorship Sub Site',
			'user_id' => self::$users['admin']->ID,
		] );

		self::$cross_site_author = $factory->user->create_and_get( [
			'role'         => 'author',
			'user_login'   => 'crosssiteauthor',
			'display_name' => 'Cross Site Author',
			'user_email'   => 'cross-site-author@example.org',
		] );
	}

	public function testIncludeCanReturnCrossSiteAuthorWithoutRoleOnCurrentSite() : void {
		switch_to_blog( self::$sub_site->blog_id );
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		try {
			wp_set_current_user( self::$users['admin']->ID );

			$cross_site_author = get_user_by( 'ID', self::$cross_site_author->ID );
			$this->assertNotFalse( $cross_site_author );
			$this->assertEmpty( $cross_site_author->roles );

			$request = new WP_REST_Request( 'GET', self::$route );
			$request->set_param( 'include', [ self::$cross_site_author->ID ] );
			$request->set_param( 'orderby', 'include' );
			$request->set_param( 'post_type', 'post' );

			$response = self::rest_do_request( $request );
			$data     = $response->get_data();
			$message  = self::get_message( $response );

			$this->assertSame( WP_Http::OK, $response->get_status(), $message );
			$this->assertSame( [ self::$cross_site_author->ID ], wp_list_pluck( $data, 'id' ) );
		} finally {
			restore_current_blog();
		}
	}

	public function testSearchCanReturnCrossSiteAuthorWithoutRoleOnCurrentSite() : void {
		switch_to_blog( self::$sub_site->blog_id );
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		try {
			wp_set_current_user( self::$users['admin']->ID );

			$request = new WP_REST_Request( 'GET', self::$route );
			$request->set_param( 'search', 'Cross Site Author' );
			$request->set_param( 'post_type', 'post' );

			$response = self::rest_do_request( $request );
			$data     = $response->get_data();
			$message  = self::get_message( $response );

			$this->assertSame( WP_Http::OK, $response->get_status(), $message );
			$this->assertContains( self::$cross_site_author->ID, wp_list_pluck( $data, 'id' ) );
		} finally {
			restore_current_blog();
		}
	}
}

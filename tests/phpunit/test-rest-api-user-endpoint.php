<?php
/**
 * REST API user endpoint tests.
 *
 * This endpoint is as extension of the `wp/v2/users` endpoint, therefore its
 * tests take this into account by asserting that various fields are not exposed
 * and various filters are not available, and also by not testing functionality
 * that is natively provided by the WordPress endpoint such as search and sort.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\Users_Controller;
use WP_Http;
use WP_REST_Request;

use const Authorship\GUEST_ROLE;

class TestRESTAPIUserEndpoint extends RESTAPITestCase {
	protected static $route = '/' . Users_Controller::_NAMESPACE . '/' . Users_Controller::BASE;

	public function testGuestAuthorCanBeCreatedWithJustAName() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'Firsty Lasty' );

		$response = self::rest_do_request( $request );
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( [ GUEST_ROLE ], $data['roles'] );
	}

	/**
	 * @dataProvider dataDisallowedFields
	 *
	 * @param string $param
	 */
	public function testFieldCannotBeSpecifiedWhenCreatingGuestAuthor( string $param ) : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'Firsty Lasty' );
		$request->set_param( $param, 'testing' );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status() );
	}

	public function testUserOutputFieldsAreRestricted() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'editor' );

		$response = self::rest_do_request( $request );
		$data = $response->get_data();

		$expected = [
			'id',
			'name',
			'link',
			'slug',
			'avatar_urls',
			'_links',
		];

		$this->assertSame( WP_Http::OK, $response->get_status() );
		$this->assertEqualSets( $expected, array_keys( $data[0] ) );
	}

	/**
	 * @dataProvider dataDisallowedFilters
	 *
	 * @param string $param
	 */
	public function testUsersCannotBeFilteredByParameter( string $param ) : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );
		$request->set_param( $param, 'testing' );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status() );
	}

	public function testContextCannotBeSetToEditWhenListingUsers() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );
		$request->set_param( 'context', 'edit' );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	public function testEndpointRequiresAuthentication() : void {
		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::UNAUTHORIZED, $response->get_status() );
	}

	public function testSearchParameterIsRequiredWhenListingUsers() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$response = self::rest_do_request( $request );

		$this->assertTrue( $response->is_error() );

		/** @var \WP_Error */
		$error = $response->as_error();
		$data = $error->get_error_data();

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertArrayHasKey( 'params', $data );
		$this->assertContains( 'search', $data['params'] );
	}

	/**
	 * @dataProvider dataAllowedOrderby
	 *
	 * @param string $orderby
	 */
	public function testAllowedOrderByParameters( string $orderby ) : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );
		$request->set_param( 'orderby', $orderby );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );
	}

	/**
	 * @dataProvider dataDisallowedOrderby
	 *
	 * @param string $orderby
	 */
	public function testDisallowedOrderByParameters( string $orderby ) : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );
		$request->set_param( 'orderby', $orderby );

		$response = self::rest_do_request( $request );

		$this->assertTrue( $response->is_error() );

		/** @var \WP_Error */
		$error = $response->as_error();
		$data = $error->get_error_data();

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertArrayHasKey( 'params', $data );
		$this->assertArrayHasKey( 'orderby', $data['params'] );
	}

	/**
	 * @return mixed[]
	 */
	public function dataAllowedOrderby() : array {
		return [
			[
				'id',
			],
			[
				'name',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedOrderby() : array {
		return [
			[
				'include',
			],
			[
				'registered_date',
			],
			[
				'slug',
			],
			[
				'include_slugs',
			],
			[
				'email',
			],
			[
				'url',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedFilters() : array {
		return [
			[
				'include',
			],
			[
				'exclude',
			],
			[
				'roles',
			],
			[
				'slug',
			],
			[
				'who',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedFields() : array {
		return [
			[
				'password',
			],
			[
				'roles',
			],
		];
	}
}

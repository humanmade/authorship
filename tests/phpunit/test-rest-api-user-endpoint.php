<?php
/**
 * REST API user endpoint tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\Users_Controller;
use WP_Http;
use WP_REST_Request;

class TestRESTAPIUserEndpoint extends RESTAPITestCase {
	protected static $route = '/' . Users_Controller::_NAMESPACE . '/' . Users_Controller::BASE;

	public function testUsersCannotBeFilteredByRole() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'editor' );
		$request->set_param( 'roles', 'editor' );

		$response = self::rest_do_request( $request );

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status() );
	}
}

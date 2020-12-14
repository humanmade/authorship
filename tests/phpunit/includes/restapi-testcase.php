<?php
/**
 * Base test case for REST API tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Base test case for the plugin.
 */
abstract class RESTAPITestCase extends TestCase {
	public function setUp() {
		parent::setUp();

		rest_get_server();
	}

	public function tearDown() {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	protected static function rest_do_request( WP_REST_Request $request ) : WP_REST_Response {
		$response = rest_do_request( $request );
		$response = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

		return $response;
	}
}
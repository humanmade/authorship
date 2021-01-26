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
 * REST API test class for the plugin.
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

	protected static function get_message( WP_REST_REsponse $response ) : string {
		if ( $response->is_error() ) {
			/** @var \WP_Error $error */
			$error = $response->as_error();
			$message = [];
			foreach ( $error->get_error_codes() as $code ) {
				$message[] = sprintf(
					'%1$s (%2$s)',
					$error->get_error_message( $code ),
					$code
				);
				$data = $error->get_error_data( $code );
				if ( isset( $data['params'] ) ) {
					$message = array_merge( $message, array_values( $data['params'] ) );
				}
			}
			return implode( "\n", $message );
		}

		return '';
	}

	protected static function rest_do_request( WP_REST_Request $request ) : WP_REST_Response {
		$response = rest_do_request( $request );
		$response = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );

		return $response;
	}
}

<?php
/**
 * Base test case for the plugin.
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
abstract class TestCase extends \WP_UnitTestCase {
	/**
	 * Users.
	 *
	 * @var \WP_User[]
	 */
	protected static $users = [];

	/**
	 * Set up class test fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		$roles = [
			'admin'       => 'administrator',
			'editor'      => 'editor',
			'author'      => 'author',
			'contributor' => 'contributor',
			'subscriber'  => 'subscriber',
			'no_role'     => '',
		];

		foreach ( $roles as $name => $role ) {
			self::$users[ $name ] = $factory->user->create_and_get( [
				'role' => $role,
			] );
		}
	}

	public function setUp() {
		parent::setUp();

		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
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

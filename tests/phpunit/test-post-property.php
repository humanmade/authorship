<?php
/**
 * Meta tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_Http;
use WP_REST_Request;

class TestPostProperty extends TestCase {
	/**
	 * Users.
	 *
	 * @var \WP_User[]
	 */
	private static $users = [];

	/**
	 * Set up class test fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
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

	// public function testPropertyIsDeclaredOnRoute() {
	// 	$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );
	// 	$response = rest_do_request( $request );
	// 	$data     = $response->get_data();

	// 	$args = array_filter( $data['endpoints'], function( array $endpoint ) {
	// 		return in_array( 'GET', $endpoint['methods'], true );
	// 	} )[0]['args'];

	// 	$this->assertArrayHasKey( 'authorship', $args );
	// 	$this->assertArrayHasKey( 'authorship', $data['schema']['properties'] );
	// }

	public function testREST_API_Post_Property_Can_Be_Specified_When_Creating() {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['editor']->ID,
			self::$users['author']->ID,
		];
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( 'authorship', $authors );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertArrayHasKey( 'authorship', $data );
		$this->assertSame( $authors, $data['authorship'] );
	}

	public function testREST_API_Post_Property_Only_Accepts_Array() {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( 'authorship', '123' );

		$response = rest_do_request( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	public function testREST_API_Post_Property_Cannot_Be_Specified_When_Creating_As_Author() {
		wp_set_current_user( self::$users['author']->ID );

		$authors = [
			self::$users['editor']->ID,
			self::$users['author']->ID,
		];
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( 'authorship', $authors );

		$response = rest_do_request( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	public function testREST_API_Post_Property_Can_Be_Specified_When_Editing() {
		wp_set_current_user( self::$users['admin']->ID );

		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
		] );

		$request = new WP_REST_Request( 'PUT', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( 'authorship', [
			self::$users['author']->ID,
		] );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( WP_Http::OK, $response->get_status() );
		$this->assertSame( 'Test Post', $data['title']['rendered'] );
		$this->assertArrayHasKey( 'authorship', $data );
	}

	public function testREST_API_Post_Property_Exists() {
		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => self::$users['editor']->ID,
		] );

		$request = new WP_REST_Request( 'GET', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'authorship', $data );
		$this->assertSame( [ self::$users['editor']->ID ], $data['authorship'] );
	}
}

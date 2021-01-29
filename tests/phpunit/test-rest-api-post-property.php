<?php
/**
 * REST API post property tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;
use const Authorship\REST_LINK_ID;
use const Authorship\REST_PARAM;
use const Authorship\REST_REL_LINK_ID;

use WP_Http;
use WP_REST_Request;

class TestRESTAPIPostProperty extends RESTAPITestCase {
	public function testAuthorshipCanBeSpecifiedWhenCreatingPost() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['editor']->ID,
			self::$users['author']->ID,
		];
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( REST_PARAM, $authors );

		$response = self::rest_do_request( $request );
		$data     = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::CREATED, $response->get_status(), $message );
		$this->assertArrayHasKey( REST_PARAM, $data );
		$this->assertSame( $authors, $data[ REST_PARAM ] );
	}

	public function testAuthorshipOnlyAcceptsAnArray() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( REST_PARAM, '123' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
	}

	public function testAuthorshipCannotBeSpecifiedWhenCreatingAsAuthorRole() : void {
		wp_set_current_user( self::$users['author']->ID );

		$authors = [
			self::$users['editor']->ID,
			self::$users['author']->ID,
		];
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( REST_PARAM, $authors );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
	}

	public function testAuthorshipCanBeSpecifiedWhenEditing() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
		] );

		$authors = [
			self::$users['author']->ID,
		];
		$request = new WP_REST_Request( 'PUT', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );
		$request->set_param( 'title', 'Test Post' );
		$request->set_param( REST_PARAM, $authors );

		$response = self::rest_do_request( $request );
		$data     = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		$this->assertArrayHasKey( REST_PARAM, $data );
		$this->assertSame( $authors, $data[ REST_PARAM ] );
	}

	public function testAuthorshipIsRetainedWhenNotSpecifiedWhenEditing() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['author']->ID,
		];
		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			POSTS_PARAM   => $authors,
		] );

		$request = new WP_REST_Request( 'PUT', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );
		$request->set_param( 'title', 'Test Post' );

		$response = self::rest_do_request( $request );
		$data     = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		$this->assertArrayHasKey( REST_PARAM, $data );
		$this->assertSame( $authors, $data[ REST_PARAM ] );
	}

	public function testAuthorshipIsRetainedWhenOnlyPostAuthorIsSpecifiedWhenEditing() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['author']->ID,
		];
		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			POSTS_PARAM   => $authors,
		] );

		$request = new WP_REST_Request( 'PUT', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );
		$request->set_param( 'author', self::$users['editor']->ID );

		$response = self::rest_do_request( $request );
		$data     = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		$this->assertArrayHasKey( REST_PARAM, $data );
		$this->assertSame( $authors, $data[ REST_PARAM ] );
	}

	public function testAuthorshipPropertyExists() : void {
		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => self::$users['editor']->ID,
		] );

		$request = new WP_REST_Request( 'GET', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );

		$response = self::rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( REST_PARAM, $data );
		$this->assertSame( [ self::$users['editor']->ID ], $data[ REST_PARAM ] );
	}

	public function testAuthorshipLinksArePresent() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['author']->ID,
			self::$users['editor']->ID,
		];

		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			POSTS_PARAM   => $authors,
		] );

		$request = new WP_REST_Request( 'GET', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );

		$response = self::rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( REST_LINK_ID, $links );
		$this->assertCount( 2, $links[ REST_LINK_ID ] );
	}

	public function testAuthorshipLinksAreEmbeddable() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$authors = [
			self::$users['author']->ID,
			self::$users['editor']->ID,
		];

		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			POSTS_PARAM   => $authors,
		] );

		$request = new WP_REST_Request( 'GET', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );

		// This is as close as we can get to mocking a `?_embed` request:
		$data = rest_get_server()->response_to_data( self::rest_do_request( $request ), true );
		$embedded = $data['_embedded'];

		$this->assertArrayHasKey( REST_LINK_ID, $embedded );
		$this->assertCount( 2, $embedded[ REST_LINK_ID ] );
		$this->assertSame( self::$users['author']->ID, $embedded[ REST_LINK_ID ][0]['id'] );
		$this->assertSame( self::$users['editor']->ID, $embedded[ REST_LINK_ID ][1]['id'] );
		$this->assertSame( self::$users['author']->display_name, $embedded[ REST_LINK_ID ][0]['name'] );
		$this->assertSame( self::$users['editor']->display_name, $embedded[ REST_LINK_ID ][1]['name'] );
	}

	public function testRelLinksArePresent() : void {
		wp_set_current_user( self::$users['admin']->ID );

		$post = self::factory()->post->create_and_get();

		$request = new WP_REST_Request( 'GET', sprintf(
			'/wp/v2/posts/%d',
			$post->ID
		) );
		$request->set_param( 'context', 'edit' );

		$response = self::rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( REST_REL_LINK_ID, $links );
		$this->assertArrayNotHasKey( 'https://api.w.org/action-assign-author', $links );
	}
}

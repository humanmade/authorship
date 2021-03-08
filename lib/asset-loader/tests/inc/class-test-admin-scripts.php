<?php
/**
 * Test functions in the Admin_Scripts\Admin namespace.
 */

declare( strict_types=1 );

namespace Asset_Loader\Tests;

use Asset_Loader\Admin;
use WP_Mock;

class Test_Admin_Scripts extends Asset_Loader_Test_Case {
	/**
	 * Test maybe_set_ssl_cert_error_handling().
	 *
	 * @dataProvider provide_maybe_setup_ssl_cert_error_handling_cases
	 */
	public function test_maybe_setup_ssl_cert_error_handling( bool $is_admin, string $script_uri, bool $expect_action, string $message ) : void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( $is_admin );
		if ( $expect_action ) {
			WP_Mock::expectActionAdded( 'admin_head', 'Asset_Loader\\Admin\\render_localhost_error_detection_script', 5 );
		} else {
			WP_Mock::expectActionNotAdded( 'admin_head', 'Asset_Loader\\Admin\\render_localhost_error_detection_script' );
		}
		Admin\maybe_setup_ssl_cert_error_handling( $script_uri );

		$this->assertConditionsMet( $message );
	}

	/**
	 * Test cases for maybe_set_ssl_cert_error_handling().
	 */
	public function provide_maybe_setup_ssl_cert_error_handling_cases() : array {
		return [
			'non-admin script' => [
				false,
				'https://localhost:9000/some-script.js',
				false,
				'Should have no effect outside of the admin',
			],
			'non-local script' => [
				true,
				'https://some-non-local-domain.com/some-script.js',
				false,
				'Should have no effect for non-local scripts',
			],
			'non-HTTPS script' => [
				true,
				'http://localhost:9000/some-script.js',
				false,
				'Should have no effect for non-HTTPS scripts',
			],
			// These next two cases intentionally use the same script.
			'first valid script binds actions' => [
				true,
				'https://localhost:9000/some-script.js',
				true,
				'Should set up error handlers for https://localhost scripts',
			],
			'second valid script does not rebind actions' => [
				true,
				'https://localhost:9000/some-script.js',
				false,
				'Should only bind action hooks the first time a matching script is found',
			],
		];
	}

	/**
	 * Test the method used to add an onerror callback to script tags.
	 *
	 * @dataProvider provide_positive_script_filter_cases
	 * @dataProvider provide_negative_script_filter_cases
	 */
	public function test_add_onerror_to_localhost_scripts( string $script_tag, string $src, string $expected_script_tag, string $message ) : void {
		$filtered_tag = Admin\add_onerror_to_localhost_scripts( $script_tag, 'handle does not matter', $src );
		$this->assertEquals( $expected_script_tag, $filtered_tag, $message );
	}

	/**
	 * Test cases for filtering tags with add_onerror_to_localhost_scripts().
	 */
	public function provide_positive_script_filter_cases() : array {
		return [
			'filter localhost URL' => [
				'<script />',
				'https://localhost:8000/script.js',
				'<script onerror="maybeSSLError && maybeSSLError( this );" />',
				'https://localhost:8000 script tag should receive onerror handler',
			],
			'filter localhost URL on different port' => [
				'<script />',
				'https://localhost:9090/script.js',
				'<script onerror="maybeSSLError && maybeSSLError( this );" />',
				'https://localhost:9090 script tag should receive onerror handler',
			],
			'filter URL using home IP' => [
				'<script />',
				'https://127.0.0.1:8000/script.js',
				'<script onerror="maybeSSLError && maybeSSLError( this );" />',
				'https://127.0.0.1:8000 script tag should receive onerror handler',
			],
		];
	}

	/**
	 * Test cases where add_onerror_to_localhost_scripts() should have no effect.
	 */
	public function provide_negative_script_filter_cases() : array {
		return [
			'no filtering of non-HTTPS URIs' => [
				'<script />',
				'http://localhost:8000/script.js',
				'<script />',
				'script tag should not be filtered if script URI is not HTTPS',
			],
			'no filtering of non-localhost URIs' => [
				'<script />',
				'https://example.com/script.js',
				'<script />',
				'script tag should not be filtered if script URI host is not localhost',
			],
		];
	}
}

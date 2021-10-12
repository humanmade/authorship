<?php
/**
 * Base class enabling WP_Mock.
 */

declare( strict_types=1 );

namespace Asset_Loader\Tests;

use WP_Mock;

class Asset_Loader_Test_Case extends WP_Mock\Tools\TestCase {
	public function setUp() : void {
		WP_Mock::setUp();

		// Mock common core utility functions which we use within this plugin, but
		// whose implementations don't really matter to the logic we're testing.

		WP_Mock::userFunction( 'wp_parse_args' )->andReturnUsing(
			function( array $values, array $defaults ) : array {
				return array_merge( $defaults, $values );
			}
		);

		WP_Mock::userFunction( 'trailingslashit' )->andReturnUsing(
			function( string $str ) : string {
				return rtrim( $str, '/\\' ) . '/';
			}
		);
	}

	public function tearDown() : void {
		WP_Mock::tearDown();
	}
}

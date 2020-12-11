<?php
/**
 * Meta tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

class TestPlugin extends TestCase {
	public function testReadmeIsUpToDate() : void {
		$file = dirname( dirname( __DIR__ ) ) . '/README.md';

		if ( ! is_file( $file ) ) {
			$this->fail( 'No readme file present.' );
		}

		$file_contents = file_get_contents( $file );

		preg_match( '|Stable tag:(.*)|i', $file_contents, $stable_tag );

		$stable_version = trim( trim( $stable_tag[1], '*' ) );
		$plugin_data    = get_plugin_data( dirname( dirname( __DIR__ ) ) . '/plugin.php' );

		$this->assertSame( $stable_version, $plugin_data['Version'] );
	}
}

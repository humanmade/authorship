<?php
/**
 * Asset enqueuing tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_HTML_Tag_Processor;

use function Authorship\enqueue_assets_for_post;

use const Authorship\SCRIPT_HANDLE;
use const Authorship\STYLE_HANDLE;

class TestAssets extends TestCase {
	/**
	 * Path to the asset file created for the test, if it didn't exist.
	 *
	 * @var string|null
	 */
	private $created_asset_file = null;

	public function setUp(): void {
		parent::setUp();

		$asset_file = dirname( __DIR__, 2 ) . '/build/index.asset.php';

		if ( ! is_file( $asset_file ) ) {
			wp_mkdir_p( dirname( $asset_file ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $asset_file, "<?php return array( 'dependencies' => array(), 'version' => 'test' );\n" );
			$this->created_asset_file = $asset_file;
		}
	}

	public function tearDown(): void {
		if ( $this->created_asset_file ) {
			wp_delete_file( $this->created_asset_file );
		}

		wp_dequeue_script( SCRIPT_HANDLE );
		wp_deregister_script( SCRIPT_HANDLE );
		wp_dequeue_style( STYLE_HANDLE );
		wp_deregister_style( STYLE_HANDLE );

		parent::tearDown();
	}

	public function testAssetsAreEnqueuedWithoutIncorrectUsage(): void {
		enqueue_assets_for_post();

		$this->assertTrue( wp_script_is( SCRIPT_HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( STYLE_HANDLE, 'enqueued' ) );
		$this->assertSame( 1, wp_scripts()->get_data( SCRIPT_HANDLE, 'group' ) );
	}

	public function testScriptIsDeferred(): void {
		enqueue_assets_for_post();

		$tags = new WP_HTML_Tag_Processor( get_echo( 'wp_print_scripts', [ SCRIPT_HANDLE ] ) );

		$this->assertTrue( $tags->next_tag( [ 'tag_name' => 'script' ] ) );
		$this->assertSame( SCRIPT_HANDLE . '-js', $tags->get_attribute( 'id' ) );
		$this->assertNotNull( $tags->get_attribute( 'defer' ) );
	}
}

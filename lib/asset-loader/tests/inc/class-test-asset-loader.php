<?php
/**
 * Test methods exported by the base Asset_Loader namespace.
 */

declare( strict_types=1 );

namespace Asset_Loader\Tests;

use Asset_Loader;
use WP_Mock;

class Test_Asset_Loader extends Asset_Loader_Test_Case {

	/**
	 * Registry to mock wp_register/enqueue_script.
	 *
	 * @var Mock_Asset_Registry
	 */
	private $scripts;

	/**
	 * Registry to mock wp_register/enqueue_style.
	 *
	 * @var Mock_Asset_Registry
	 */
	private $styles;

	/**
	 * String path to production manifest fixture.
	 *
	 * @var string
	 */
	private $prod_manifest;

	/**
	 * String path to development manifest fixture.
	 *
	 * @var string
	 */
	private $dev_manifest;

	public function setUp() : void {
		parent::setUp();

		$this->prod_manifest = dirname( __DIR__ ) . '/fixtures/prod-asset-manifest.json';
		$this->dev_manifest = dirname( __DIR__ ) . '/fixtures/devserver-asset-manifest.json';

		// Set up mock script & style registries, and mock the behavior of WP's
		// enqueuing and registration functions.

		$this->scripts = new Mock_Asset_Registry();

		WP_Mock::userFunction( 'wp_register_script' )
			->andReturnUsing( [ $this->scripts, 'register' ] );
		WP_Mock::userFunction( 'wp_enqueue_script' )
			->andReturnUsing( [ $this->scripts, 'enqueue' ] );
		WP_Mock::userFunction( 'wp_scripts' )->andReturn( $this->scripts );

		$this->styles = new Mock_Asset_Registry();

		WP_Mock::userFunction( 'wp_register_style' )
			->andReturnUsing( [ $this->styles, 'register' ] );
		WP_Mock::userFunction( 'wp_enqueue_style' )
			->andReturnUsing( [ $this->styles, 'enqueue' ] );

		// Simulate a theme environment.

		WP_Mock::userFunction( 'get_stylesheet_directory' )
			->andReturn( dirname( __DIR__ ) );

		WP_Mock::userFunction( 'get_theme_file_uri' )
			->andReturnUsing( function( string $path ) {
				return 'https://my.theme/uri/' . $path;
			} );

		WP_Mock::userFunction( 'is_admin' )
			->andReturn( false );
	}

	/**
	 * Test is_css() utility method.
	 *
	 * @dataProvider provide_is_css_cases
	 */
	public function test_is_css( $asset_uri, $expected, $message ) {
		$this->assertEquals( $expected, Asset_Loader\is_css( $asset_uri ), $message );
	}

	/**
	 * Test cases for is_css().
	 */
	public function provide_is_css_cases() {
		return [
			'return false for js assets' => [
				'not-a-css-file.js',
				false,
				'Should return false for JS assets',
			],
			'return true for css assets' => [
				'css-file.css',
				true,
				'Should return true for CSS assets',
			],
			'return true for css assets with query strings' => [
				'css-file.css?with-query=params',
				true,
				'Should return true for CSS assets with query parameters',
			],
		];
	}

	/**
	 * Test register_asset() behavior for script assets.
	 *
	 * @dataProvider provide_script_asset_cases
	 */
	public function test_register_script( string $manifest, string $resource, array $options, array $expected ) : void {
		Asset_Loader\register_asset( $this->{$manifest}, $resource, $options );

		$this->assertEquals( $expected, $this->scripts->get_registered( $expected['handle'] ) );
	}

	/**
	 * Test enqueue_asset() behavior for script assets.
	 *
	 * @dataProvider provide_script_asset_cases
	 */
	public function test_enqueue_script( string $manifest, string $resource, array $options, array $expected ) : void {
		Asset_Loader\enqueue_asset( $this->{$manifest}, $resource, $options );

		$this->assertEquals( $expected, $this->scripts->get_registered( $expected['handle'] ) );
		$this->assertEmpty( $this->styles->registered );

		$this->assertEquals( [ $expected['handle'] ], $this->scripts->get_enqueued() );
	}

	/**
	 * Script test cases for register_asset and enqueue_asset.
	 */
	public function provide_script_asset_cases() : array {
		return [
			'production script' => [
				'prod_manifest',
				'editor.js',
				[
					'handle'       => 'custom-handle',
					'dependencies' => [ 'wp-data' ],
				],
				[
					'handle' => 'custom-handle',
					'src'    => 'https://my.theme/uri/fixtures/editor.03bfa96fd1c694ca18b3.js',
					'deps'   => [ 'wp-data' ],
					'ver'    => null,
				],
			],
			'development script' => [
				'dev_manifest',
				'editor.js',
				[
					'handle'       => 'editor-bundle',
					'dependencies' => [ 'wp-data', 'wp-element' ],
				],
				[
					'handle' => 'editor-bundle',
					'src'    => 'https://localhost:9090/build/editor.js',
					'deps'   => [ 'wp-data', 'wp-element' ],
					'ver'    => '499bb147f8e7234d957a47ac983e19e7',
				],
			],
		];
	}

	/**
	 * Test register_asset() behavior for style assets.
	 *
	 * @dataProvider provide_style_asset_cases
	 */
	public function test_register_style( string $manifest, string $resource, array $options, array $expected ) : void {
		Asset_Loader\register_asset( $this->{$manifest}, $resource, $options );

		$this->assertEquals( $expected, $this->styles->get_registered( $expected['handle'] ) );
	}

	/**
	 * Test enqueue_asset() behavior for style assets.
	 *
	 * @dataProvider provide_style_asset_cases
	 */
	public function test_enqueue_style( string $manifest, string $resource, array $options, array $expected ) : void {
		Asset_Loader\enqueue_asset( $this->{$manifest}, $resource, $options );

		$this->assertEquals( $expected, $this->styles->get_registered( $expected['handle'] ) );
		$this->assertEmpty( $this->scripts->registered );

		$this->assertEquals( [ $expected['handle'] ], $this->styles->get_enqueued() );
	}

	/**
	 * Stylesheet test cases for register_asset and enqueue_asset.
	 */
	public function provide_style_asset_cases() : array {
		return [
			'production stylesheet' => [
				'prod_manifest',
				'frontend-styles.css',
				[
					'handle'       => 'frontend-styles',
					'dependencies' => [ 'dependency-style' ],
				],
				[
					'handle' => 'frontend-styles',
					'src'    => 'https://my.theme/uri/fixtures/frontend-styles.96a500e3dd1eb671f25e.css',
					'deps'   => [ 'dependency-style' ],
					'ver'    => null,
				],
			],
			'production stylesheet with no explicit handle' => [
				'prod_manifest',
				'frontend-styles.css',
				[
					'dependencies' => [ 'dependency-style' ],
				],
				[
					'handle' => 'frontend-styles.css',
					'src'    => 'https://my.theme/uri/fixtures/frontend-styles.96a500e3dd1eb671f25e.css',
					'deps'   => [ 'dependency-style' ],
					'ver'    => null,
				],
			],
			'production stylesheet not in manifest' => [
				'prod_manifest',
				'theme.css',
				[],
				[
					'handle' => 'theme.css',
					'src'    => 'https://my.theme/uri/fixtures/theme.css',
					'deps'   => [],
					'ver'    => '2a9dea09d6ed09f7c4ce052b82cc4999',
				],
			],
		];
	}

	public function test_register_dev_stylesheet() : void {
		Asset_Loader\register_asset(
			$this->dev_manifest,
			'frontend-styles.css',
			[
				'handle' => 'frontend-styles',
			]
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => 'https://localhost:9090/build/frontend-styles.js',
				'deps'   => [],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'frontend-styles' )
		);
		$this->assertEquals( [], $this->styles->registered );
	}

	public function test_enqueue_dev_stylesheet() : void {
		Asset_Loader\enqueue_asset(
			$this->dev_manifest,
			'frontend-styles.css',
			[
				'handle' => 'frontend-styles',
			]
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => 'https://localhost:9090/build/frontend-styles.js',
				'deps'   => [],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'frontend-styles' )
		);
		$this->assertEquals( [], $this->styles->registered );

		$this->assertEquals( [ 'frontend-styles' ], $this->scripts->get_enqueued() );
	}

	public function test_register_dev_stylesheet_with_dependencies() : void {
		Asset_Loader\register_asset(
			$this->dev_manifest,
			'frontend-styles.css',
			[
				'handle'       => 'frontend-styles',
				'dependencies' => [ 'dependency-style' ],
			]
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => 'https://localhost:9090/build/frontend-styles.js',
				'deps'   => [],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'frontend-styles' )
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => false,
				'deps'   => [ 'dependency-style' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->styles->get_registered( 'frontend-styles' )
		);
	}

	public function test_enqueue_dev_stylesheet_with_dependencies() : void {
		Asset_Loader\enqueue_asset(
			$this->dev_manifest,
			'frontend-styles.css',
			[
				'handle'       => 'frontend-styles',
				'dependencies' => [ 'dependency-style' ],
			]
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => 'https://localhost:9090/build/frontend-styles.js',
				'deps'   => [],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'frontend-styles' )
		);
		$this->assertEquals(
			[
				'handle' => 'frontend-styles',
				'src'    => false,
				'deps'   => [ 'dependency-style' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->styles->get_registered( 'frontend-styles' )
		);

		$this->assertEquals( [ 'frontend-styles' ], $this->scripts->get_enqueued() );
		$this->assertEquals( [ 'frontend-styles' ], $this->styles->get_enqueued() );
	}

	public function test_register_dev_stylesheet_then_corresponding_dev_script() : void {
		Asset_Loader\register_asset(
			$this->dev_manifest,
			'editor.css',
			[
				'handle'       => 'editor',
				'dependencies' => [ 'style-dependency' ],
			]
		);
		Asset_Loader\register_asset(
			$this->dev_manifest,
			'editor.js',
			[
				'handle'       => 'editor',
				'dependencies' => [ 'script-dependency' ],
			]
		);
		$this->assertEquals(
			[
				'handle' => 'editor',
				'src'    => 'https://localhost:9090/build/editor.js',
				'deps'   => [ 'script-dependency' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'editor' )
		);
		$this->assertEquals(
			[
				'handle' => 'editor',
				'src'    => false,
				'deps'   => [ 'style-dependency' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->styles->get_registered( 'editor' )
		);
	}

	public function test_enqueue_dev_stylesheet_then_corresponding_dev_script() : void {
		Asset_Loader\enqueue_asset(
			$this->dev_manifest,
			'editor.css',
			[
				'handle'       => 'editor',
				'dependencies' => [ 'style-dependency' ],
			]
		);
		Asset_Loader\enqueue_asset(
			$this->dev_manifest,
			'editor.js',
			[
				'handle'       => 'editor',
				'dependencies' => [ 'script-dependency' ],
			]
		);
		$this->assertEquals(
			[
				'handle' => 'editor',
				'src'    => 'https://localhost:9090/build/editor.js',
				'deps'   => [ 'script-dependency' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->scripts->get_registered( 'editor' )
		);
		$this->assertEquals(
			[
				'handle' => 'editor',
				'src'    => false,
				'deps'   => [ 'style-dependency' ],
				'ver'    => '499bb147f8e7234d957a47ac983e19e7',
			],
			$this->styles->get_registered( 'editor' )
		);

		$this->assertEquals( [ 'editor' ], $this->scripts->get_enqueued() );
		$this->assertEquals( [ 'editor' ], $this->styles->get_enqueued() );
	}
}

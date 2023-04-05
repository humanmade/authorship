<?php
/**
 * Test functions in the Asset_Loader\Manifest namespace.
 */

declare( strict_types=1 );

namespace Asset_Loader\Tests;

use Asset_Loader\Manifest;
use WP_Mock;

class Test_Manifest extends Asset_Loader_Test_Case {
	/**
	 * Test get_manifest_resource() function.
	 *
	 * @dataProvider provide_get_manifest_resource_cases
	 */
	public function test_get_manifest_resource( string $manifest_path, string $resource, ?string $expected, string $message ) : void {
		$result = Manifest\get_manifest_resource( $manifest_path, $resource );
		$this->assertEquals( $expected, $result, $message );
	}

	/**
	 * Test cases for get_manifest_resource() utility function.
	 */
	public function provide_get_manifest_resource_cases() : array {
		$dev_manifest = dirname( __DIR__ ) . '/fixtures/devserver-asset-manifest.json';
		$prod_manifest = dirname( __DIR__ ) . '/fixtures/prod-asset-manifest.json';
		return [
			'dev manifest editor JS' => [
				$dev_manifest,
				'editor.js',
				'https://localhost:9090/build/editor.js',
				'editor resource dev URI should be retrieved from manifest',
			],
			'dev manifest frontend JS' => [
				$dev_manifest,
				'frontend-styles.js',
				'https://localhost:9090/build/frontend-styles.js',
				'frontend styles JS bundle dev URI should be retrieved from manifest',
			],
			'dev manifest invalid resource' => [
				$dev_manifest,
				'one-script-to-rule-them-all.js',
				null,
				'resources not included in the dev manifest should return null',
			],
			'prod manifest editor JS' => [
				$prod_manifest,
				'editor.js',
				'editor.03bfa96fd1c694ca18b3.js',
				'production editor JS path should be retrieved from manifest',
			],
			'prod manifest editor CSS' => [
				$prod_manifest,
				'editor.css',
				'editor.cce01a3e310944f3603f.css',
				'production editor CSS path should be retrieved from manifest',
			],
			'prod manifest frontend JS' => [
				$prod_manifest,
				'frontend-styles.js',
				null,
				'production frontend-styles JS bundle should not exist in manifest',
			],
			'prod manifest frontend CSS' => [
				$prod_manifest,
				'frontend-styles.css',
				'frontend-styles.96a500e3dd1eb671f25e.css',
				'production frontend-styles CSS path should be retrieved from manifest',
			],
			'prod manifest invalid resource' => [
				$prod_manifest,
				'one-script-to-rule-them-all.js',
				null,
				'resources not included in the production manifest should return null',
			],
		];
	}

	/**
	 * Test get_version() function.
	 *
	 * @dataProvider provide_get_version_cases
	 */
	public function test_get_version( string $asset_uri, string $manifest_path, ?string $expected, string $message ) : void {
		$version = Manifest\get_version( $asset_uri, $manifest_path );

		$this->assertEquals( $expected, $version, $message );
	}

	/**
	 * Test cases for get_version() utility function.
	 */
	public function provide_get_version_cases() : array {
		return [
			'hashed asset filename' => [
				'main.03bfa96fd1c694ca18b3.js',
				dirname( __DIR__ ) . '/fixtures/prod-asset-manifest.json',
				'03bfa96fd1c694ca18b3',
				'Version should be set to the hash in the filename if asset is determined to contain a hash already',
			],
			'fall back to manifest content hash' => [
				'main.js',
				dirname( __DIR__ ) . '/fixtures/prod-asset-manifest.json',
				'2a9dea09d6ed09f7c4ce052b82cc4999',
				'Version should use MD5 hash of asset manifest file if asset is not hashed already',
			],
			'default to "null" if no version can be derived' => [
				'main.js',
				'good-luck-finding-this-nonexistent-manifest.json',
				null,
				'Version should use MD5 hash of asset manifest file if asset is not hashed already',
			],
		];
	}

	public function test_get_version_in_altis_environment() : void {
		WP_Mock::userFunction( 'Altis\\get_environment_codebase_revision' )
			->andReturn( 'spiffy-altis-deploy-revision' );

		$version = Manifest\get_version( 'unhashed.js', 'another-nonexistent-file.json' );

		$this->assertEquals( 'spiffy-altis-deploy-revision', $version, 'Version should fall back to the deployed Altis revision when available' );
	}

	/**
	 * Test get_active_manifest() function.
	 *
	 * @dataProvider provide_get_active_manifest_cases
	 */
	public function test_get_active_manifest( array $manifest_options, ?string $expected, string $message ) : void {
		$result = Manifest\get_active_manifest( $manifest_options );

		$this->assertEquals( $expected, $result, $message );
	}

	/**
	 * Test cases for get_manifest_resource() utility function.
	 */
	public function provide_get_active_manifest_cases() : array {
		$dev_manifest = dirname( __DIR__ ) . '/fixtures/devserver-asset-manifest.json';
		$prod_manifest = dirname( __DIR__ ) . '/fixtures/prod-asset-manifest.json';
		$invalid_manifest_1 = dirname( __DIR__ ) . '/fixtures/does-not-exist.json';
		$invalid_manifest_2 = dirname( __DIR__ ) . '/fixtures/also-does-not-exist.json';
		return [
			'first manifest exists' => [
				[ $dev_manifest, $prod_manifest, $invalid_manifest_1, $invalid_manifest_2 ],
				$dev_manifest,
				'First valid manifest should be returned',
			],
			'second manifest exists' => [
				[ $invalid_manifest_1, $dev_manifest, $prod_manifest, $invalid_manifest_2 ],
				$dev_manifest,
				'Unreadable paths should be skipped',
			],
			'second manifest exists' => [
				[ $invalid_manifest_1, $invalid_manifest_2, $prod_manifest, $dev_manifest ],
				$prod_manifest,
				'Multiple unreadable paths should be skipped',
			],
			'no manifest exists' => [
				[ $invalid_manifest_1, $invalid_manifest_2 ],
				null,
				'null should be returned if no manifest is readable',
			],
		];
	}
}

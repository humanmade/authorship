<?php
/**
 * Define utility functions for loading & parsing an asset manifest file.
 */

declare( strict_types=1 );

namespace Asset_Loader\Manifest;

use Altis;

/**
 * Return the first readable file from an array of manifest paths.
 *
 * This is a helper which can be used when different manifest files need to be
 * read under different conditions: for example, using a dev server manifest
 * when the dev server is running, and a production manifest otherwise.
 *
 * @param string[] $paths Array of potential paths to manifest files.
 * @return string|null The first readable manifest path, or null.
 */
function get_active_manifest( array $paths ) : ?string {
	foreach ( $paths as $path ) {
		if ( is_readable( $path ) ) {
			return $path;
		}
	}

	return null;
}

/**
 * Attempt to load a manifest at the specified path and parse its contents as JSON.
 *
 * @param string $path The path to the JSON file to load.
 * @return array|null;
 */
function load_asset_manifest( $path ) {
	// Avoid repeatedly opening & decoding the same file.
	static $manifests = [];

	if ( isset( $manifests[ $path ] ) ) {
		return $manifests[ $path ];
	}

	if ( ! is_readable( $path ) ) {
		return null;
	}
	$contents = file_get_contents( $path );
	if ( empty( $contents ) ) {
		return null;
	}

	/**
	 * Filter the contents of the loaded manifest.
	 *
	 * @since 0.6.0
	 *
	 * @param array  $contents The loaded manifest contents.
	 * @param string $path     The path to the JSON file loaded.
	 */
	$manifests[ $path ] = apply_filters( 'asset_loader_manifest_contents', json_decode( $contents, true ), $path );

	return $manifests[ $path ];
}

/**
 * Check a directory for a root or build asset manifest file, and attempt to
 * decode and return the asset list JSON if found.
 *
 * @param string $manifest_path Absolute file system path to a JSON asset manifest.
 * @return array|null;
 */
function get_assets_list( string $manifest_path ) {
	$dev_assets = load_asset_manifest( $manifest_path );
	if ( ! empty( $dev_assets ) ) {
		return array_values( $dev_assets );
	}

	return null;
}

/**
 * Attempt to extract a specific value from an asset manifest file.
 *
 * @param string $manifest_path File system path for an asset manifest JSON file.
 * @param string $asset        Asset to retrieve within the specified manifest.
 *
 * @return string|null
 */
function get_manifest_resource( string $manifest_path, string $asset ) : ?string {
	$dev_assets = load_asset_manifest( $manifest_path );

	return $dev_assets[ $asset ] ?? null;
}

/**
 * Given an asset URI and a manifest file path, attempt to derive a unique
 * version number to use when registering that asset with WordPress.
 *
 * The preferred approach here is for the Webpack build to render output files
 * with a hashed filename: see https://webpack.js.org/guides/caching/
 *
 * @param string $asset_uri     String URI of an asset to be registered.
 * @param string $manifest_path File system path for an asset manifest JSON file.
 *
 * @return string|null A unique revision string, or else null if asset versioning
 * is not possible or is determined not to be needed.
 */
function get_version( string $asset_uri, string $manifest_path ) : ?string {
	// Guess whether the provided asset URI is already uniquely hashed using the
	// heuristic of "contains a 16-character-or-more substring made up of nothing
	// but numbers and the letters a through f", which matches most common hash
	// algorithms (including Webpack's default of MD4) while rarely matching
	// any human-readable naming scheme.
	if ( preg_match( '/[a-f0-9]{16,}/', $asset_uri, $possible_hash ) ) {
		// If the file is already hashed, then use the existing hash as the version string.
		return $possible_hash[0];
	}

	// Next, try hashing the contents of the asset manifest file (if available).
	// We don't hash the asset file itself because we may not know where to find
	// those on disk -- this does mean a new asset file invalidates ALL deployed
	// assets, but that is still preferable to versioning based on the Altis
	// revision constant (our last resort).
	static $manifest_hashes = [];

	if ( isset( $manifest_hashes[ $manifest_path ] ) ) {
		return $manifest_hashes[ $manifest_path ];
	}

	if ( is_readable( $manifest_path ) ) {
		$manifest_hash = md5_file( $manifest_path );
		if ( $manifest_hash ) {
			$manifest_hashes[ $manifest_path ] = $manifest_hash;
			return $manifest_hashes[ $manifest_path ];
		} else {
			// Set "null" to prevent trying to re-hash after hashing has failed once.
			$manifest_hashes[ $manifest_path ] = null;
		}
	}

	// Finally, use the Altis deployment revision when available. This is a
	// heavy-handed solution which will expire uncached assets whenever a new
	// deploy goes out, so we recommend using a hashed asset filename instead.
	if ( function_exists( 'Altis\\get_environment_codebase_revision' ) ) {
		return Altis\get_environment_codebase_revision();
	}

	return null;
}

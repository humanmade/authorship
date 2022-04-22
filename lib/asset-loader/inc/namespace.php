<?php
/**
 * Define the Asset_Loader namespace exposing methods for use in other themes & plugins.
 */

declare( strict_types=1 );

namespace Asset_Loader;

/**
 * Register some or all scripts and styles defined in a manifest file.
 *
 * @deprecated 0.4.0
 * @param string $manifest_path Absolute path to a Webpack asset manifest file.
 * @param array  $options {
 *     @type array    $scripts Script dependencies.
 *     @type function $filter  Filter function to limit which scripts are enqueued.
 *     @type string   $handle  Style/script handle. (Default is last part of directory name.)
 *     @type array    $styles  Style dependencies.
 * }
 * @return array|null An array of registered script and style handles, or null.
 */
function register_assets( $manifest_path, $options = [] ) {
	if ( function_exists( '_doing_it_wrong' ) ) {
		_doing_it_wrong(
			'register_assets',
			'register_assets() is deprecated and will be removed soon. Use register_asset() instead.',
			'0.4.0'
		);
	}

	$defaults = [
		'handle'  => basename( plugin_dir_path( $manifest_path ) ),
		'filter'  => '__return_true',
		'scripts' => [],
		'styles'  => [],
	];

	$options = wp_parse_args( $options, $defaults );

	$assets = Manifest\get_assets_list( $manifest_path );

	if ( empty( $assets ) ) {
		// Trust the theme or pluign to handle its own asset loading.
		return false;
	}

	// Generate a hash of the manifest, for script versioning.
	$manifest_hash = md5_file( $manifest_path );

	// Keep track of whether a CSS file has been encountered.
	$has_css = false;

	$registered = [
		'scripts' => [],
		'styles' => [],
	];

	// There should only be one JS and one CSS file emitted per plugin or theme.
	foreach ( $assets as $asset_uri ) {
		if ( ! $options['filter']( $asset_uri ) ) {
			// Ignore file paths which do not pass the provided filter test.
			continue;
		}

		$is_js    = preg_match( '/\.js$/', $asset_uri );
		$is_css   = preg_match( '/\.css$/', $asset_uri );
		$is_chunk = preg_match( '/\.chunk\./', $asset_uri );

		if ( ( ! $is_js && ! $is_css ) || $is_chunk ) {
			// Assets such as source maps and images are also listed; ignore these.
			continue;
		}

		if ( $is_js ) {
			Admin\maybe_setup_ssl_cert_error_handling( $asset_uri );
			wp_register_script(
				$options['handle'],
				$asset_uri,
				$options['scripts'],
				$manifest_hash,
				true
			);
			$registered['scripts'][] = $options['handle'];
		} elseif ( $is_css ) {
			$has_css = true;
			wp_register_style(
				$options['handle'],
				$asset_uri,
				$options['styles'],
				$manifest_hash
			);
			$registered['styles'][] = $options['handle'];
		}
	}

	// Ensure CSS dependencies are always loaded, even when using CSS-in-JS in
	// development.
	if ( ! $has_css && ! empty( $options['styles'] ) ) {
		wp_register_style(
			$options['handle'],
			null,
			$options['styles']
		);
		$registered['styles'][] = $options['handle'];
	}

	if ( empty( $registered['scripts'] ) && empty( $registered['styles'] ) ) {
		return null;
	}
	return $registered;
}

/**
 * Attempt to register a particular script bundle from a manifest, falling back
 * to wp_register_script when the manifest is not available.
 *
 * The manifest, build_path, and target_bundle options are required.
 *
 * @deprecated 0.4.0
 * @param string $manifest_path Absolute file system path to Webpack asset manifest file.
 * @param string $target_bundle The expected string filename of the bundle to load from the manifest.
 * @param array  $options {
 *     @type string $build_path Absolute file system path to the static asset output folder.
 *                              Optional; defaults to the manifest file's parent folder.
 *     @type string $handle     The handle to use when enqueuing the style/script bundle.
 *                              Optional; defaults to the basename of the build folder's parent folder.
 *     @type array  $scripts    Script dependencies. Optional.
 *     @type array  $styles     Style dependencies. Optional.
 * }
 * @return array|null An array of registered script and style handles, or null.
 */
function autoregister( string $manifest_path, string $target_bundle, array $options = [] ) {
	if ( function_exists( '_doing_it_wrong' ) ) {
		_doing_it_wrong(
			'autoregister',
			'autoregister() is deprecated and will be removed soon. Use register_asset() instead.',
			'0.4.0'
		);
	}

	// Guess that the manifest resides within the build folder if no build path is provided.
	$inferred_build_folder = Paths\containing_folder( $manifest_path );

	// Set up argument defaults and make some informed guesses about the build path and handle.
	$defaults = [
		'build_path' => $inferred_build_folder,
		'handle'     => basename( Paths\containing_folder( $inferred_build_folder ) ),
		'filter'     => '__return_true',
		'scripts'    => [],
		'styles'     => [],
	];

	$options = wp_parse_args( $options, $defaults );

	$registered = register_assets( $manifest_path, [
		'handle'  => $options['handle'],
		'filter'  => $options['filter'] !== $defaults['filter'] ?
			$options['filter'] :
			/**
			 * Default filter function selects only assets matching the provided $target_bundle.
			 */
			function( $script_key ) use ( $target_bundle ) {
				return strpos( $script_key, $target_bundle ) !== false;
			},
		'scripts' => $options['scripts'],
		'styles'  => $options['styles'],
	] );

	$build_path = trailingslashit( $options['build_path'] );

	if ( ! empty( $registered ) ) {
		return $registered;
	}

	// If assets were not auto-registered, attempt to manually register the specified bundle.
	$registered = [
		'scripts' => [],
		'styles' => [],
	];

	$js_bundle = $build_path . $target_bundle;
	// These file naming assumption break down in several instances, such as when
	// using hashed filenames or when naming files .min.js.
	$css_bundle = $build_path . preg_replace( '/\.js$/', '.css', $target_bundle );

	// Production mode. Manually register script bundles.
	if ( is_readable( $js_bundle ) ) {
		wp_register_script(
			$options['handle'],
			Paths\get_file_uri( $js_bundle ),
			$options['scripts'],
			md5_file( $js_bundle ),
			true
		);
		$registered['scripts'][] = $options['handle'];
	}

	if ( is_readable( $css_bundle ) ) {
		wp_register_style(
			$options['handle'],
			Paths\get_file_uri( $css_bundle ),
			$options['styles'],
			md5_file( $css_bundle )
		);
		$registered['styles'][] = $options['handle'];
	}

	if ( empty( $registered['scripts'] ) && empty( $registered['styles'] ) ) {
		return null;
	}

	return $registered;
}

/**
 * Attempt to enqueue a particular script bundle from a manifest, falling back
 * to wp_enqueue_script when the manifest is not available.
 *
 * The manifest, build_path, and target_bundle options are required.
 *
 * @deprecated 0.4.0
 * @param string $manifest_path Absolute file system path to Webpack asset manifest file.
 * @param string $target_bundle The expected string filename of the bundle to load from the manifest.
 * @param array  $options {
 *     @type string $build_path Absolute file system path to the static asset output folder.
 *                              Optional; defaults to the manifest file's parent folder.
 *     @type string $handle     The handle to use when enqueuing the style/script bundle.
 *                              Optional; defaults to the basename of the build folder's parent folder.
 *     @type array  $scripts    Script dependencies. Optional.
 *     @type array  $styles     Style dependencies. Optional.
 * }
 * @return void
 */
function autoenqueue( string $manifest_path, string $target_bundle, array $options = [] ) {
	if ( function_exists( '_doing_it_wrong' ) ) {
		_doing_it_wrong(
			'autoenqueue',
			'autoenqueue() is deprecated and will be removed soon. Use enqueue_asset() instead.',
			'0.4.0'
		);
	}

	$registered = autoregister( $manifest_path, $target_bundle, $options );

	if ( empty( $registered ) ) {
		return;
	}

	foreach ( $registered['scripts'] as $handle ) {
		wp_enqueue_script( $handle );
	}
	foreach ( $registered['styles'] as $handle ) {
		wp_enqueue_style( $handle );
	}
}

/**
 * Helper function to naively check whether or not a given URI is a CSS resource.
 *
 * @param string $uri A URI to test for CSS-ness.
 * @return boolean Whether that URI points to a CSS file.
 */
function is_css( string $uri ) : bool {
	return preg_match( '/\.css(\?.*)?$/', $uri ) === 1;
}

/**
 * Register a script, or update an already-registered script using the provided
 * handle which did not declare dependencies of its own to use the dependencies
 * array passed in with a second registration request.
 *
 * @param string              $handle       Handle at which to register this script.
 * @param string              $asset_uri    URI of the registered script file.
 * @param string[]            $dependencies Array of script dependencies.
 * @param string|boolean|null $version      Optional version string for asset.
 * @param boolean             $in_footer    Whether to load this script in footer.
 * @return string
 */
function _register_or_update_script( string $handle, string $asset_uri, array $dependencies, $version = false, $in_footer = true ) : ?string {
	// Handle the case where a `register_asset( 'foo.css' )` call falls back to
	// enqueue the dev bundle's JS. Since the dependencies provided in that CSS-
	// specific registration call would not apply to the world of scripts, but
	// a script asset would still get registered, we may need to update that new
	// script's registration to reflect an actual list of JS dependencies if we
	// later called `register_asset( 'foo.js' )`.
	if ( ! empty( $dependencies ) ) {
		$existing_scripts = wp_scripts();
		if ( isset( $existing_scripts->registered[ $handle ]->deps ) ) {
			if ( ! empty( $existing_scripts->registered[ $handle ]->deps ) ) {
				// We have dependencies, but so does the already-registered script.
				// This is a weird state, and may be an error in future releases.
				return null;
			}

			$existing_scripts->registered[ $handle ]->deps = $dependencies;

			// Updating those dependencies is assumed to be all that needs to be done.
			return $handle;
		}
	}
	wp_register_script( $handle, $asset_uri, $dependencies, $version, $in_footer );

	return $handle;
}

/**
 * Attempt to register a particular script bundle from a manifest.
 *
 * @param string $manifest_path File system path for an asset manifest JSON file.
 * @param string $target_asset  Asset to retrieve within the specified manifest.
 * @param array  $options {
 *     @type string $handle       Handle to use when enqueuing the asset. Optional.
 *     @type array  $dependencies Script or Style dependencies. Optional.
 * }
 * @return array Array detailing which script and/or style handles got registered.
 */
function register_asset( string $manifest_path, string $target_asset, array $options = [] ) : array {
	$defaults = [
		'dependencies' => [],
		'in-footer' => true,
	];
	$options = wp_parse_args( $options, $defaults );

	// Track whether we are falling back to a JS file because a CSS asset could not be found.
	$is_js_style_fallback = false;

	$manifest_folder = trailingslashit( dirname( $manifest_path ) );

	$asset_uri = Manifest\get_manifest_resource( $manifest_path, $target_asset );

	// If we fail to match a .css asset, try again with .js in case there is a
	// JS wrapper for that asset available (e.g. when using DevServer).
	if ( empty( $asset_uri ) && is_css( $target_asset ) ) {
		$asset_uri = Manifest\get_manifest_resource( $manifest_path, preg_replace( '/\.css$/', '.js', $target_asset ) );
		if ( ! empty( $asset_uri ) ) {
			$is_js_style_fallback = true;
		}
	}

	// If asset is not present in manifest, attempt to resolve the $target_asset
	// relative to the folder containing the manifest file.
	if ( empty( $asset_uri ) ) {
		// TODO: Consider checking is_readable( $manifest_folder . $target_asset )
		// and warning (in console or error log) if it is not present on disk.
		$asset_uri = $target_asset;
	}

	// Reconcile static asset build paths relative to the manifest's directory.
	if ( strpos( $asset_uri, '//' ) === false ) {
		$asset_uri = Paths\get_file_uri( $manifest_folder . $asset_uri );
	}

	// Use the requested asset as the asset handle if no handle was provided.
	$asset_handle = $options['handle'] ?? $target_asset;
	$asset_version = Manifest\get_version( $asset_uri, $manifest_path );

	// Track registered handles so we can enqueue the correct assets later.
	$handles = [];

	if ( is_css( $asset_uri ) ) {
		// Register a normal CSS bundle.
		wp_register_style(
			$asset_handle,
			$asset_uri,
			$options['dependencies'],
			$asset_version
		);
		$handles['style'] = $asset_handle;
	} elseif ( $is_js_style_fallback ) {
		// We're registering a JS bundle when we originally asked for a CSS bundle.
		// Register the JS, but if any dependencies were passed in, also register a
		// dummy style bundle so that those style dependencies still get loaded.
		Admin\maybe_setup_ssl_cert_error_handling( $asset_uri );
		_register_or_update_script(
			$asset_handle,
			$asset_uri,
			[],
			$asset_version,
			true
		);
		$handles['script'] = $asset_handle;
		if ( ! empty( $options['dependencies'] ) ) {
			wp_register_style(
				$asset_handle,
				false,
				$options['dependencies'],
				$asset_version
			);
			$handles['style'] = $asset_handle;
		}
	} else {
		// Register a normal JS bundle.
		Admin\maybe_setup_ssl_cert_error_handling( $asset_uri );
		_register_or_update_script(
			$asset_handle,
			$asset_uri,
			$options['dependencies'],
			$asset_version,
			$options['in-footer']
		);
		$handles['script'] = $asset_handle;
	}

	return $handles;
}

/**
 * Register and immediately enqueue a particular asset within a manifest.
 *
 * @param string $manifest_path File system path for an asset manifest JSON file.
 * @param string $target_asset  Asset to retrieve within the specified manifest.
 * @param array  $options {
 *     @type string $handle       Handle to use when enqueuing the asset. Optional.
 *     @type array  $dependencies Script or Style dependencies. Optional.
 * }
 */
function enqueue_asset( string $manifest_path, string $target_asset, array $options = [] ) : void {
	$registered_handles = register_asset( $manifest_path, $target_asset, $options );

	if ( isset( $registered_handles['script'] ) ) {
		wp_enqueue_script( $registered_handles['script'] );
	}
	if ( isset( $registered_handles['style'] ) ) {
		wp_enqueue_style( $registered_handles['style'] );
	}
}

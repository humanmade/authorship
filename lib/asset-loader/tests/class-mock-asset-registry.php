<?php
/**
 * Helper class used to mock script & style registries underlying wp_enqueue_*.
 */

declare( strict_types=1 );

namespace Asset_Loader\Tests;

class Mock_Asset_Registry {
	/**
	 * Dictionary of registered objects in this registry.
	 *
	 * @var object[]
	 */
	public $registered;

	/**
	 * Dictionary of enqueued assets in this registry.
	 *
	 * @var string[]
	 */
	public $enqueued;

	public function __construct() {
		$this->registered = [];
		$this->enqueued = [];
	}

	/**
	 * Add an item to the registry.
	 *
	 * @param string              $handle       Handle at which to register this script.
	 * @param string|boolean      $asset_uri    URI of the registered script file.
	 * @param string[]            $dependencies Array of script dependencies.
	 * @param string|boolean|null $version      Optional version string for asset.
	 * @param string|boolean      $last_arg     Whether to load this script in footer
	 *                                          (scripts), or media (styles).
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register( string $handle, $asset_uri, array $dependencies, $version = null, $last_arg = false ) : bool {
		$this->registered[ $handle ] = (object) [];
		$this->registered[ $handle ]->handle = $handle;
		$this->registered[ $handle ]->src = $asset_uri;
		$this->registered[ $handle ]->deps = $dependencies;
		$this->registered[ $handle ]->ver = $version;

		return true;
	}

	/**
	 * Enqueue an item in the registry.
	 *
	 * @param string $handle Name of the asset to enqueue. Should be unique.
	 */
	public function enqueue( string $handle ) : void {
		$this->enqueued[ $handle ] = true;
	}

	/**
	 * Get the handles of all enqueued assets.
	 *
	 * @return string[]
	 */
	public function get_enqueued() : array {
		return array_keys( $this->enqueued );
	}

	/**
	 * Return an array of a registered object's properties.
	 *
	 * @param string $handle Handle of registered asset to return.
	 * @return array|null
	 */
	public function get_registered( string $handle ) : ?array {
		if ( isset( $this->registered[ $handle ] ) ) {
			return (array) $this->registered[ $handle ];
		}
		return null;
	}
}

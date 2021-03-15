<?php
/**
 * Asset Loader
 *
 * Manage loading Webpack Dev Server assets.
 *
 * @package   Asset_Loader
 * @author    K Adam White & contributors
 * @license   GPL-2.0+
 * @copyright 2019 K Adam White and Contributors
 *
 * @wordpress-plugin
 * Plugin Name: Asset Loader
 * Plugin URI:  https://github.com/humanmade/asset-loader
 * Description: Utilities to seamlessly consume Webpack-bundled assets in WordPress themes & plugins.
 * Version:     0.5.0
 * Author:      K Adam White
 * Author URI:  http://kadamwhite.com
 * License:     GPL-2.0+
 * License URI: https//github.com/humanmade/asset-loader/tree/master/LICENSE
 */

declare( strict_types=1 );

// phpcs:disable HM.Files.NamespaceDirectoryName
namespace Asset_Loader;

/**
 * Prevent Fatal errors in case multiple individual plugins rely on this package and thus ship it, multiple times.
 * PHP doesn't allow existing functions to be redefined, so we use a namespace constant as "include guard".
 */
if ( defined( __NAMESPACE__ . '\\LOADED' ) ) {
	return;
}

const LOADED = true;

require plugin_dir_path( __FILE__ ) . 'inc/admin.php';
require plugin_dir_path( __FILE__ ) . 'inc/manifest.php';
require plugin_dir_path( __FILE__ ) . 'inc/paths.php';
require plugin_dir_path( __FILE__ ) . 'inc/namespace.php';

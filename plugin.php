<?php
/**
 * Authorship plugin for WordPress
 *
 * @package   authorship
 * @link      https://github.com/humanmade/authorship
 * @copyright 2020 Human Made
 * @license   GPL v3 or later
 *
 * Plugin Name:  Authorship
 * Description:  Authorship plugin for WordPress.
 * Version:      0.2.17
 * Plugin URI:   https://github.com/humanmade/authorship
 * Author:       Human Made, initially funded by Siemens.
 * Author URI:   https://humanmade.com/
 * Text Domain:  authorship
 * Requires PHP: 7.2.0
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

declare( strict_types=1 );

namespace Authorship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load asset-loader if it's not loaded already.
if ( ! defined( 'Asset_Loader\\LOADED' ) ) {
	require_once __DIR__ . '/lib/asset-loader/asset-loader.php';
}

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/taxonomy.php';
require_once __DIR__ . '/inc/class-users-controller.php';
require_once __DIR__ . '/inc/template.php';

if ( is_admin() ) {
	require_once __DIR__ . '/inc/admin.php';
	Admin\bootstrap();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/inc/cli/namespace.php';
	require_once __DIR__ . '/inc/cli/class-migrate-command.php';
	CLI\bootstrap();
}

bootstrap();

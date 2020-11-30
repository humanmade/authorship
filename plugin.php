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
 * Description:  Authorship
 * Version:      0.1.0
 * Plugin URI:   https://github.com/humanmade/authorship
 * Author:       Human Made
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

require_once __DIR__ . '/lib/asset-loader/asset-loader.php';
require_once __DIR__ . '/inc/namespace.php';

bootstrap();

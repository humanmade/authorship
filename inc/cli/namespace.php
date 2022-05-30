<?php
/**
 * Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\CLI;

use WP_CLI;

/**
 * Registers WP CLI commands
 *
 * @return void
 */
function bootstrap() : void {
	// @phpstan-ignore-next-line
	WP_CLI::add_command( 'authorship', __NAMESPACE__ . '\\Commands' );
}

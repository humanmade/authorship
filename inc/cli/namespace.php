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
	WP_CLI::add_command( 'authorship migrate', __NAMESPACE__ . '\\Migrate_Command' );
}

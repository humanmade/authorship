<?php
/**
 * PHP test framework bootstrap
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship;

$_plugin_dir = getcwd();
$_env_dir    = dirname( dirname( __DIR__ ) );

require_once $_plugin_dir . '/vendor/autoload.php';

if ( is_readable( $_env_dir . '/.env' ) ) {
	$dotenv = \Dotenv\Dotenv::create( $_env_dir );
	$dotenv->load();
}

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() use ( $_plugin_dir ) : void {
	require_once $_plugin_dir . '/plugin.php';
} );

require_once $_tests_dir . '/includes/bootstrap.php';
require_once __DIR__ . '/testcase.php';
require_once __DIR__ . '/restapi-testcase.php';

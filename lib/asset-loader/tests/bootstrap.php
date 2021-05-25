<?php
/**
 * Bootstrap our PHPUnit tests.
 */

declare( strict_types=1 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define constants before WP_Mock comes into play.
define( 'WP_CONTENT_DIR', '/root/content/' );

// Now call the bootstrap method of WP Mock.
WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

// Load in namespaces containing code to test.
require_once dirname( __DIR__ ) . '/inc/admin.php';
require_once dirname( __DIR__ ) . '/inc/manifest.php';
require_once dirname( __DIR__ ) . '/inc/namespace.php';
require_once dirname( __DIR__ ) . '/inc/paths.php';

// Load our base test case classes.
require_once __DIR__ . '/class-asset-loader-test-case.php';
require_once __DIR__ . '/class-mock-asset-registry.php';

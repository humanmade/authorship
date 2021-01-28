<?php
/**
 * Base test case for email tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

/**
 * Email test class for the plugin.
 */
abstract class EmailTestCase extends TestCase {
	public function setUp() {
		parent::setUp();
		reset_phpmailer_instance();
	}

	public function tearDown() {
		reset_phpmailer_instance();
		parent::tearDown();
	}
}

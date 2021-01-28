<?php
/**
 * Base test case for email tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use MockPHPMailer;

/**
 * Email test class for the plugin.
 */
abstract class EmailTestCase extends TestCase {
	/**
	 * @var MockPHPMailer
	 */
	protected $mailer = null;

	public function setUp() {
		parent::setUp();
		reset_phpmailer_instance();
		$this->mailer = tests_retrieve_phpmailer_instance();
	}

	public function tearDown() {
		$this->mailer = null;
		reset_phpmailer_instance();
		parent::tearDown();
	}
}

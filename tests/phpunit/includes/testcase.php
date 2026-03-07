<?php
/**
 * Base test case for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;

/**
 * Base test case for the plugin.
 */
abstract class TestCase extends \WP_UnitTestCase {
	/**
	 * Output buffer level captured in setUp().
	 *
	 * @var int
	 */
	private $output_buffer_level = 0;

	/**
	 * Users.
	 *
	 * @var \WP_User[]
	 */
	protected static $users = [];

	/**
	 * Set up class test fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		$roles = [
			'admin'       => 'administrator',
			'editor'      => 'editor',
			'author'      => 'author',
			'contributor' => 'contributor',
			'subscriber'  => 'subscriber',
			GUEST_ROLE    => GUEST_ROLE,
			'no_role'     => '',
		];

		foreach ( $roles as $name => $role ) {
			$display = ( $role ) ? $role : 'none';
			self::$users[ $name ] = $factory->user->create_and_get( [
				'role' => $role,
				'display_name' => $display,
				'user_email' => "{$display}.role@example.org",
			] );
		}
	}

	public function setUp() : void {
		parent::setUp();
		$this->output_buffer_level = ob_get_level();
		ob_start();

		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
	}

	public function tearDown() : void {
		while ( ob_get_level() > $this->output_buffer_level ) {
			ob_end_clean();
		}

		parent::tearDown();
	}
}

<?php
/**
 * Add New User screen tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use function Authorship\Admin\enqueue_user_new_script;

use const Authorship\GUEST_ROLE;

class TestAdminUserNew extends TestCase {
	public function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__, 2 ) . '/inc/admin.php';

		// Start from a fresh registry so inline scripts added by other tests don't leak in.
		$GLOBALS['wp_scripts'] = null;
	}

	public function tearDown(): void {
		$GLOBALS['wp_scripts'] = null;

		parent::tearDown();
	}

	public function testEmailRequirementScriptIsAddedOnAddNewUserScreen(): void {
		enqueue_user_new_script( 'user-new.php' );

		$inline = implode( "\n", (array) wp_scripts()->get_data( 'wp-ajax-response', 'after' ) );

		$this->assertStringContainsString( 'toggleEmailRequired', $inline );
		$this->assertStringContainsString( wp_json_encode( GUEST_ROLE ), $inline );
	}

	public function testEmailRequirementScriptIsNotAddedOnOtherScreens(): void {
		enqueue_user_new_script( 'user-edit.php' );

		$inline = implode( "\n", (array) wp_scripts()->get_data( 'wp-ajax-response', 'after' ) );

		$this->assertStringNotContainsString( 'toggleEmailRequired', $inline );
	}
}

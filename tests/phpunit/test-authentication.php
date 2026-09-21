<?php
/**
 * Authentication tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;

class TestAuthentication extends TestCase {
	public function testGuestAuthorCannotLogIn(): void {
		$user = self::$users[ GUEST_ROLE ];
		wp_set_password( 'password', $user->ID );

		$result = wp_authenticate( $user->user_login, 'password' );

		$this->assertWPError( $result );
		$this->assertSame( 'authorship_guest_author_login', $result->get_error_code() );
	}

	public function testOtherUserCanLogIn(): void {
		$user = self::$users['subscriber'];
		wp_set_password( 'password', $user->ID );

		$this->assertInstanceOf( \WP_User::class, wp_authenticate( $user->user_login, 'password' ) );
	}

	public function testApplicationPasswordsAreOnlyUnavailableToGuestAuthors(): void {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		$this->assertFalse( wp_is_application_passwords_available_for_user( self::$users[ GUEST_ROLE ] ) );
		$this->assertTrue( wp_is_application_passwords_available_for_user( self::$users['subscriber'] ) );
	}

	public function testGuestAuthorCannotResetPassword(): void {
		$result = get_password_reset_key( self::$users[ GUEST_ROLE ] );

		$this->assertWPError( $result );
		$this->assertSame( 'no_password_reset', $result->get_error_code() );
	}

	public function testOtherUserCanResetPassword(): void {
		$this->assertIsString( get_password_reset_key( self::$users['subscriber'] ) );
	}
}

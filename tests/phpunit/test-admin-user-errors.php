<?php
/**
 * Required user fields tests for guest authors in the admin area.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_Error;
use WP_User;

use const Authorship\GUEST_ROLE;

/**
 * @group ms-excluded
 */
class TestAdminUserErrors extends TestCase {
	public function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/user.php';
		require_once dirname( __DIR__, 2 ) . '/inc/admin.php';

		add_action( 'user_profile_update_errors', 'Authorship\\Admin\\remove_required_fields_errors', 10, 3 );

		wp_set_current_user( self::$users['admin']->ID );
	}

	public function tearDown(): void {
		$_POST = [];

		parent::tearDown();
	}

	/**
	 * @param array<string, string> $data Form data.
	 * @return int|WP_Error
	 */
	private function add_user( array $data ) {
		$_POST = wp_slash( array_merge( [
			'user_login' => 'guest' . wp_rand(),
			'email' => '',
			'pass1' => '',
			'pass2' => '',
		], $data ) );

		return edit_user();
	}

	public function testGuestAuthorCanBeAddedWithoutEmailOrPassword(): void {
		$user_id = $this->add_user( [
			'role' => GUEST_ROLE,
		] );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );

		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( [ GUEST_ROLE ], $user->roles );
		$this->assertNotEmpty( $user->user_pass );
	}

	public function testGuestAuthorPasswordIsKeptWhenProvided(): void {
		$user_id = $this->add_user( [
			'role' => GUEST_ROLE,
			'pass1' => 'correct horse battery staple',
			'pass2' => 'correct horse battery staple',
		] );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );

		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertTrue( wp_check_password( 'correct horse battery staple', $user->user_pass, $user_id ) );
	}

	public function testMismatchedPasswordsAreRejectedForGuestAuthor(): void {
		$result = $this->add_user( [
			'role' => GUEST_ROLE,
			'pass1' => 'one password',
			'pass2' => 'another password',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'pass', $result->get_error_codes() );
	}

	public function testMalformedEmailIsRejectedForGuestAuthor(): void {
		$result = $this->add_user( [
			'role' => GUEST_ROLE,
			'email' => 'not-an-email',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_email', $result->get_error_codes() );
	}

	public function testEmailIsRequiredForOtherRoles(): void {
		$result = $this->add_user( [
			'role' => 'author',
			'pass1' => 'correct horse battery staple',
			'pass2' => 'correct horse battery staple',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNotEmpty( array_intersect( [ 'empty_email', 'invalid_email' ], $result->get_error_codes() ) );
	}

	public function testPasswordIsRequiredForOtherRoles(): void {
		$result = $this->add_user( [
			'role' => 'author',
			'email' => 'new.author@example.org',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'pass', $result->get_error_codes() );
	}
}

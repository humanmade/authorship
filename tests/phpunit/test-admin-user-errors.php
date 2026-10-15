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
	/**
	 * @var array<string, mixed>
	 */
	private $post_backup = [];

	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore HM.Security.NonceVerification.Missing -- Backing up test state.
		$this->post_backup = $_POST;

		require_once ABSPATH . 'wp-admin/includes/user.php';
		require_once dirname( __DIR__, 2 ) . '/inc/admin.php';

		add_action( 'user_profile_update_errors', 'Authorship\\Admin\\remove_required_fields_errors', 10, 3 );

		wp_set_current_user( self::$users['admin']->ID );
	}

	public function tearDown(): void {
		$_POST = $this->post_backup;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $data Form data.
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

	/**
	 * @param int                   $user_id User ID.
	 * @param array<string, string> $data    Form data.
	 * @return int|WP_Error
	 */
	private function update_user( int $user_id, array $data ) {
		$_POST = wp_slash( array_merge( [
			'email' => '',
			'nickname' => 'Guest',
			'pass1' => '',
			'pass2' => '',
		], $data ) );

		return edit_user( $user_id );
	}

	public function testGuestAuthorCanBeAddedWithoutEmailOrPassword(): void {
		$user_id = $this->add_user( [
			'role' => GUEST_ROLE,
		] );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );

		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( [ GUEST_ROLE ], $user->roles );
		$this->assertSame( '', $user->user_email );
		$this->assertNotEmpty( $user->user_pass );
	}

	public function testGuestAuthorEmailCanBeCleared(): void {
		$guest_id = self::factory()->user->create( [
			'role' => GUEST_ROLE,
			'user_email' => 'guest.cleared@example.org',
		] );

		$result = $this->update_user( $guest_id, [
			'role' => GUEST_ROLE,
		] );

		$this->assertSame( $guest_id, $result );
		$this->assertSame( '', get_userdata( $guest_id )->user_email );
	}

	public function testGuestAuthorCanBeUpdatedWithoutEmailWhenRoleIsNotSubmitted(): void {
		$guest_id = self::factory()->user->create( [
			'role' => GUEST_ROLE,
			'user_email' => '',
		] );

		$result = $this->update_user( $guest_id, [] );

		$this->assertSame( $guest_id, $result );
	}

	public function testOtherErrorsAreKeptWhenRoleIsNotSubmitted(): void {
		$result = $this->update_user( self::$users['author']->ID, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'empty_email', $result->get_error_codes() );
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

	public function testPasswordWithBackslashIsRejectedForGuestAuthor(): void {
		$result = $this->add_user( [
			'role' => GUEST_ROLE,
			'pass1' => 'back\\slash',
			'pass2' => 'back\\slash',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'pass', $result->get_error_codes() );
	}

	/**
	 * @dataProvider dataMalformedEmails
	 */
	public function testMalformedEmailIsRejectedForGuestAuthor( string $email ): void {
		$result = $this->add_user( [
			'role' => GUEST_ROLE,
			'email' => $email,
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		// Depending on the WordPress version, core reports malformed emails as empty or invalid.
		$this->assertNotEmpty( array_intersect( [ 'empty_email', 'invalid_email' ], $result->get_error_codes() ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function dataMalformedEmails(): array {
		return [
			'plain text' => [ 'not-an-email' ],
			'tag' => [ '<foo>' ],
		];
	}

	public function testWhitespaceEmailIsTreatedAsEmptyForGuestAuthor(): void {
		$user_id = $this->add_user( [
			'role' => GUEST_ROLE,
			'email' => '   ',
		] );

		$this->assertIsInt( $user_id );
		$this->assertSame( '', get_userdata( $user_id )->user_email );
	}

	public function testNonStringEmailIsRejectedForGuestAuthor(): void {
		$result = $this->add_user( [
			'role' => GUEST_ROLE,
			'email' => [ 'guest@example.org' ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNotEmpty( array_intersect( [ 'empty_email', 'invalid_email' ], $result->get_error_codes() ) );
	}

	public function testEmailIsRequiredForOtherRoles(): void {
		$result = $this->add_user( [
			'role' => 'author',
			'pass1' => 'correct horse battery staple',
			'pass2' => 'correct horse battery staple',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'empty_email', $result->get_error_codes() );
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

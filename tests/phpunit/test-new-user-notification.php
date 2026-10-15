<?php
/**
 * New user notification tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;

class TestNewUserNotification extends EmailTestCase {
	public function testNotificationIsNotSentToUserWithoutEmail(): void {
		$guest_id = self::factory()->user->create( [
			'role' => GUEST_ROLE,
			'user_email' => '',
		] );

		wp_new_user_notification( $guest_id, null, 'both' );

		$recipients = [];
		foreach ( $this->mailer->mock_sent as $i => $mock_sent ) {
			$recipients[] = $this->mailer->get_recipient( 'to', $i )->address;
		}

		// Only the site admin is notified.
		$this->assertSame( [ get_option( 'admin_email' ) ], $recipients );
		$this->assertSame( '', get_userdata( $guest_id )->user_activation_key );
	}

	public function testNotificationIsSentToUserWithEmail(): void {
		$guest_id = self::factory()->user->create( [
			'role' => GUEST_ROLE,
			'user_email' => 'guest.notified@example.org',
		] );

		wp_new_user_notification( $guest_id, null, 'user' );

		$this->assertCount( 1, $this->mailer->mock_sent );
		$this->assertSame( 'guest.notified@example.org', $this->mailer->get_recipient( 'to', 0 )->address );
	}
}

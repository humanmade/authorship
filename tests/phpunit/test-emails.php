<?php
/**
 * Email tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;

class TestEmails extends EmailTestCase {
	public function testCommentModerationEmailIsSentToUsersWhoCanModerateIt() : void {
		// Attributed to one user of each role:
		$post_id = self::factory()->post->create( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => array_column( self::$users, 'ID' ),
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID'  => $post_id,
			'comment_approved' => '0',
		] );

		wp_new_comment_notify_moderator( $comment_id );

		$expected = [
			get_option( 'admin_email' ),
			self::$users['editor']->user_email,
			self::$users['admin']->user_email,
			self::$users['author']->user_email,
		];

		$actual = [];

		foreach ( $this->mailer->mock_sent as $i => $mock_sent ) {
			$actual[] = $this->mailer->get_recipient( 'to', $i )->address;
		}

		$this->assertSame( $expected, $actual );
	}

	public function testCommentNotificationEmailIsSentToAllAttributedAuthors() : void {
		// Attributed to one user of each role:
		$post_id = self::factory()->post->create( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => array_column( self::$users, 'ID' ),
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID'  => $post_id,
			'comment_approved' => '1',
		] );

		wp_notify_postauthor( $comment_id );

		$expected = [
			self::$users['editor']->user_email,
			self::$users['admin']->user_email,
			self::$users['author']->user_email,
			self::$users['contributor']->user_email,
			self::$users['subscriber']->user_email,
			self::$users[ GUEST_ROLE ]->user_email,
			self::$users['no_role']->user_email,
		];

		$actual = [];

		foreach ( $this->mailer->mock_sent as $i => $mock_sent ) {
			$actual[] = $this->mailer->get_recipient( 'to', $i )->address;
		}

		$this->assertSame( $expected, $actual );
	}

}

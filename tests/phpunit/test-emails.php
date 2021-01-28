<?php
/**
 * Email tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

use MockPHPMailer;

class TestEmails extends EmailTestCase {
	public function testCommentModerationEmailIsSentToUsersWhoCanModerateIt() : void {
		$factory = self::factory()->post;

		// Attributed to one user of each role:
		$post = $factory->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => array_column( self::$users, 'ID' ),
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID'  => $post->ID,
			'comment_approved' => '0',
		] );

		wp_new_comment_notify_moderator( $comment_id );

		$expected = [
			get_option( 'admin_email' ),
			self::$users['editor']->user_email,
			self::$users['admin']->user_email,
			self::$users['author']->user_email,
		];

		/**
		 * @var MockPHPMailer
		 */
		$mailer = tests_retrieve_phpmailer_instance();
		$actual = [];

		foreach ( $mailer->mock_sent as $i => $mock_sent ) {
			$actual[] = $mailer->get_recipient( 'to', $i )->address;
		}

		$this->assertSame( $expected, $actual );
	}

}

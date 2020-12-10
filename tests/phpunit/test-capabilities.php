<?php
/**
 * User capability tests for the plugin.
 *
 * @package authorship
 *
 * @TODO this entire test class needs to also run against:
 *
 *  - a CPT with `map_meta_cap` set to `true`
 *  - a CPT with `map_meta_cap` set to `false`
 *  - posts with post status of type that is not `public`
 *  - posts with post status of type that is `private`
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

class TestCapabilities extends TestCase {
	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 */
	public function testUserCanManagePostTheyAreAttributedTo( string $role ) : void {
		$factory = self::factory()->post;

		$user_id = self::$users[ $role ]->ID;

		// Attributed to user, owned by Admin.
		$draft_post = $factory->create_and_get( [
			'post_status' => 'draft',
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Attributed to user, owned by Admin.
		$published_post = $factory->create_and_get( [
			'post_status' => 'publish',
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Attributed to user, owned by Admin.
		$scheduled_post = $factory->create_and_get( [
			'post_status' => 'future',
			'post_date'   => date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) ),
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Draft post:
		$this->assertTrue( user_can( $user_id, 'edit_post', $draft_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'publish_post', $draft_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'read_post', $draft_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'delete_post', $draft_post->ID ) );

		// Published post:
		$this->assertTrue( user_can( $user_id, 'edit_post', $published_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'publish_post', $published_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'read_post', $published_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'delete_post', $published_post->ID ) );

		// Scheduled post:
		$this->assertTrue( user_can( $user_id, 'edit_post', $scheduled_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'publish_post', $scheduled_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'read_post', $scheduled_post->ID ) );
		$this->assertTrue( user_can( $user_id, 'delete_post', $scheduled_post->ID ) );
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesAndCaps() : array {
		return [
			[
				'author',
			],
		];
	}
}

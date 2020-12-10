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
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

class TestCapabilities extends TestCase {
	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManageDraftPostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'draft';

		// Draft, attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => $status,
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManagePublishedPostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'publish';

		// Published, attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => $status,
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManageScheduledPostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'future';

		// Scheduled, attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => $status,
			'post_date'   => date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) ),
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Scheduled post:
		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManagePendingPostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'pending';

		// Pending ("Submit for Review"), attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => $status,
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManageTrashedPostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'trash';

		// Trashed, attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => 'publish',
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );
		wp_trash_post( $post->ID );

		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManagePrivatePostTheyAreAttributedTo( string $role, array $caps ) : void {
		$user_id = self::$users[ $role ]->ID;
		$status  = 'private';

		// Trashed, attributed to user, owned by Admin.
		$post = self::factory()->post->create_and_get( [
			'post_status' => $status,
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		$this->assertSame( $caps[ $status ]['edit_post'], user_can( $user_id, 'edit_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['publish_post'], user_can( $user_id, 'publish_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['read_post'], user_can( $user_id, 'read_post', $post->ID ) );
		$this->assertSame( $caps[ $status ]['delete_post'], user_can( $user_id, 'delete_post', $post->ID ) );
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesAndCaps() : array {
		return [
			[
				'editor',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'future' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'pending' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'trash' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'private' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
				],
			],
			[
				'author',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'future' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'pending' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'trash' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'private' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
				],
			],
			[
				'contributor',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => false,
					],
					'future' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'pending' => [
						'edit_post'    => true,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'trash' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'private' => [
						'edit_post'    => true,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => true,
					],
				],
			],
			[
				'subscriber',
				[
					'draft' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'publish' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => false,
					],
					'future' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'pending' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'trash' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'private' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => false,
					],
				],
			],
		];
	}
}
<?php
/**
 * Admin area tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\Admin;
use WP_Error;

use const Authorship\Admin\COLUMN_NAME;
use const Authorship\GUEST_ROLE;

class TestAdmin extends TestCase {
	public function testRemoveRequiredFieldsErrorsRemovesGuestSpecificErrors() : void {
		$errors = new WP_Error();
		$errors->add( 'empty_email', 'Email is required.' );
		$errors->add( 'nickname', 'Nickname is required.' );
		$errors->add( 'other', 'Other error.' );

		$user = (object) [
			'role' => GUEST_ROLE,
		];

		Admin\remove_required_fields_errors( $errors, true, $user );

		$this->assertNotContains( 'empty_email', $errors->get_error_codes() );
		$this->assertNotContains( 'nickname', $errors->get_error_codes() );
		$this->assertContains( 'other', $errors->get_error_codes() );
	}

	public function testRemoveRequiredFieldsErrorsDoesNothingForNonGuestRole() : void {
		$errors = new WP_Error();
		$errors->add( 'empty_email', 'Email is required.' );
		$errors->add( 'nickname', 'Nickname is required.' );

		$user = (object) [
			'role' => 'author',
		];

		Admin\remove_required_fields_errors( $errors, true, $user );

		$this->assertContains( 'empty_email', $errors->get_error_codes() );
		$this->assertContains( 'nickname', $errors->get_error_codes() );
	}

	public function testFilterPostColumnsReplacesAuthorColumnInPlace() : void {
		$columns = [
			'title'  => 'Title',
			'author' => 'Author',
			'date'   => 'Date',
		];

		$filtered = Admin\filter_post_columns( $columns );

		$this->assertArrayNotHasKey( 'author', $filtered );
		$this->assertArrayHasKey( COLUMN_NAME, $filtered );
		$this->assertSame( [ 'title', COLUMN_NAME, 'date' ], array_keys( $filtered ) );
	}

	public function testInitAdminColsRegistersColumnHooksForSupportedPostTypes() : void {
		$supported_post_types_filter = function() : array {
			return [ 'post' ];
		};
		add_filter( 'authorship_supported_post_types', $supported_post_types_filter );

		try {
			Admin\init_admin_cols();

			$this->assertSame(
				10,
				has_filter( 'manage_post_posts_columns', 'Authorship\\Admin\\filter_post_columns' )
			);
			$this->assertSame(
				10,
				has_action( 'manage_post_posts_custom_column', 'Authorship\\Admin\\action_author_column' )
			);
		} finally {
			remove_filter( 'authorship_supported_post_types', $supported_post_types_filter );
		}
	}

	public function testActionAuthorColumnOutputsLinkedAuthorList() : void {
		$post = self::factory()->post->create_and_get( [
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => self::$users['admin']->ID,
		] );

		\Authorship\set_authors( $post, [ self::$users['author']->ID, self::$users['editor']->ID ] );

		ob_start();
		Admin\action_author_column( COLUMN_NAME, $post->ID );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<ul', $output );
		$this->assertStringContainsString( self::$users['author']->display_name, $output );
		$this->assertStringContainsString( self::$users['editor']->display_name, $output );
	}
}

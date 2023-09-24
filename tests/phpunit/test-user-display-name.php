<?php
/**
 * General user saving tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\TAXONOMY;

class TestUserDisplayName extends TestCase {
	public function testPostAuthorshipUpdatesTermNameWhenUserDisplayNameIsUpdated() : void {
		// Create an author term for the author user.
		$author_term = get_term_by( 'slug', (string) self::$users['author']->ID, TAXONOMY );
		if ( ! $author_term ) {
			wp_insert_term( self::$users['author']->ID, TAXONOMY );
			$author_term = get_term_by( 'slug', (string) self::$users['author']->ID, TAXONOMY );
		}

		$display_name = 'New Author Name';

		wp_update_user( [
			'ID' => self::$users['author']->ID,
			'display_name' => $display_name,
		] );

		$author_term = get_term_by( 'slug', (string) self::$users['author']->ID, TAXONOMY );

		$this->assertSame( $display_name, $author_term->name );
	}
}

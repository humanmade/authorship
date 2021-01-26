<?php
/**
 * RSS2 tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

class TestRSS2 extends FeedTestCase {
	public function testMultipleAuthorNamesAreListed() {
		$factory = self::factory()->post;

		// Attributed to Editor and Author, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
			],
		] );

		$feed = $this->go_to_feed( '/?feed=rss2' );

		$items  = xml_find( $feed, 'rss', 'channel', 'item' );
		$author = xml_find( $items[0]['child'], 'dc:creator' );

		$expected = sprintf(
			'%1$s, %2$s',
			self::$users['editor']->display_name,
			self::$users['author']->display_name
		);

		$this->assertCount( 1, $author );
		$this->assertSame( $expected, $author[0]['content'] );
	}
}

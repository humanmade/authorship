<?php
/**
 * Base test case for feed tests for the plugin.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

/**
 * Feed test class for the plugin.
 */
abstract class FeedTestCase extends TestCase {
	/**
	 * Loads a feed template for the given URL.
	 *
	 * @param string $url The URL to visit, eg. `/?feed=rss2`.
	 * @return mixed[] The feed output as an array.
	 */
	public function go_to_feed( string $url ) : array {
		$this->go_to( $url );

		ob_start();

		// This @-suppressor is needed as the feed files in WordPress core output headers
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@load_template( ABSPATH . '/wp-includes/feed-' . get_query_var( 'feed' ) . '.php' );

		return xml_to_array( ob_get_clean() );
	}
}

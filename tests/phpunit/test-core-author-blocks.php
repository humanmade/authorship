<?php
/**
 * Tests for core author block filters.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_Block;
use WP_Post;
use WP_User;

use function Authorship\get_post_authors;

use const Authorship\POSTS_PARAM;

class TestCoreAuthorBlocks extends TestCase {
	public function testPostAuthorsFallsBackToNativePostAuthor(): void {
		add_filter( 'authorship_default_author', '__return_empty_array' );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		remove_filter( 'authorship_default_author', '__return_empty_array' );

		$this->assertSame( [ self::$users['editor']->ID ], wp_list_pluck( get_post_authors( $post ), 'ID' ) );
	}

	public function testPostAuthorNameUsesAttributedAuthorArchiveUrl(): void {
		[ $post, $native_author, $attributed_author ] = $this->create_post_with_different_attributed_author();

		$output = $this->render_block( 'core/post-author-name', [
			'isLink' => true,
		], $post );

		$this->assertStringContainsString( $attributed_author->display_name, $output );
		$this->assertStringContainsString( get_author_posts_url( $attributed_author->ID ), $output );
		$this->assertStringNotContainsString( get_author_posts_url( $native_author->ID ), $output );
	}

	public function testPostAuthorBiographyUsesAttributedAuthorWhenCoreRendersNoContent(): void {
		[ $post, $native_author, $attributed_author ] = $this->create_post_with_different_attributed_author();

		update_user_meta( $attributed_author->ID, 'description', 'Attributed author biography.' );

		$output = $this->render_block( 'core/post-author-biography', [
			'textAlign' => 'center',
		], $post );

		$this->assertStringContainsString( 'Attributed author biography.', $output );
		$this->assertStringContainsString( 'has-text-align-center', $output );
		$this->assertStringNotContainsString( $native_author->display_name, $output );
	}

	public function testAvatarUsesAttributedAuthor(): void {
		[ $post, $native_author, $attributed_author ] = $this->create_post_with_different_attributed_author();

		$output = $this->render_block( 'core/avatar', [
			'isLink'     => true,
			'linkTarget' => '_blank',
			'size'       => 40,
		], $post );

		$this->assertStringContainsString( get_author_posts_url( $attributed_author->ID ), $output );
		$this->assertStringNotContainsString( get_author_posts_url( $native_author->ID ), $output );
		$this->assertStringContainsString( sprintf( '%s Avatar', $attributed_author->display_name ), $output );
		$this->assertStringContainsString( sprintf( '(%s author archive, opens in a new tab)', $attributed_author->display_name ), $output );
	}

	/**
	 * Creates a post whose attributed author differs from its native author.
	 *
	 * @return array{WP_Post, WP_User, WP_User} Post, native author and attributed author.
	 */
	private function create_post_with_different_attributed_author(): array {
		/** @var WP_User */
		$native_author = self::factory()->user->create_and_get( [
			'display_name' => 'native-author',
			'role'         => 'author',
			'user_login'   => 'native-author',
		] );
		/** @var WP_User */
		$attributed_author = self::factory()->user->create_and_get( [
			'display_name' => 'Attributed Author',
			'role'         => 'author',
			'user_login'   => 'attributed-author',
		] );

		/** @var WP_Post */
		$post = self::factory()->post->create_and_get( [
			'post_author' => $native_author->ID,
			POSTS_PARAM   => [ $attributed_author->ID ],
		] );

		return [ $post, $native_author, $attributed_author ];
	}

	/**
	 * Renders a dynamic core block for a post context.
	 *
	 * @param string  $name  Block name.
	 * @param array   $attrs Block attributes.
	 * @param WP_Post $post  Post to render for.
	 * @return string Rendered markup.
	 */
	private function render_block( string $name, array $attrs, WP_Post $post ): string {
		$block = new WP_Block(
			[
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => [],
				'innerContent' => [],
				'innerHTML'    => '',
			],
			[ 'postId' => $post->ID ]
		);

		return $block->render();
	}
}

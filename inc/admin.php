<?php
/**
 * Admin area related functionality for Authorship.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Admin;

use WP_Post;
use WP_Error;
use WP_User;
use stdClass;

use const Authorship\GUEST_ROLE;

use function Authorship\get_authors;
use function Authorship\get_supported_post_types;

const COLUMN_NAME = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap() : void {
	// Actions.
	add_action( 'admin_init', __NAMESPACE__ . '\\init_admin_cols', 99 );
	add_action( 'user_profile_update_errors', __NAMESPACE__ . '\\remove_required_fields_errors', 10, 3 );
}

/**
 * Removes required fields errors from the user profile update.
 *
 * @param WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update Whether this is a user update.
 * @param stdClass $user   User object (passed by reference).
 * @return void
 */
function remove_required_fields_errors( WP_Error $errors, bool $update, stdClass $user ) : void {
	if ( $user->role !== GUEST_ROLE ) {
		return;
	}

	$current_error_codes = $errors->get_error_codes();
	$removed_error_codes = [ 'empty_email' ];
	if ( $update ) {
		// Remove those errors on update.
		array_push( $removed_error_codes, 'nickname' );
	} else {
		// Remove those errors on add.
		array_push( $removed_error_codes, 'pass' );
	}

	// Remove matched errors.
	$codes = array_intersect( $current_error_codes, $removed_error_codes );
	foreach ( $codes as $code ) {
		$errors->remove( $code );
	}

	// Provide a random password for the user (JS disabled).
	if ( empty( $user->user_pass ) && in_array( 'pass', $codes, true ) ) {
		$user->user_pass = wp_generate_password();
	}
}

/**
 * Fires as an admin screen or script is being initialized.
 */
function init_admin_cols() : void {
	foreach ( get_supported_post_types() as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", __NAMESPACE__ . '\\filter_post_columns' );
		add_action( "manage_{$post_type}_posts_custom_column", __NAMESPACE__ . '\\action_author_column', 10, 2 );
	}
}

/**
 * Fires for each custom column of a specific post type in the Posts list table.
 *
 * @param string $column_name The name of the column to display.
 * @param int    $post_id     The current post ID.
 */
function action_author_column( string $column_name, int $post_id ) : void {
	if ( COLUMN_NAME !== $column_name ) {
		return;
	}

	/** @var WP_Post */
	$post = get_post( $post_id );

	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		return;
	}

	echo '<ul style="margin:0">';

	foreach ( $authors as $user ) {
		$url = add_query_arg( [
			'post_type' => $post->post_type,
			'author'    => $user->ID,
		], admin_url( 'edit.php' ) );
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( $url ),
			esc_html( $user->display_name )
		);
	}

	echo '</ul>';
}

/**
 * Filters the columns displayed in the Posts list table for a specific post type.
 *
 * @param string[] $post_columns An associative array of column headings.
 * @return string[] An associative array of column headings.
 */
function filter_post_columns( array $post_columns ) : array {
	$new_columns = [];

	foreach ( $post_columns as $key => $value ) {
		if ( 'author' === $key ) {
			// This replaces the default Author column with our own, in the same position.
			$new_columns[ COLUMN_NAME ] = __( 'Authors', 'authorship' );
		} else {
			$new_columns[ $key ] = $value;
		}
	}

	return $new_columns;
}

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

use function Authorship\get_authors;
use function Authorship\get_supported_post_types;

use const Authorship\GUEST_ROLE;

const COLUMN_NAME = 'authorship';

/**
 * Bootstraps the main actions and filters.
 */
function bootstrap(): void {
	// Actions.
	add_action( 'admin_init', __NAMESPACE__ . '\\init_admin_cols', 99 );
	add_action( 'user_profile_update_errors', __NAMESPACE__ . '\\remove_required_fields_errors', 10, 3 );
}

/**
 * Removes required fields errors from the user profile update.
 *
 * Guest authors don't need an email or a password. When a guest author is added without a
 * password, a random one is assigned.
 *
 * @param WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update Whether this is a user update.
 * @param stdClass $user   User object (passed by reference).
 * @return void
 */
function remove_required_fields_errors( WP_Error $errors, bool $update, stdClass $user ): void {
	if ( ! is_guest_author_submission( $update, $user ) ) {
		return;
	}

	$error_codes_to_remove = [ 'nickname' ];

	/*
	 * Only skip the email requirement when the field was left empty. Depending on the
	 * WordPress version, core reports an empty field as `empty_email`, `invalid_email` or both,
	 * and may sanitize input such as `<foo>` into an empty email, which must still be rejected.
	 * The raw value is only compared, so it isn't sanitized.
	 */
	// phpcs:ignore HM.Security.NonceVerification.Missing, HM.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified before edit_user() runs, and the value is only compared.
	$email = isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
	if ( is_string( $email ) && trim( $email ) === '' ) {
		array_push( $error_codes_to_remove, 'empty_email', 'invalid_email' );

		// Core only sets the email when it's valid, so set it explicitly to clear an existing one.
		$user->user_email = '';
	}

	/*
	 * Guest authors don't log in, so don't require a password when adding one. The form
	 * generates one client side, so this only happens when JS is disabled or the field is
	 * cleared. A random password is assigned so that no account has an empty password. Other
	 * password errors, such as mismatched passwords, are kept.
	 */
	if ( ! $update && empty( $user->user_pass ) ) {
		$error_codes_to_remove[] = 'pass';
		$user->user_pass = wp_generate_password( 24 );
	}

	$current_codes = array_intersect( $errors->get_error_codes(), $error_codes_to_remove );

	foreach ( $current_codes as $code ) {
		$errors->remove( $code );
	}
}

/**
 * Determines whether the user being added or updated is a guest author.
 *
 * The submitted role is only set when the role field is submitted and the current user can
 * change it. Otherwise, the role of an existing user is used.
 *
 * @param bool     $update Whether this is a user update.
 * @param stdClass $user   User object.
 * @return bool Whether the user is a guest author.
 */
function is_guest_author_submission( bool $update, stdClass $user ): bool {
	if ( isset( $user->role ) ) {
		return $user->role === GUEST_ROLE;
	}

	if ( ! $update ) {
		return false;
	}

	$existing_user = get_userdata( $user->ID );

	return $existing_user instanceof WP_User && in_array( GUEST_ROLE, $existing_user->roles, true );
}

/**
 * Fires as an admin screen or script is being initialized.
 */
function init_admin_cols(): void {
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
function action_author_column( string $column_name, int $post_id ): void {
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
function filter_post_columns( array $post_columns ): array {
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

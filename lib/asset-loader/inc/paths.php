<?php
/**
 * Define utility functions for evaluating and manipulating file system paths & uris.
 */

declare( strict_types=1 );

namespace Asset_Loader\Paths;

/**
 * Check if provided path is within the stylesheet directory.
 *
 * @param string $path An absolute file system path.
 * @return boolean
 */
function is_theme_path( string $path ): bool {
	return strpos( $path, get_stylesheet_directory() ) === 0;
}

/**
 * Check if provided path is within the parent theme directory.
 *
 * @param string $path An absolute file system path.
 * @return boolean
 */
function is_parent_theme_path( string $path ): bool {
	return strpos( $path, get_template_directory() ) === 0;
}

/**
 * Given a file system path, return the relative path from the theme folder.
 *
 * @param string $path A file system path within a parent or child theme.
 * @return string The relative path starting from the theme folder.
 */
function theme_relative_path( string $path ): string {
	if ( is_theme_path( $path ) ) {
		return str_replace( trailingslashit( get_stylesheet_directory() ), '', $path );
	}
	if ( is_parent_theme_path( $path ) ) {
		return str_replace( trailingslashit( get_template_directory() ), '', $path );
	}
	// This is a bad state. How to indicate?
	return '';
}

/**
 * Check if provided path is within the stylesheet or template directories.
 *
 * @param string $path An absolute file system path.
 * @return boolean
 */
function is_plugin_path( string $path ): bool {
	return ! is_theme_path( $path ) && ! is_parent_theme_path( $path );
}

/**
 * Take in an absolute file system path that may be part of a theme or plugin
 * directory, and return the URL for that file.
 *
 * @param string $path Absolute file path.
 * @return string
 */
function get_file_uri( string $path ): string {
	if ( ! is_plugin_path( $path ) ) {
		return get_theme_file_uri( theme_relative_path( $path ) );
	}

	return content_url( str_replace( WP_CONTENT_DIR, '', $path ) );
}

/**
 * Get the filesystem directory path (with trailing slash) for the file passed in.
 *
 * Note: This is a more descriptively-named equivalent to WP's core plugin_dir_path().
 *
 * @param string $file The path to a file on the local file system.
 * @return string The filesystem path of the directory that contains the provided $file.
 */
function containing_folder( $file ): string {
	return trailingslashit( dirname( $file ) );
}

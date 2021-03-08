<?php
/**
 * Define functions for displaying warnings in the WP Admin.
 */

declare( strict_types=1 );

namespace Asset_Loader\Admin;

/**
 * Return a regular expression pattern for matching HTTPS localhost URLs.
 *
 * @return string Regular expression pattern matching https://localhost.
 */
function get_localhost_pattern() : string {
	return '#https://(localhost|127.0.0.1)#';
}

/**
 * Check to see if a dev server asset is to be loaded from an HTTPS localhost
 * URI, and set up error detection to display a notice reminding the developer
 * to accept self-signed SSL certificates if any such scripts fail to load.
 *
 * @param string $script_uri URI of a script to be loaded.
 * @return void
 */
function maybe_setup_ssl_cert_error_handling( $script_uri ) : void {
	static $error_handling_enabled = false;
	if ( $error_handling_enabled ) {
		// We have already set up the error handling script; no further action needed.
		return;
	}

	// Do nothing in a non-admin context.
	if ( ! is_admin() ) {
		return;
	}

	if ( ! preg_match( get_localhost_pattern(), $script_uri ) ) {
		// Not an HTTPS localhost script.
		return;
	}

	// Toggle the static variable so we only add actions & filters once.
	$error_handling_enabled = true;

	add_action( 'admin_head', __NAMESPACE__ . '\\render_localhost_error_detection_script', 5 );
	add_filter( 'script_loader_tag', __NAMESPACE__ . '\\add_onerror_to_localhost_scripts', 10, 3 );
}

/**
 * Inject an onerror attribute into the rendered script tag for any script
 * loaded from localhost with an HTTPS protocol.
 *
 * @param string $tag    The HTML of the script tag to render.
 * @param string $handle The registered script handle for this tag.
 * @param string $src    The src URI of the JavaScript file this script loads.
 * @return string The script tag HTML, conditionally transformed.
 */
function add_onerror_to_localhost_scripts( string $tag, string $handle, string $src ) : string {
	if ( ! preg_match( get_localhost_pattern(), $src ) ) {
		return $tag;
	}
	return preg_replace(
		'/<script/',
		'<script onerror="maybeSSLError && maybeSSLError( this );"',
		$tag
	);
}

/**
 * Render inline JS into the page header to register a function which will be
 * called should any of our registered HTTPS localhost scripts fail to load.
 *
 * If errors are detected when loading scripts from HTTPS localhost URLs, use
 * WP's JavaScript notification system to display a contextual warning banner.
 *
 * @return void
 */
function render_localhost_error_detection_script() : void {
	?>
<script>
( function() {
	var scriptsWithErrors = [];

	/**
	 * @param HTMLScriptElement The script which experienced an error.
	 */
	window.maybeSSLError = function( script ) {
		scriptsWithErrors.push( script );
	};

	/**
	 * Check whether an error has occurred, then attempt to display a Block Editor
	 * notice to alert the developer if so.
	 *
	 * @return void
	 */
	function processErrors() {
		if ( ! scriptsWithErrors.length ) {
			// There are no problems to highlight.
			return;
		}

		var notices = null;
		if ( window.wp && window.wp.data && window.wp.data.dispatch ) {
			notices = window.wp.data.dispatch( 'core/notices' );
		}
		if ( ! notices ) {
			// We're not in a context where it is easy to display a notice from JS.
			return;
		}

		// Build a list of problem hosts.
		var hosts = scriptsWithErrors.reduce(
			function( hosts, script ) {
				var src = script.getAttribute( 'src' );
				if ( ! src || ! /https:\/\/localhost/i.test( src ) ) {
					return hosts;
				}
				src = src.replace( /^(https:\/\/localhost:\d+).*$/i, '$1' );
				hosts[ src ] = true;
				return hosts;
			},
			{}
		);
		hosts = Object.keys( hosts );

		// Build the error markup.
		const messageHTML = [
			'<strong>Error loading scripts from localhost!</strong>',
			'<br>',
			'Ensure that ',
			( hosts.length > 1 ? 'these hosts are ' : 'this host is ' ),
			'accessible, and that you have accepted any development server SSL certificates:',
			'<ul>',
			hosts.map( host => '<li><a target="_blank" href="' + host + '">' + host + '</a></li>' ).join( '' ),
			'</ul>'
		].join( '' );

		notices.createErrorNotice( messageHTML, { __unstableHTML: true } );
	}

	// Set up processErrors to run 1 second after page load.
	document.addEventListener( 'DOMContentLoaded', function() {
		setTimeout( processErrors, 1000 );
	}, { once: true } );
} )();
</script>
	<?php
}

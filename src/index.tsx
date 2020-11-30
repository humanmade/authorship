import * as React from 'react';

import { PluginPostStatusInfo } from '@wordpress/edit-post';

declare const wp;

const registerPlugin = wp.plugins.registerPlugin;

/**
 * Renders the author selector control.
 *
 * @returns {JSX.Element} An element.
 */
const AuthorsSelect = () => {
	return (
		<PluginPostStatusInfo>
			<p>Hello, World!</p>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'authorship', {
	render: AuthorsSelect,
} );

import * as React from 'react';

import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';

import AuthorsSelect from './components/authors-select';

declare const wp: any;

const registerPlugin = wp.plugins.registerPlugin;

const Select = compose( [
	withDispatch( dispatch => ( {
		onUpdate( value: number[] ) {
			dispatch( 'core/editor' ).editPost( {
				authorship: value,
			} );
		},
	} ) ),
] )( AuthorsSelect );

const render = () => (
	<PluginPostStatusInfo>
		<Select/>
	</PluginPostStatusInfo>
);

registerPlugin( 'authorship', { render } );

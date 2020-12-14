import * as React from 'react';

import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { get } from 'lodash';

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
		onError( message: string ) {
			dispatch( 'core/notices' ).createErrorNotice( message );
		},
	} ) ),
	withSelect( select => {
		const post = select( 'core/editor' ).getCurrentPost();
		return {
			hasAssignAuthorAction: get(
				post,
				[ '_links', 'authorship:action-assign-authorship' ],
				false
			),
		};
	} ),
] )( AuthorsSelect );

const render = () => (
	<PluginPostStatusInfo>
		<Select/>
	</PluginPostStatusInfo>
);

registerPlugin( 'authorship', { render } );

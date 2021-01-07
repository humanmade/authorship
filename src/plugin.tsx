import { get } from 'lodash';
import * as React from 'react';

import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';

import AuthorsSelect from './components/authors-select';

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

export const name = 'authorship';

export const settings = {
	icon: null,
	render() {
		return (
			<PluginPostStatusInfo>
				<Select/>
			</PluginPostStatusInfo>
		);
	},
};

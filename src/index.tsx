import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';

import AuthorsSelect from './components/authors-select';

declare const wp;

const registerPlugin = wp.plugins.registerPlugin;

const render = compose( [
	withDispatch( dispatch => ( {
		onUpdate( value: number[] ) {
			dispatch( 'core/editor' ).editPost( {
				authorship: value,
			} );
		},
	} ) ),
] )( AuthorsSelect );

registerPlugin( 'authorship', { render } );

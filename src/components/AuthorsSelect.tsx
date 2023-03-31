import { get, isEqual } from 'lodash';
import React, { ReactElement, useState } from 'react';
import { Styles } from 'react-select';
import type {
	WP_REST_API_Error,
	WP_REST_API_User,
} from 'wp-types';

import apiFetch from '@wordpress/api-fetch';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

import { authorshipDataFromWP, Option, SortedOption } from '../types';
import arrayMove from '../utils/arrayMove';

import SortableSelectContainer, { className as containerClassName } from './SortableSelectContainer';

declare const authorshipData: authorshipDataFromWP;

interface AuthorsSelectProps {
	currentAuthorIDs: number[],
	hasAssignAuthorAction: boolean,
	onError: ( message: string ) => void,
	onUpdate: ( value: number[] ) => void,
	postType: string,
	preloadedAuthorOptions: authorshipDataFromWP,
}

/**
 * Creates an option from a REST API user response.
 *
 * @param {WP_REST_API_User} user The user object.
 * @returns {Option} The option object.
 */
const createOption = ( user: WP_REST_API_User ): Option => ( {
	value: user.id,
	label: user.name,
	avatar: user?.avatar_urls?.[48] || null,
} );

const getHelperContainer = (): HTMLElement => document.querySelector( `.${ containerClassName}` );

/**
 * Returns the author selector control.
 *
 * @param {AuthorsSelectProps} props Component props.
 * @returns {ReactElement} An element.
 */
const AuthorsSelect = ( props: AuthorsSelectProps ): ReactElement => {
	const { currentAuthorIDs, hasAssignAuthorAction, onError, onUpdate, postType, preloadedAuthorOptions } = props;

	const isDisabled = ! hasAssignAuthorAction;

	const [ selected, setSelected ] = useState<Option[]>( [] );

	const preloadedAuthorIDs = preloadedAuthorOptions.authors.map( author => author.value );

	if ( ! selected.length && isEqual( preloadedAuthorIDs, currentAuthorIDs ) ) {
		setSelected( preloadedAuthorOptions.authors );
	} else if ( currentAuthorIDs.length && ! selected.length ) {

		const path = addQueryArgs(
			'/authorship/v1/users/',
			{
				include: currentAuthorIDs,
				orderby: 'include',
				post_type: postType,
			}
		);

		const api: Promise<WP_REST_API_User[]> = apiFetch( { path } );

		api.then( users => {
			setSelected( users.map( createOption ) );
		} ).catch( ( error: WP_REST_API_Error ) => {
			onError( error.message );
		} );
	}

	/**
	 * Asynchronously loads the options for the control based on the search parameter.
	 *
	 * @param {string} search The search string.
	 * @returns {Promise<Option[]>} A promise that fulfils the options.
	 */
	const loadOptions = ( search: string ): Promise<Option[]> => {
		const path = addQueryArgs(
			'/authorship/v1/users/',
			{
				search,
				post_type: postType,
			}
		);

		const api: Promise<WP_REST_API_User[]> = apiFetch( { path } );

		return api.then( users =>
			users.map( createOption )
		).catch( ( error: WP_REST_API_Error ) => {
			onError( error.message );
			return [];
		} );
	};

	/**
	 * Declares styles for elements that can't easily be targeted with a CSS selector.
	 */
	const styles: Styles<Option, true> = {
		input: () => ( {
			margin: 0,
			width: '100%',
		} ),
	};

	/**
	 * Handles changes to the selected authors.
	 *
	 * @param {Option[]} [options] The selected options.
	 */
	const changeValue = ( options?: Option[] ) => {
		setSelected( options || [] );
		onUpdate( options ? ( options.map( option => option.value ) ) : [] );
	};

	/**
	 * Handles the creation of a new guest author.
	 *
	 * @param {string} option The new option.
	 */
	const onCreateOption = ( option: string ) => {
		const path = addQueryArgs(
			'/authorship/v1/users/',
			{
				name: option,
			}
		);

		const api: Promise<WP_REST_API_User> = apiFetch( {
			method: 'POST',
			path,
		} );

		return api.then( user => {
			const options = [ ...selected, createOption( user ) ];

			setSelected( options );
			onUpdate( options.map( option => option.value ) );
		} ).catch( ( error: WP_REST_API_Error ) => {
			onError( error.message );
		} );
	};

	/**
	 * Fired when option sorting ends. Updates the component state and calls the update callback.
	 *
	 * @param {SortedOption} option Sorting information for the option.
	 */
	const onSortEnd = ( option: SortedOption ) => {
		const value = arrayMove( selected, option.oldIndex, option.newIndex );
		setSelected( value );
		onUpdate( value.map( option => option.value ) );
	};

	return (
		<SortableSelectContainer
			axis="y"
			distance={ 4 }
			helperContainer={ getHelperContainer }
			isDisabled={ isDisabled }
			loadOptions={ loadOptions }
			lockAxis="y"
			lockToContainerEdges
			styles={ styles }
			value={ selected }
			onChange={ changeValue }
			onCreateOption={ onCreateOption }
			onSortEnd={ onSortEnd }
		/>
	);
};

const mapDispatchToProps = ( dispatch: CallableFunction ): Record<string, CallableFunction> => ( {
	onError( message: string ) {
		dispatch( 'core/notices' ).createErrorNotice( message );
	},
	onUpdate( value: number[] ) {
		dispatch( 'core/editor' ).editPost( {
			authorship: value,
		} );
	},
} );

const mapSelectToProps = ( select: CallableFunction ): Record<string, unknown> => ( {
	currentAuthorIDs: select( 'core/editor' ).getEditedPostAttribute( 'authorship' ),
	postType: select( 'core/editor' ).getCurrentPostType(),
	preloadedAuthorOptions: authorshipData,
	hasAssignAuthorAction: Boolean( get(
		select( 'core/editor' ).getCurrentPost(),
		[ '_links', 'authorship:action-assign-authorship' ],
		false
	) ),
} );

export default compose( [
	withDispatch( mapDispatchToProps ),
	withSelect( mapSelectToProps ),
] )( AuthorsSelect );

import React, { ReactElement, useEffect, useRef, useState } from 'react';
import type { MultiValue, StylesConfig } from 'react-select';
import type {
	WP_REST_API_Error,
	WP_REST_API_User,
} from 'wp-types';

import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

import { authorshipDataFromWP, Option, SortedOption } from '../types';
import arrayMove from '../utils/arrayMove';

import SortableSelectContainer from './SortableSelectContainer';

declare const authorshipData: authorshipDataFromWP;

interface AuthorsSelectProps {
	currentAuthorIDs: number[],
	hasAssignAuthorAction: boolean,
	onError: ( message: string ) => void,
	onUpdate: ( value: number[] ) => void,
	postType: string,
	preloadedAuthorOptions: authorshipDataFromWP,
}

interface EditorStore {
	getCurrentPost?: () => {
		_links?: Record<string, unknown>,
	} | undefined,
	getCurrentPostType?: () => string,
	getEditedPostAttribute?: ( attribute: string ) => unknown,
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

/**
 * Compares author ID arrays while preserving order semantics.
 *
 * @param {number[]} left The first ID array.
 * @param {number[]} right The second ID array.
 * @returns {boolean} Whether both arrays are equal.
 */
const areAuthorIDsEqual = ( left: number[], right: number[] ): boolean => {
	if ( left.length !== right.length ) {
		return false;
	}

	return left.every( ( value, index ) => value === right[ index ] );
};

/**
 * Returns the author selector control.
 *
 * @param {AuthorsSelectProps} props Component props.
 * @returns {ReactElement} An element.
 */
export const AuthorsSelectBase = ( props: AuthorsSelectProps ): ReactElement => {
	const { currentAuthorIDs, hasAssignAuthorAction, onError, onUpdate, postType, preloadedAuthorOptions } = props;

	const isDisabled = ! hasAssignAuthorAction;

	const [ selected, setSelected ] = useState<Option[]>( [] );
	const [ liveMessage, setLiveMessage ] = useState<string>( '' );
	const hasInitializedSelection = useRef<boolean>( false );

	useEffect( () => {
		if ( hasInitializedSelection.current || selected.length ) {
			return;
		}

		const preloadedAuthorIDs = preloadedAuthorOptions.authors.map( author => author.value );

		if ( areAuthorIDsEqual( preloadedAuthorIDs, currentAuthorIDs ) ) {
			setSelected( preloadedAuthorOptions.authors );
			hasInitializedSelection.current = true;
			return;
		}

		if ( currentAuthorIDs !== undefined && currentAuthorIDs.length ) {
			hasInitializedSelection.current = true;

			const path = addQueryArgs(
				'/authorship/v1/users/',
				{
					include: currentAuthorIDs,
					orderby: 'include',
					post_type: postType,
				}
			);

			let isCancelled = false;
			const api: Promise<WP_REST_API_User[]> = apiFetch( { path } );

			api.then( users => {
				if ( isCancelled ) {
					return;
				}

				setSelected( users.map( createOption ) );
			} ).catch( ( error: WP_REST_API_Error ) => {
				if ( isCancelled ) {
					return;
				}

				onError( error.message );
			} );

			return () => {
				isCancelled = true;
			};
		}

		hasInitializedSelection.current = true;
	}, [
		currentAuthorIDs,
		onError,
		postType,
		preloadedAuthorOptions.authors,
		selected.length,
	] );

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
	const styles: StylesConfig<Option, true> = {
		input: () => ( {
			margin: 0,
			width: '100%',
		} ),
	};

	/**
	 * Handles changes to the selected authors.
	 *
	 * @param {MultiValue<Option> | null} options The selected options.
	 */
	const changeValue = ( options: MultiValue<Option> | null ) => {
		const normalized = options ? [ ...options ] : [];
		const normalizedIDs = normalized.map( option => option.value );
		const selectedIDs = selected.map( option => option.value );

		if ( ! areAuthorIDsEqual( normalizedIDs, selectedIDs ) ) {
			if ( normalized.length === 0 ) {
				setLiveMessage( __( 'Author selection cleared.', 'authorship' ) );
			} else if ( normalized.length > selected.length ) {
				const added = normalized.find( option => ! selectedIDs.includes( option.value ) );
				setLiveMessage(
					added
						? sprintf(
							/* translators: %s: selected author name. */
							__( 'Added author %s.', 'authorship' ),
							added.label
						)
						: __( 'Author selection updated.', 'authorship' )
				);
			} else if ( normalized.length < selected.length ) {
				const removed = selected.find( option => ! normalizedIDs.includes( option.value ) );
				setLiveMessage(
					removed
						? sprintf(
							/* translators: %s: removed author name. */
							__( 'Removed author %s.', 'authorship' ),
							removed.label
						)
						: __( 'Author selection updated.', 'authorship' )
				);
			}
		}

		setSelected( normalized );
		onUpdate( normalizedIDs );
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
			setLiveMessage(
				sprintf(
					/* translators: %s: guest author name. */
					__( 'Added guest author %s.', 'authorship' ),
					user.name
				)
			);
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
		const movedOption = selected[ option.oldIndex ];
		const value = arrayMove( selected, option.oldIndex, option.newIndex );

		if ( movedOption ) {
			setLiveMessage(
				sprintf(
					/* translators: 1: author name, 2: list position. */
					__( 'Moved %1$s to position %2$d.', 'authorship' ),
					movedOption.label,
					option.newIndex + 1
				)
			);
		}

		setSelected( value );
		onUpdate( value.map( option => option.value ) );
	};

	return (
		<>
			<span aria-atomic="true" aria-live="polite" className="screen-reader-text">
				{ liveMessage }
			</span>
			<SortableSelectContainer
				isDisabled={ isDisabled }
				loadOptions={ loadOptions }
				styles={ styles }
				value={ selected }
				onChange={ changeValue }
				onCreateOption={ onCreateOption }
				onSortEnd={ onSortEnd }
			/>
		</>
	);
};

/**
 * Returns connected editor data for the AuthorsSelect component.
 *
 * @param {CallableFunction} select Data select callback.
 * @returns {Record<string, unknown>} Connected editor data.
 */
const selectConnectedProps = ( select: CallableFunction ): Record<string, unknown> => {
	const editorStore = select( 'core/editor' ) as EditorStore | null;

	if ( ! editorStore ) {
		return {
			currentAuthorIDs: [],
			hasAssignAuthorAction: false,
			postType: 'post',
		};
	}

	const currentPost = editorStore.getCurrentPost?.();
	const currentAuthorIDs = editorStore.getEditedPostAttribute?.( 'authorship' );

	return {
		currentAuthorIDs: Array.isArray( currentAuthorIDs ) ? currentAuthorIDs : [],
		hasAssignAuthorAction: Boolean( currentPost?._links?.['authorship:action-assign-authorship'] ),
		postType: editorStore.getCurrentPostType?.() || 'post',
	};
};

const AuthorsSelect = (): ReactElement => {
	const { createErrorNotice } = useDispatch( 'core/notices' ) as {
		createErrorNotice: ( message: string ) => void,
	};
	const { editPost } = useDispatch( 'core/editor' ) as {
		editPost: ( value: Record<string, unknown> ) => void,
	};
	const {
		currentAuthorIDs,
		hasAssignAuthorAction,
		postType,
	} = useSelect( selectConnectedProps, [] ) as {
		currentAuthorIDs: number[],
		hasAssignAuthorAction: boolean,
		postType: string,
	};

	return (
		<AuthorsSelectBase
			currentAuthorIDs={ currentAuthorIDs }
			hasAssignAuthorAction={ hasAssignAuthorAction }
			postType={ postType }
			preloadedAuthorOptions={ authorshipData }
			onError={ createErrorNotice }
			onUpdate={ value => editPost( { authorship: value } ) }
		/>
	);
};

export default AuthorsSelect;

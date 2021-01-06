import * as React from 'react';
import { ActionMeta, components } from 'react-select';
import AsyncCreatableSelect from 'react-select/async-creatable';
import { SortableContainer, SortableElement } from 'react-sortable-hoc';
import type {
	WP_REST_API_Error,
	WP_REST_API_User,
} from 'wp-types';

import { addQueryArgs } from '@wordpress/url';

import arrayMove from '../utils/arrayMove';

declare const authorshipData: authorshipDataFromWP;
declare const wp: any;

/**
 * Returns the author selector control.
 *
 * @param {object} args Arguments.
 * @returns {JSX.Element} An element.
 */
const AuthorsSelect = args => {
	const currentAuthors = authorshipData.authors;
	const { hasAssignAuthorAction, onUpdate, onError } = args;

	const [ selected, setSelected ] = React.useState( currentAuthors );
	const isDisabled = ! hasAssignAuthorAction;

	/**
	 * Asynchronously loads the options for the control based on the search paramter.
	 *
	 * @param {string} search The search string.
	 * @returns {Promise<Option[]>} A promise that fulfils the options.
	 */
	const loadOptions = ( search: string ) : Promise<Option[]> => {
		const path = addQueryArgs(
			'/authorship/v1/users/',
			{
				search,
			}
		);

		const api: Promise<WP_REST_API_User[]> = wp.apiFetch( { path } );

		return api.then( users =>
			users.map( createOption )
		).catch( ( error: WP_REST_API_Error ) => {
			onError( error.message );
			return [];
		} );
	};

	/**
	 * Creates an option from a REST API user response.
	 *
	 * @param {WP_REST_API_User} user The user object.
	 * @returns {Option} The option object.
	 */
	const createOption = ( user: WP_REST_API_User ): Option => ( {
		value: user.id,
		label: user.name,
		avatar: user.avatar_urls ? user.avatar_urls[48] : null,
	} );

	/**
	 * Overrides the default option display with our custom one.
	 *
	 * @param {Option} option The option data.
	 * @returns {JSX.Element} The element.
	 */
	const formatOptionLabel = ( option: Option ) => (
		<div style={ {
			display: 'flex',
			alignItems: 'center',
		} }>
			{ option.avatar && (
				<div style={ {
					flex: '0 0 24px',
					marginRight: '5px',
				} }>
					<img alt="" src={ option.avatar } style={ {
						width: '24px',
						height: '24px',
					} } />
				</div>
			) }
			<div>{ option.label }</div>
		</div>
	);

	const SortableMultiValueElement = SortableElement( props => {
		// This prevents the menu from being opened/closed when the user clicks
		// on a value to begin dragging it.
		const innerProps = {
			/**
			 * Stops event propagation when sorting options.
			 *
			 * @param {Event} e The event.
			 */
			onMouseDown: ( e: Event ) => {
				e.preventDefault();
				e.stopPropagation();
			},
		};
		return <components.MultiValue { ...props } innerProps={ innerProps } />;
	} );

	/**
	 * Handles changes to the selected authors.
	 *
	 * @param {Option[]|null} options The selected options.
	 * @param {ActionMeta}    action  The action performed that triggered the value change.
	 */
	const changeValue = ( options: Option[]|null, action: ActionMeta<any> ) => {
		setSelected( options || [] );
		onUpdate( options ? ( options.map( option => option.value ) ) : [] );
	};

	/**
	 * Handles the creation of a new option.
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

		const api: Promise<WP_REST_API_User> = wp.apiFetch( { path, method: 'POST' } );

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

	/**
	 * Returns the base author selector control.
	 *
	 * @returns {JSX.Element} An element.
	 */
	const Select = () => (
		<AsyncCreatableSelect
			cacheOptions
			className="authorship-select-container"
			classNamePrefix="authorship-select"
			components={ {
				MultiValue: SortableMultiValueElement,
			} }
			defaultValue={ currentAuthors }
			formatOptionLabel={ formatOptionLabel }
			isClearable={ false }
			isDisabled={ isDisabled }
			isMulti
			isValidNewOption={ ( value: string ) => value.length >= 2 }
			loadOptions={ loadOptions }
			value={ selected }
			onChange={ changeValue }
			onCreateOption={ onCreateOption }
		/>
	);

	const SortableSelectContainer = SortableContainer( Select );

	return (
		<SortableSelectContainer
			axis="y"
			distance={ 4 }
			helperContainer={ () => document.getElementsByClassName( 'authorship-select-container' )[0] as HTMLElement }
			lockAxis="y"
			lockToContainerEdges
			onSortEnd={ onSortEnd }
		/>
	);
};

export default AuthorsSelect;

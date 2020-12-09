import * as React from 'react';
import { ActionMeta, components } from 'react-select';
import AsyncCreatableSelect from 'react-select/async-creatable';
import { SortableContainer, SortableElement } from 'react-sortable-hoc';
import type { WP_REST_API_User as User } from 'wp-types';

import { addQueryArgs } from '@wordpress/url';

interface Option {
	/**
	 * The option value.
	 */
	value: number;
	/**
	 * The option label.
	 */
	label: string;
	/**
	 * The option avatar.
	 */
	avatar: string | null;
}

interface SortedOption {
	/**
	 * The old index position.
	 */
	oldIndex: number;
	/**
	 * The new index position.
	 */
	newIndex: number;
}

interface authorshipDataFromWP {
	authors: Option[];
}

declare const authorshipData: authorshipDataFromWP;
declare const wp;

/**
 * Moves an element in array from one position to another. Used as the sorting callback.
 *
 * @template T
 * @param {T[]}    array The affected array.
 * @param {number} from  The position of the element to move.
 * @param {number} to    The new position for the element.
 * @returns {T[]} The updated array.
 */
function arrayMove<T>( array: T[], from: number, to: number ) : T[] {
	array = array.slice();
	array.splice( to < 0 ? array.length + to : to, 0, array.splice( from, 1 )[0] );

	return array;
}

/**
 * Returns the author selector control.
 *
 * @param {object} args Arguments.
 * @returns {JSX.Element} An element.
 */
const AuthorsSelect = args => {
	const currentAuthors = authorshipData.authors;
	const { onUpdate } = args;

	const [ selected, setSelected ] = React.useState( currentAuthors );

	/**
	 * Asynchronously loads the options for the control based on the search paramter.
	 *
	 * @param {string} search The search string.
	 * @returns {Promise<Option[]>} A promise that fulfils the options.
	 */
	const loadOptions = ( search: string ) => {
		const path = addQueryArgs(
			'/wp/v2/users/',
			{
				search,
			}
		);

		const api: Promise<User[]> = wp.apiFetch( { path } );

		return api.then( users =>
			users.map( user => {
				const option: Option = {
					value: user.id,
					label: user.name,
					avatar: user.avatar_urls ? user.avatar_urls[48] : null,
				};

				return option;
			} )
		);
	};

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
		setSelected( options );
		onUpdate( options ? ( options.map( option => option.value ) ) : [] );
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
			isMulti
			isValidNewOption={ ( value: string ) => value.length >= 2 }
			loadOptions={ loadOptions }
			value={ selected }
			onChange={ changeValue }
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

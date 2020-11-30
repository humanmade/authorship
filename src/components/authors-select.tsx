import * as React from 'react';
import { ActionMeta } from 'react-select';
import AsyncCreatableSelect from 'react-select/async-creatable';
import type { WP_REST_API_User as User } from 'wp-types';

import { PluginPostStatusInfo } from '@wordpress/edit-post';
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
	avatar: string;
}

interface authorshipDataFromWP {
	authors: Option[];
}

declare const authorshipData: authorshipDataFromWP;
declare const wp;

/**
 * Renders the author selector control.
 *
 * @param {object} args Arguments.
 * @returns {JSX.Element} An element.
 */
const AuthorsSelect = args => {
	const currentAuthors = authorshipData.authors;
	const { onUpdate } = args;

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
				who: 'authors',
			}
		);

		const api: Promise<User[]> = wp.apiFetch( { path } );

		return api.then( users =>
			users.map( user => {
				const option: Option = {
					value: user.id,
					label: user.name,
					avatar: user.avatar_urls[48] ?? null,
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

	/**
	 * Handles changes to the selected authors.
	 *
	 * @param {Option[]|null} options The selected options.
	 * @param {ActionMeta}    action  The action performed that triggered the value change.
	 */
	const changeValue = ( options: Option[]|null, action: ActionMeta<any> ) => {
		onUpdate( options ? ( options.map( option => option.value ) ) : [] );
	};

	return (
		<PluginPostStatusInfo>
			<AsyncCreatableSelect
				cacheOptions
				className="authorship-select-container"
				classNamePrefix="authorship-select"
				defaultValue={ currentAuthors }
				formatOptionLabel={ formatOptionLabel }
				isClearable={ false }
				isMulti
				isValidNewOption={ ( value: string ) => value.length >= 2 }
				loadOptions={ loadOptions }
				onChange={ changeValue }
			/>
		</PluginPostStatusInfo>
	);
};

export default AuthorsSelect;

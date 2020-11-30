import * as React from 'react';
import AsyncCreatableSelect from 'react-select/async-creatable';
import type { WP_REST_API_User as User } from 'wp-types';

import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { addQueryArgs } from '@wordpress/url';

declare const wp;

const registerPlugin = wp.plugins.registerPlugin;

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

/**
 * Renders the author selector control.
 *
 * @returns {JSX.Element} An element.
 */
const AuthorsSelect = () => {
	const currentAuthors: Option[] = [
	];

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
		<div style={ { display: 'flex' } }>
			{ option.avatar && (
				<div style={ {
					flex: '0 0 24px',
					marginRight: '5px',
					alignContent: 'center',
				} }>
					<img alt="" src={ option.avatar } />
				</div>
			) }
			<div>{ option.label }</div>
		</div>
	);

	return (
		<PluginPostStatusInfo>
			<AsyncCreatableSelect
				cacheOptions
				className="authorship-select-container"
				classNamePrefix="authorship-select"
				defaultOptions={ currentAuthors }
				formatOptionLabel={ formatOptionLabel }
				isClearable={ false }
				isMulti
				isValidNewOption={ ( value: string ) => value.length >= 2 }
				loadOptions={ loadOptions }
			/>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'authorship', {
	render: AuthorsSelect,
} );

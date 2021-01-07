import React, { ReactElement } from 'react';

import { PluginPostStatusInfo } from '@wordpress/edit-post';

import AuthorsSelect from './components/authors-select';

export const name = 'authorship';

export const settings = {
	icon: null,
	render(): ReactElement {
		return (
			<PluginPostStatusInfo>
				<AuthorsSelect />
			</PluginPostStatusInfo>
		);
	},
};

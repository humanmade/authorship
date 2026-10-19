import { ReactElement } from 'react';

import { PluginPostStatusInfo } from '@wordpress/editor';

import AuthorsSelect from './components/AuthorsSelect';

export const name = 'authorship';

export const settings = {
	render(): ReactElement {
		return (
			<PluginPostStatusInfo>
				<AuthorsSelect />
			</PluginPostStatusInfo>
		);
	},
};

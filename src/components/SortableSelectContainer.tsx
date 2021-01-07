import React, { ReactElement } from 'react';
import AsyncCreatableSelect, { Props as AsyncCreatableSelectProps } from 'react-select/async-creatable';
import { SortableContainer } from 'react-sortable-hoc';

import { Option } from '../types';

import SortableMultiValueElement from './SortableMultiValueElement';

const components = {
	MultiValue: SortableMultiValueElement,
};

const isValidNewOption = ( value: string ) => value.length >= 2;

export const className = 'authorship-select-container';

/**
 * Overrides the default option display with our custom one.
 *
 * @param {Option} option The option data.
 * @returns {ReactElement} The element.
 */
const formatOptionLabel = ( option: Option ): ReactElement => (
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
 * Returns the base author selector control.
 *
 * @param {AsyncCreatableSelectProps} props Component props.
 * @returns {ReactElement} An element.
 */
const Select = ( props: AsyncCreatableSelectProps<Option, true> ): ReactElement => (
	<AsyncCreatableSelect
		cacheOptions
		className={ className }
		classNamePrefix="authorship-select"
		components={ components }
		formatOptionLabel={ formatOptionLabel }
		isClearable={ false }
		isMulti
		isValidNewOption={ isValidNewOption }
		{ ...props }
	/>
);

export { Select };

export default SortableContainer( Select );

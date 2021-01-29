import React, { ReactElement } from 'react';
import AsyncCreatableSelect, { Props as AsyncCreatableSelectProps } from 'react-select/async-creatable';
import { SortableContainer } from 'react-sortable-hoc';

import { __ } from '@wordpress/i18n';

import { Option } from '../types';

import SortableMultiValueElement from './SortableMultiValueElement';

const components = {
	MultiValue: SortableMultiValueElement,
};

const isValidNewOption = ( value: string ) => value.length >= 2;

const placeholder = __( 'Select authors…', 'authorship' );

export const className = 'authorship-select-container';
export const classNamePrefix = 'authorship-select';

/**
 * Overrides the default option display with our custom one.
 *
 * @param {Option} option The option data.
 * @returns {ReactElement} The element.
 */
const formatOptionLabel = ( option: Option ): ReactElement => (
	<>
		{ option.avatar && (
			<div className={ `${classNamePrefix}__multi-value__avatar` }>
				<img alt="" src={ option.avatar }/>
			</div>
		) }
		<div>{ option.label }</div>
	</>
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
		classNamePrefix={ classNamePrefix }
		components={ components }
		formatOptionLabel={ formatOptionLabel }
		isClearable={ false }
		isMulti
		isValidNewOption={ isValidNewOption }
		placeholder={ placeholder }
		{ ...props }
	/>
);

export { Select };

export default SortableContainer( Select );

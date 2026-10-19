import React from 'react';
import type { ReactElement, ComponentProps } from 'react';
import {
	DndContext,
	closestCenter,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
	SortableContext,
	sortableKeyboardCoordinates,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import type { AsyncProps } from 'react-select/async';
import AsyncCreatableSelect from 'react-select/async-creatable';

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
 * @param {ComponentProps<typeof AsyncCreatableSelect>} props Component props.
 * @returns {ReactElement} An element.
 */
const Select = ( props: ComponentProps<typeof AsyncCreatableSelect> ): ReactElement => (
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

interface SortableSelectContainerProps extends AsyncProps<Option, true, never> {
	onSortEnd?: ( sort: { oldIndex: number; newIndex: number } ) => void;
	onCreateOption?: ( inputValue: string ) => void;
	isValidNewOption?: ( inputValue: string ) => boolean;
}

/**
 * Returns the sortable author selector control.
 *
 * @param {SortableSelectContainerProps} props Component props.
 * @returns {ReactElement} An element.
 */
const SortableSelectContainer = ( props: SortableSelectContainerProps ): ReactElement => {
	const { value: propValue = [], onSortEnd, onChange, isLoading, ...restProps } = props;
	const value = Array.isArray(propValue) ? propValue : [];

	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 4,
			},
		} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	const handleDragEnd = ( event: DragEndEvent ) => {
		const { active, over } = event;

		if ( active && over && active.id !== over.id ) {
			const oldIndex = value.findIndex( ( item: Option ) => item.value === Number( active.id ) );
			const newIndex = value.findIndex( ( item: Option ) => item.value === Number( over.id ) );

			if ( onSortEnd ) {
				onSortEnd( {
					oldIndex,
					newIndex,
				} );
			}
		}
	};

	const items = value.map( ( item: Option ) => item.value );

	return (
		<DndContext
			collisionDetection={ closestCenter }
			sensors={ sensors }
			onDragEnd={ handleDragEnd }
		>
			<SortableContext
				items={ items }
				strategy={ verticalListSortingStrategy }
			>
				<Select
					value={ propValue }
					onChange={ onChange }
					{ ...restProps }
				/>
			</SortableContext>
		</DndContext>
	);
};

export { Select };

export default SortableSelectContainer;

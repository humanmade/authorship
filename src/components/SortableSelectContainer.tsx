import { DndContext, DragEndEvent, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import React, { ReactElement } from 'react';
import type { GroupBase } from 'react-select';
import AsyncCreatableSelect, { AsyncCreatableProps } from 'react-select/async-creatable';

import { __ } from '@wordpress/i18n';

import { Option, SortedOption } from '../types';

import SortableMultiValueElement from './SortableMultiValueElement';

const components = {
	MultiValue: SortableMultiValueElement,
};

const isValidNewOption = ( value: string ) => value.length >= 2;

const placeholder = __( 'Select authors…', 'authorship' );

export const className = 'authorship-select-container';
export const classNamePrefix = 'authorship-select';

interface SortableSelectProps extends AsyncCreatableProps<Option, true, GroupBase<Option>> {
	onSortEnd: ( option: SortedOption ) => void;
}

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
 * @param {SortableSelectProps} props Component props.
 * @returns {ReactElement} An element.
 */
const Select = ( props: SortableSelectProps ): ReactElement => {
	const { onSortEnd, value, ...selectProps } = props;
	const selectedOptions = Array.isArray( value ) ? value : [];
	const selectedIDs = selectedOptions.map( option => option.value );
	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 4,
			},
		} )
	);

	/**
	 * Emits `onSortEnd` callback payload that matches the legacy shape.
	 *
	 * @param {DragEndEvent} event Drag-end event data.
	 */
	const handleDragEnd = ( event: DragEndEvent ) => {
		const { active, over } = event;

		if ( ! over || active.id === over.id ) {
			return;
		}

		const oldIndex = selectedIDs.indexOf( Number( active.id ) );
		const newIndex = selectedIDs.indexOf( Number( over.id ) );

		if ( oldIndex < 0 || newIndex < 0 ) {
			return;
		}

		onSortEnd( {
			oldIndex,
			newIndex,
		} );
	};

	return (
		<DndContext collisionDetection={ closestCenter } sensors={ sensors } onDragEnd={ handleDragEnd }>
			<SortableContext items={ selectedIDs } strategy={ verticalListSortingStrategy }>
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
					value={ selectedOptions }
					{ ...selectProps }
				/>
			</SortableContext>
		</DndContext>
	);
};

export { Select };

export default Select;

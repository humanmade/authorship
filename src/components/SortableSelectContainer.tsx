import { DndContext, DragEndEvent, KeyboardSensor, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import React, { ReactElement } from 'react';
import type { GroupBase } from 'react-select';
import AsyncCreatableSelect, { AsyncCreatableProps } from 'react-select/async-creatable';

import { __, sprintf } from '@wordpress/i18n';

import { Option, SortedOption } from '../types';

import SortableMultiValueElement from './SortableMultiValueElement';

const components = {
	MultiValue: SortableMultiValueElement,
};

const isValidNewOption = ( value: string ) => value.length >= 2;

const placeholder = __( 'Select authors…', 'authorship' );
const ariaLabel = __( 'Authors', 'authorship' );
const helpTextID = 'authorship-select-help';
const helpText = __( 'Use the authors field to assign one or more authors. Use drag and drop or keyboard reordering to change author order.', 'authorship' );

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
		} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	const getOptionLabel = ( id: number | string ): string => {
		const selected = selectedOptions.find( option => option.value === Number( id ) );

		return selected?.label || __( 'author', 'authorship' );
	};

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
		<>
			<span className="screen-reader-text" id={ helpTextID }>
				{ helpText }
			</span>
			<DndContext
				accessibility={ {
					announcements: {
						onDragStart: ( { active }: { active: { id: number | string } } ) => sprintf(
							/* translators: %s: selected author label. */
							__( 'Picked up %s for reordering.', 'authorship' ),
							getOptionLabel( active.id )
						),
						onDragEnd: ( { active, over }: { active: { id: number | string }, over: { id: number | string } | null } ) => {
							if ( ! over ) {
								return __( 'Author reordering cancelled.', 'authorship' );
							}

							const newIndex = selectedIDs.indexOf( Number( over.id ) );

							if ( newIndex < 0 ) {
								return __( 'Author reordering cancelled.', 'authorship' );
							}

							return sprintf(
								/* translators: 1: selected author label, 2: position number, 3: total selected authors. */
								__( 'Moved %1$s to position %2$d of %3$d.', 'authorship' ),
								getOptionLabel( active.id ),
								newIndex + 1,
								selectedIDs.length
							);
						},
						onDragCancel: () => __( 'Author reordering cancelled.', 'authorship' ),
					},
				} }
				collisionDetection={ closestCenter }
				sensors={ sensors }
				onDragEnd={ handleDragEnd }
			>
				<SortableContext items={ selectedIDs } strategy={ verticalListSortingStrategy }>
					<AsyncCreatableSelect
						aria-describedby={ helpTextID }
						aria-label={ ariaLabel }
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
		</>
	);
};

export { Select };

export default Select;

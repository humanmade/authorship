import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import React, { CSSProperties, ReactElement } from 'react';
import { components, MultiValueProps } from 'react-select';

import { Option } from '../types';

const { MultiValue } = components;

const MultiValueElement = ( props: MultiValueProps<Option> ): ReactElement => {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable( {
		id: props.data.value,
	} );

	const style: CSSProperties = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	// This prevents the menu from being opened/closed when the user clicks
	// on a value to begin dragging it.
	const innerProps = {
		...props.innerProps,
		/**
		 * Stops event propagation when sorting options.
		 *
		 * @param {React.MouseEvent<HTMLDivElement>} e The event.
		 */
		onMouseDown( e: React.MouseEvent<HTMLDivElement> ) {
			e.preventDefault();
			e.stopPropagation();
		},
	};

	return (
		<div { ...attributes } { ...listeners } ref={ setNodeRef } style={ style }>
			<MultiValue { ...props } innerProps={ innerProps } />
		</div>
	);
};

export { MultiValueElement };

export default MultiValueElement;

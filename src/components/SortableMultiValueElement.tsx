import * as React from 'react';
import { components } from 'react-select';
import { SortableElement } from 'react-sortable-hoc';

export default SortableElement( props => {
	// This prevents the menu from being opened/closed when the user clicks
	// on a value to begin dragging it.
	const innerProps = {
		/**
		 * Stops event propagation when sorting options.
		 *
		 * @param {Event} e The event.
		 */
		onMouseDown: ( e: Event ) => {
			e.preventDefault();
			e.stopPropagation();
		},
	};
	return <components.MultiValue { ...props } innerProps={ innerProps } />;
} );

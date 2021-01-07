import React, { ReactElement } from 'react';
import { components, MultiValueProps } from 'react-select';
import { SortableElement } from 'react-sortable-hoc';

const { MultiValue } = components;

const MultiValueElement = ( props: MultiValueProps<Option> ): ReactElement => {
	// This prevents the menu from being opened/closed when the user clicks
	// on a value to begin dragging it.
	const innerProps = {
		/**
		 * Stops event propagation when sorting options.
		 *
		 * @param {Event} e The event.
		 */
		onMouseDown( e: Event ) {
			e.preventDefault();
			e.stopPropagation();
		},
	};
	return <MultiValue { ...props } innerProps={ innerProps } />;
};

export { MultiValueElement };

export default SortableElement( MultiValueElement );

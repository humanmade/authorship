import React, { ReactElement } from 'react';
import type { MultiValueProps } from 'react-select';
import { components } from 'react-select';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { Option } from '../types';

const MultiValueElement = ( props: MultiValueProps<Option> ): ReactElement => {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: props.data.value } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			{...attributes}
			{...listeners}
			ref={setNodeRef}
			style={style}
			className="authorship-select__multi-value"
			onMouseDown={e => {
				e.preventDefault();
				e.stopPropagation();
			}}
			{...props.innerProps}
		>
			<components.MultiValue {...props} />
		</div>
	);
};

export { MultiValueElement };

export default MultiValueElement;

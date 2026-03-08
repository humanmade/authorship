import { render } from '@testing-library/react';
import React from 'react';

let capturedDragEnd: CallableFunction | undefined;

jest.mock( '../../../src/components/SortableMultiValueElement', () => ( {
	__esModule: true,
	default: () => null,
} ) );

jest.mock( 'react-select/async-creatable', () => ( {
	__esModule: true,
	default: () => null,
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

jest.mock( '@dnd-kit/core', () => ( {
	DndContext: ( {
		children,
		onDragEnd,
	}: {
		children: React.ReactNode,
		onDragEnd: CallableFunction,
	} ) => {
		capturedDragEnd = onDragEnd;
		return <>{ children }</>;
	},
	PointerSensor: jest.fn(),
	closestCenter: jest.fn(),
	useSensor: jest.fn( () => ( {} ) ),
	useSensors: jest.fn( () => [] ),
} ) );

jest.mock( '@dnd-kit/sortable', () => ( {
	SortableContext: ( { children }: { children: React.ReactNode } ) => <>{ children }</>,
	verticalListSortingStrategy: jest.fn(),
} ) );

const { Select } = require( '../../../src/components/SortableSelectContainer' );

describe( 'SortableSelectContainer', () => {
	beforeEach( () => {
		capturedDragEnd = undefined;
	} );

	it( 'emits legacy sort indexes from dnd-kit drag events', () => {
		const onSortEnd = jest.fn();

		render(
			<Select
				loadOptions={ jest.fn() }
				value={ [
					{
						value: 11,
						label: 'Author A',
						avatar: null,
					},
					{
						value: 22,
						label: 'Author B',
						avatar: null,
					},
					{
						value: 33,
						label: 'Author C',
						avatar: null,
					},
				] }
				onChange={ jest.fn() }
				onSortEnd={ onSortEnd }
			/>
		);

		expect( capturedDragEnd ).toBeDefined();

		capturedDragEnd?.( {
			active: {
				id: 11,
			},
			over: {
				id: 33,
			},
		} );

		expect( onSortEnd ).toHaveBeenCalledWith( {
			oldIndex: 0,
			newIndex: 2,
		} );
	} );

	it( 'ignores drag events without a drop target', () => {
		const onSortEnd = jest.fn();

		render(
			<Select
				loadOptions={ jest.fn() }
				value={ [
					{
						value: 11,
						label: 'Author A',
						avatar: null,
					},
					{
						value: 22,
						label: 'Author B',
						avatar: null,
					},
				] }
				onChange={ jest.fn() }
				onSortEnd={ onSortEnd }
			/>
		);

		expect( capturedDragEnd ).toBeDefined();

		capturedDragEnd?.( {
			active: {
				id: 11,
			},
			over: null,
		} );

		expect( onSortEnd ).not.toHaveBeenCalled();
	} );
} );

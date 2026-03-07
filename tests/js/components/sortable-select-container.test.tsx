import { render, screen } from '@testing-library/react';
import React from 'react';

let capturedDragEnd: CallableFunction | undefined;
let capturedAccessibility: Record<string, unknown> = {};
let capturedSelectProps: Record<string, unknown> = {};

jest.mock( '../../../src/components/SortableMultiValueElement', () => ( {
	__esModule: true,
	default: () => null,
} ) );

jest.mock( 'react-select/async-creatable', () => ( {
	__esModule: true,
	default: ( props: Record<string, unknown> ) => {
		capturedSelectProps = props;
		return null;
	},
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	sprintf: ( text: string, ...args: Array<string | number> ) => {
		let output = text;
		args.forEach( ( arg, index ) => {
			output = output.replace( `%${ index + 1 }$s`, String( arg ) );
			output = output.replace( `%${ index + 1 }$d`, String( arg ) );
			output = output.replace( '%s', String( arg ) );
			output = output.replace( '%d', String( arg ) );
		} );

		return output;
	},
} ) );

jest.mock( '@dnd-kit/core', () => ( {
	DndContext: ( {
		children,
		onDragEnd,
		accessibility,
	}: {
		children: React.ReactNode,
		onDragEnd: CallableFunction,
		accessibility: Record<string, unknown>,
	} ) => {
		capturedDragEnd = onDragEnd;
		capturedAccessibility = accessibility;
		return <>{ children }</>;
	},
	PointerSensor: jest.fn(),
	KeyboardSensor: jest.fn(),
	closestCenter: jest.fn(),
	useSensor: jest.fn( ( sensor: unknown, options: unknown ) => ( {
		sensor,
		options,
	} ) ),
	useSensors: jest.fn( ( ...sensors: unknown[] ) => sensors ),
} ) );

jest.mock( '@dnd-kit/sortable', () => ( {
	SortableContext: ( { children }: { children: React.ReactNode } ) => <>{ children }</>,
	verticalListSortingStrategy: jest.fn(),
	sortableKeyboardCoordinates: jest.fn(),
} ) );

const { Select } = require( '../../../src/components/SortableSelectContainer' );

describe( 'SortableSelectContainer', () => {
	beforeEach( () => {
		capturedDragEnd = undefined;
		capturedAccessibility = {};
		capturedSelectProps = {};
		jest.clearAllMocks();
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

	it( 'configures pointer and keyboard sensors for drag interactions', () => {
		const dndKitCore = require( '@dnd-kit/core' );
		const dndKitSortable = require( '@dnd-kit/sortable' );

		render(
			<Select
				loadOptions={ jest.fn() }
				value={ [] }
				onChange={ jest.fn() }
				onSortEnd={ jest.fn() }
			/>
		);

		expect( dndKitCore.useSensor ).toHaveBeenCalledWith(
			dndKitCore.PointerSensor,
			{
				activationConstraint: {
					distance: 4,
				},
			}
		);
		expect( dndKitCore.useSensor ).toHaveBeenCalledWith(
			dndKitCore.KeyboardSensor,
			{
				coordinateGetter: dndKitSortable.sortableKeyboardCoordinates,
			}
		);
	} );

	it( 'passes explicit accessibility labels and instructions to the selector', () => {
		render(
			<Select
				loadOptions={ jest.fn() }
				value={ [] }
				onChange={ jest.fn() }
				onSortEnd={ jest.fn() }
			/>
		);

		expect( capturedSelectProps[ 'aria-label' ] ).toBe( 'Authors' );
		expect( capturedSelectProps[ 'aria-describedby' ] ).toBe( 'authorship-select-help' );
		expect( screen.getByText( /Use the authors field to assign one or more authors./i ) ).toBeTruthy();
	} );

	it( 'announces drag outcomes for assistive technology users', () => {
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
				onSortEnd={ jest.fn() }
			/>
		);

		const announcements = capturedAccessibility.announcements as {
			onDragEnd: ( event: Record<string, unknown> ) => string,
		};

		expect( announcements.onDragEnd( {
			active: { id: 11 },
			over: { id: 33 },
		} ) ).toContain( 'Moved Author A to position 3 of 3.' );
		expect( announcements.onDragOver( {
			over: { id: 22 },
		} ) ).toContain( 'Moving to position 2 of 3.' );
	} );
} );

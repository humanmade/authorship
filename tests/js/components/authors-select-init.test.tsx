import { act, render, waitFor } from '@testing-library/react';
import React from 'react';

const mockApiFetch = jest.fn();
let mockSortableProps: Record<string, unknown> = {};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: ( args: unknown ) => mockApiFetch( args ),
} ) );

jest.mock( '../../../src/components/SortableSelectContainer', () => ( {
	__esModule: true,
	className: 'authorship-select-container',
	default: ( props: Record<string, unknown> ) => {
		mockSortableProps = props;
		return null;
	},
} ) );

const { AuthorsSelectBase } = require( '../../../src/components/AuthorsSelect' );

const baseProps = {
	hasAssignAuthorAction: true,
	onError: jest.fn(),
	onUpdate: jest.fn(),
	postType: 'post',
};

describe( 'AuthorsSelect initialization', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockSortableProps = {};
	} );

	it( 'initializes from preloaded authors when IDs match', async () => {
		const preloaded = [
			{
				value: 9,
				label: 'Preloaded Author',
				avatar: null,
			},
		];

		render(
			<AuthorsSelectBase
				{ ...baseProps }
				currentAuthorIDs={ [ 9 ] }
				preloadedAuthorOptions={ {
					authors: preloaded,
				} }
			/>
		);

		await waitFor( () => {
			expect( mockSortableProps.value ).toEqual( preloaded );
		} );

		expect( mockApiFetch ).not.toHaveBeenCalled();
	} );

	it( 'loads authors from REST when current IDs are not preloaded', async () => {
		mockApiFetch.mockResolvedValue( [
			{
				id: 7,
				name: 'Remote Author',
				avatar_urls: {
					48: 'https://example.com/avatar.jpg',
				},
			},
		] );

		render(
			<AuthorsSelectBase
				{ ...baseProps }
				currentAuthorIDs={ [ 7 ] }
				preloadedAuthorOptions={ { authors: [] } }
			/>
		);

		await waitFor( () => {
			expect( mockSortableProps.value ).toEqual( [
				{
					value: 7,
					label: 'Remote Author',
					avatar: 'https://example.com/avatar.jpg',
				},
			] );
		} );

		expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'emits reordered author IDs on sort end', async () => {
		const onUpdate = jest.fn();
		const preloaded = [
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
		];

		render(
			<AuthorsSelectBase
				{ ...baseProps }
				currentAuthorIDs={ [ 11, 22, 33 ] }
				preloadedAuthorOptions={ {
					authors: preloaded,
				} }
				onUpdate={ onUpdate }
			/>
		);

		await waitFor( () => {
			expect( mockSortableProps.value ).toEqual( preloaded );
		} );

		act( () => {
			( mockSortableProps.onSortEnd as CallableFunction )( {
				oldIndex: 0,
				newIndex: 2,
			} );
		} );

		expect( onUpdate ).toHaveBeenCalledWith( [ 22, 33, 11 ] );
	} );

	it( 'does not pass legacy sortable-hoc props to selector container', async () => {
		render(
			<AuthorsSelectBase
				{ ...baseProps }
				currentAuthorIDs={ [ 9 ] }
				preloadedAuthorOptions={ {
					authors: [
						{
							value: 9,
							label: 'Author',
							avatar: null,
						},
					],
				} }
			/>
		);

		await waitFor( () => {
			expect( mockSortableProps.value ).toEqual( [
				{
					value: 9,
					label: 'Author',
					avatar: null,
				},
			] );
		} );

		expect( mockSortableProps.axis ).toBeUndefined();
		expect( mockSortableProps.distance ).toBeUndefined();
		expect( mockSortableProps.helperContainer ).toBeUndefined();
		expect( mockSortableProps.lockAxis ).toBeUndefined();
		expect( mockSortableProps.lockToContainerEdges ).toBeUndefined();
	} );
} );

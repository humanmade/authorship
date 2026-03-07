import { render, waitFor } from '@testing-library/react';
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
} );

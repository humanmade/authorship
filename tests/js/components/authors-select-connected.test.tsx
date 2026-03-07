import { act, render, waitFor } from '@testing-library/react';
import React from 'react';

const mockUseDispatch = jest.fn();
const mockUseSelect = jest.fn();
const mockCreateErrorNotice = jest.fn();
const mockEditPost = jest.fn();
let mockSortableProps: Record<string, unknown> = {};

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: ( ...args: unknown[] ) => mockUseDispatch( ...args ),
	useSelect: ( ...args: unknown[] ) => mockUseSelect( ...args ),
} ) );

jest.mock( '../../../src/components/SortableSelectContainer', () => ( {
	__esModule: true,
	className: 'authorship-select-container',
	default: ( props: Record<string, unknown> ) => {
		mockSortableProps = props;
		return null;
	},
} ) );

const ConnectedAuthorsSelect = require( '../../../src/components/AuthorsSelect' ).default;

const setEditorSelect = ( {
	authorship = [ 9 ],
	hasAssignAuthorAction = true,
	postType = 'post',
}: {
	authorship?: number[],
	hasAssignAuthorAction?: boolean,
	postType?: string,
} = {} ) => {
	mockUseSelect.mockImplementation( ( mapSelect: CallableFunction ) => mapSelect( ( storeName: string ) => {
		if ( storeName !== 'core/editor' ) {
			return null;
		}

		return {
			getCurrentPost: () => ( hasAssignAuthorAction ? {
				_links: {
					'authorship:action-assign-authorship': [ {} ],
				},
			} : {} ),
			getCurrentPostType: () => postType,
			getEditedPostAttribute: () => authorship,
		};
	} ) );
};

describe( 'AuthorsSelect connected component', () => {
	beforeEach( () => {
		mockSortableProps = {};
		mockUseSelect.mockReset();
		mockUseDispatch.mockReset();
		mockCreateErrorNotice.mockReset();
		mockEditPost.mockReset();

		( global as { authorshipData: unknown } ).authorshipData = {
			authors: [
				{
					value: 9,
					label: 'Preloaded Author',
					avatar: null,
				},
			],
		};

		mockUseDispatch.mockImplementation( ( storeName: string ) => {
			if ( storeName === 'core/notices' ) {
				return {
					createErrorNotice: mockCreateErrorNotice,
				};
			}

			if ( storeName === 'core/editor' ) {
				return {
					editPost: mockEditPost,
				};
			}

			return {};
		} );
	} );

	it( 'maps hook data to props and dispatches editPost updates', async () => {
		setEditorSelect();

		render( <ConnectedAuthorsSelect /> );

		await waitFor( () => {
			expect( mockSortableProps.isDisabled ).toBe( false );
			expect( mockSortableProps.value ).toEqual( [
				{
					value: 9,
					label: 'Preloaded Author',
					avatar: null,
				},
			] );
		} );

		act( () => {
			( mockSortableProps.onChange as CallableFunction )( [
				{
					value: 9,
					label: 'Preloaded Author',
					avatar: null,
				},
				{
					value: 22,
					label: 'Second Author',
					avatar: null,
				},
			] );
		} );

		expect( mockEditPost ).toHaveBeenCalledWith( {
			authorship: [ 9, 22 ],
		} );
	} );

	it( 'disables selector when assign-authorship action link is missing', async () => {
		setEditorSelect( {
			hasAssignAuthorAction: false,
		} );

		render( <ConnectedAuthorsSelect /> );

		await waitFor( () => {
			expect( mockSortableProps.isDisabled ).toBe( true );
		} );
	} );
} );

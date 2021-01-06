/**
 * Moves an element in array from one position to another. Used as the sorting callback.
 *
 * @template T
 * @param {T[]}    array The affected array.
 * @param {number} from  The position of the element to move.
 * @param {number} to    The new position for the element.
 * @returns {T[]} The updated array.
 */
export default function arrayMove<T>( array: T[], from: number, to: number ) : T[] {
	array = array.slice();
	array.splice( to < 0 ? array.length + to : to, 0, array.splice( from, 1 )[0] );

	return array;
}

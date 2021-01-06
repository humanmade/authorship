interface Option {
	/**
	 * The option value.
	 */
	value: number;
	/**
	 * The option label.
	 */
	label: string;
	/**
	 * The option avatar.
	 */
	avatar: string | null;
}

interface SortedOption {
	/**
	 * The old index position.
	 */
	oldIndex: number;
	/**
	 * The new index position.
	 */
	newIndex: number;
}

interface authorshipDataFromWP {
	authors: Option[];
}

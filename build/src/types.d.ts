export interface Option {
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
export interface SortedOption {
    /**
     * The old index position.
     */
    oldIndex: number;
    /**
     * The new index position.
     */
    newIndex: number;
}
export interface authorshipDataFromWP {
    authors: Option[];
}
//# sourceMappingURL=types.d.ts.map
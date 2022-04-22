import React, { ReactElement } from 'react';
import { Props as AsyncCreatableSelectProps } from 'react-select/async-creatable';
import { Option } from '../types';
export declare const className = "authorship-select-container";
export declare const classNamePrefix = "authorship-select";
/**
 * Returns the base author selector control.
 *
 * @param {AsyncCreatableSelectProps} props Component props.
 * @returns {ReactElement} An element.
 */
declare const Select: (props: AsyncCreatableSelectProps<Option, true>) => ReactElement;
export { Select };
declare const _default: React.ComponentClass<import("react-select").Props<Option, true> & import("react-select/src/Async").AsyncProps<Option> & import("react-select/src/Creatable").CreatableProps<Option, true> & import("react-sortable-hoc").SortableContainerProps, any>;
export default _default;
//# sourceMappingURL=SortableSelectContainer.d.ts.map
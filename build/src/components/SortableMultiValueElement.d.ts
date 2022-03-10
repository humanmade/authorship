import React, { ReactElement } from 'react';
import { MultiValueProps } from 'react-select';
import { Option } from '../types';
declare const MultiValueElement: (props: MultiValueProps<Option>) => ReactElement;
export { MultiValueElement };
declare const _default: React.ComponentClass<import("react-select").CommonProps<Option, true> & {
    children: React.ReactNode;
    components: any;
    cropWithEllipsis: boolean;
    data: Option;
    innerProps: any;
    isFocused: boolean;
    isDisabled: boolean;
    removeProps: {
        onTouchEnd: (event: any) => void;
        onClick: (event: any) => void;
        onMouseDown: (event: any) => void;
    };
} & import("react-sortable-hoc").SortableElementProps, any>;
export default _default;
//# sourceMappingURL=SortableMultiValueElement.d.ts.map
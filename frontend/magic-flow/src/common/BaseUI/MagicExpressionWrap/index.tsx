/** Expression component with one more render layer
 * {
        "id": "component-66399f15d691c",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "input",
                    "value": "",
                    "name": "",
                    "args": null
                }
            ]
        }
    }
 */

import { InputExpressionProps, InputExpressionValue } from "@/MagicExpressionWidget/types"
import { MagicExpressionWidget } from "@/index"
import { useMemoizedFn } from "ahooks"
import React from "react"

export type WidgetExpressionValue = {
	id: string
	version: string
	type: string
	structure: InputExpressionValue
}

//@ts-ignore
interface MagicExpressionWrapProps extends InputExpressionProps {
	value?: WidgetExpressionValue
	onChange?: (value: WidgetExpressionValue) => void
}

function MagicExpressionWrap({ ...props }: MagicExpressionWrapProps) {
    // We only need to change the structure data; the outer wrapper is not required for now
	const onChange = useMemoizedFn((value: InputExpressionValue) => {
		if (!props.onChange || !props.value) return
		props.onChange({
			...props.value,
			structure: value,
		})
	})

	return <MagicExpressionWidget {...props} value={props?.value?.structure} onChange={onChange} />
}

export default MagicExpressionWrap as any

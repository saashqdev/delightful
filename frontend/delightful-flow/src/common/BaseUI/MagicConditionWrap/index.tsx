/** Condition component with an extra rendering layer
 * {
        "id": "component-66399f15d691c",
        "version": "1",
        "type": "value",
        "structure": null
    }
 */

import { CustomConditionContainerProps } from "@/DelightfulConditionEdit"
import { Expression } from "@/DelightfulConditionEdit/types/expression"
import { DelightfulConditionEdit } from "@/index"
import { useMemoizedFn } from "ahooks"
import React from "react"

export type WidgetConditionValue = {
	id: string
	version: string
	type: string
	structure: Expression.Condition
}

//@ts-ignore
interface DelightfulConditionWrapProps extends CustomConditionContainerProps {
	value?: WidgetConditionValue
	onChange?: (value: WidgetConditionValue) => void
}

export default function DelightfulConditionWrap({ ...props }: DelightfulConditionWrapProps) {
	// Outer wrapper not needed; only update the structure data
	const onChange = useMemoizedFn((value: Expression.Condition) => {
		if (!props.onChange || !props.value) return
		props.onChange({
			...props.value,
			structure: value,
		})
	})

	return <DelightfulConditionEdit {...props} value={props?.value?.structure} onChange={onChange} />
}

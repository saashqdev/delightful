/** Condition component with an additional rendering layer
 * {
        "id": "component-66399f15d691c",
        "version": "1",
        "type": "value",
        "structure": null
    }
 */

import DelightfulConditionEdit from "@bedelightful/delightful-flow/dist/DelightfulConditionEdit"
import type { Expression } from "@bedelightful/delightful-flow/dist/DelightfulConditionEdit/types/expression"
import { useMemoizedFn } from "ahooks"
import { useMemo } from "react"

export type WidgetConditionValue = {
	id: string
	version: string
	type: string
	structure: Expression.Condition
}

// @ts-ignore
interface DelightfulConditionWrapProps {
	value?: WidgetConditionValue
	onChange?: (value: WidgetConditionValue) => void
	[key: string]: any
}

export default function DelightfulConditionWrap({ ...props }: DelightfulConditionWrapProps) {
	// No need for outer structure for now, just change the structure data
	const onChange = useMemoizedFn((value: Expression.Condition) => {
		if (!props.onChange || !props.value) return
		props.onChange({
			...props.value,
			structure: value,
		})
	})

	// Avoid re-rendering caused by Form.Item onChange each time
	const MemoComponent = useMemo(() => {
		return <DelightfulConditionEdit {...props} value={props?.value?.structure} onChange={onChange} />
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	return MemoComponent
}






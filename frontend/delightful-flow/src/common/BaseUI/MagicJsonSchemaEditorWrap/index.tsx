/** Form component with one extra render layer
 * {
        "id": "component-66399f15d691c",
        "version": "1",
        "type": "form",
        "structure": {}
    }
 */

import { JsonSchemaEditorProps } from "@/DelightfulJsonSchemaEditor"
import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import { DelightfulJsonSchemaEditor } from "@/index"
import { useMemoizedFn } from "ahooks"
import _ from "lodash"
import React from "react"

export type WidgetJSONSchemaValue = {
	id: string
	version: string
	type: string
	structure: Schema
}

//@ts-ignore
interface DelightfulJSONSchemaEditorWrap extends JsonSchemaEditorProps {
	value?: WidgetJSONSchemaValue
	onChange?: (value: WidgetJSONSchemaValue) => void
}

function DelightfulJSONSchemaEditorWrap({ ...props }: DelightfulJSONSchemaEditorWrap) {
	// Only need to update the structure data; the outer wrapper is not required for now
	const onChange = useMemoizedFn((value: Schema) => {
		if (!props.onChange || !props.value) return

		/** Skip dispatch if the value is unchanged */
		if (_.isEqual(props.value?.structure, value)) return
		props.onChange({
			...props.value,
			structure: value,
		})
	})

	return <DelightfulJsonSchemaEditor {...props} data={props?.value?.structure} onChange={onChange} />
}

export default DelightfulJSONSchemaEditorWrap as any

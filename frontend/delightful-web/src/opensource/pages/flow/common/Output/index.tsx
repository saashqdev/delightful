import type Schema from "@delightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import DropdownCard from "@delightful/delightful-flow/dist/common/BaseUI/DropdownCard"
import JSONSchemaRenderer from "@/opensource/pages/flow/components/JSONSchemaRenderer"
import { cx } from "antd-style"
import styles from "./index.module.less"

export type WidgetValue = {
	value: {
		widget: any
		form: {
			id: string
			version: string
			type: string
			structure: Schema
		}
	}
	title?: string
	wrapperClassName?: string
}

export default function Output({ value, title = "Output", wrapperClassName }: WidgetValue) {
	return (
		<div className={cx(styles.output, wrapperClassName)}>
			<DropdownCard title={title}>
				{/* @ts-ignore */}
				<JSONSchemaRenderer form={value?.form?.structure} />
			</DropdownCard>
		</div>
	)
}

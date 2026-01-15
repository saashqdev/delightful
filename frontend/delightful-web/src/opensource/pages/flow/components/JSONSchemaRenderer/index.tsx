import { useMemoizedFn } from "ahooks"
import React, { useMemo, useRef } from "react"
import BaseItem from "./BaseItem"
import GroupItem from "./GroupItem"
import useExtraClassname from "./hooks/useExtraClassname"
import { useStyles } from "./style/style"
import Schema from "@bedelightful/delightful-flow/dist/DelightfulJsonSchemaEditor/types/Schema"
import { FormItemType } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"

type JSONSchemaRenderer = {
	form: Schema
}

export default function JSONSchemaRenderer({ form }: JSONSchemaRenderer) {
	const { styles, cx } = useStyles()
	const properties = useMemo(() => {
		if (form && form.properties) {
			// Sort properties by the sort field, smaller values come first
			return Object.entries(form.properties).sort(
				([, a], [, b]) => (a.sort || 0) - (b.sort || 0),
			)
		}
		return [] as Array<[string, Schema]>
	}, [form])

	const scrollRef = useRef<HTMLDivElement>(null)

	const { extraClassname, banScroll, makeCanScroll } = useExtraClassname()

	const onMouseEnter = useMemoizedFn(() => {
		const scrollHeight = scrollRef.current?.scrollHeight || 0
		const clientHeight = scrollRef.current?.clientHeight || 0

		const scrollable = scrollHeight > clientHeight
		if (scrollable) {
			makeCanScroll()
		}
	})

	return (
		<div
			className={cx(styles.jsonSchemaTree, extraClassname)}
			ref={scrollRef}
			onMouseEnter={onMouseEnter}
			onMouseLeave={banScroll}
		>
			{properties.map(([key, schema], index) => {
				if (schema.type === FormItemType.Array || schema.type === FormItemType.Object) {
					return (
						<GroupItem field={schema} key={index} fieldKey={key} type={schema.type} />
					)
				}
				return <BaseItem displayKey={key} key={index} field={schema} />
			})}
		</div>
	)
}

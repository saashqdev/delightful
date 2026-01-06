import React, { useMemo, useState } from "react"

import { FormItemType } from "@/DelightfulExpressionWidget/types"
import TSIcon from "@/common/BaseUI/TSIcon"
import { Button, Dropdown, Input, Popover, Tooltip } from "antd"
import { useMemoizedFn, useMount, useResetState, useUpdateEffect } from "ahooks"
import i18next from "i18next"
import _ from "lodash"
import { DataSourceOption } from "../.."
import { allMethodOptions, generateStepOptions, generateString } from "./helpers"
import "./index.less"
import { Wrap } from "./style"
import { useReactFlow } from "reactflow"

export type StepOption = {
	value: string // Actual value, e.g., toArray(), toJson(), toNumber()
	arguments?: string // Arguments to pass; currently only string, e.g., str.toArray(',')
	withArguments?: boolean
	label: string // Display text, e.g., convert to array/object
	icon?: React.ReactElement // Display icon, e.g., array icon
	type: string
}

type TransformerProps = React.PropsWithChildren<{
	/** Data source */
	source: DataSourceOption
	/** Callback invoked when a transform chain is chosen */
	onSelect: (node: any, trans?: string) => void
}>

/**
	 * Transformer component
	 *
	 * A floating panel in the ReactFlow canvas that lets users convert one data
	 * type into another, supporting chained transforms.
	 *
	 * Features:
	 * - Dynamically generates available transforms based on the source type
	 * - Supports chaining multiple transforms
	 * - Allows parameterized transforms for specific steps
	 * - Adapts positioning in the scaled ReactFlow canvas
	 * - Provides breadcrumb navigation for the transform chain
	 *
	 * Implementation notes:
	 * - Uses ReactFlow scale info to adjust popover positioning
	 * - Mounts the popover to the correct DOM node for event handling
	 * - Adjusts offsets (instead of scaling content) to keep interactions crisp
	 *
	 * @param {TransformerProps} props - component props
	 * @param {DataSourceOption} props.source - data source with type metadata
	 * @param {Function} props.onSelect - transform selection callback
	 * @param {React.ReactNode} props.children - trigger element
	 * @returns {JSX.Element} transformer popover
 */
const Transformer = ({ source, onSelect, children }: TransformerProps) => {
	const [values, setValues, resetValues] = useResetState([] as StepOption[])
	const [paths, setPaths] = useState([] as StepOption[])
	const [open, setOpen] = useState(false)

	const breadcrumbItems = useMemo(() => {
		if (!values.length) return []

		const list = values.reduce((res, val) => {
			const str = allMethodOptions?.find?.((item) => item.value === val.value)?.label
			res.push(str as string)
			return res
		}, [] as string[])

		return list as string[]
	}, [values])

	const panelOptions = useMemo(() => {
		return paths || ([] as StepOption[])
	}, [paths])

	/** Initialize the list of available transforms */
	const initStepOptions = useMemoizedFn((type: string) => {
		if (!type) return
		let typeArgs = type
		// Normalize array-like strings/objects that are not exactly "array"
		if (type.includes(FormItemType.Array)) {
			typeArgs = FormItemType.Array
		}
		const stepOptions = generateStepOptions(typeArgs)
		setPaths([...stepOptions])
	})

	useUpdateEffect(() => {
		const lastValue = _.last(values)
		if (lastValue) {
			initStepOptions(lastValue.type)
		} else {
			initStepOptions(source.type!)
		}
	}, [values])

	useMount(() => {
		initStepOptions(source.type!)
	})

	const handleSelected = useMemoizedFn((selectedOption: StepOption) => {
		setValues([...values, selectedOption])
	})

	const handleBreadcrumb = useMemoizedFn((index) => {
		setValues((old) => old.splice(0, index))
	})

	const onArgumentsChange = useMemoizedFn((newArguments: string, index: number) => {
		setPaths((oldPaths) => {
			return oldPaths.map((val, i) => {
				if (index !== i) return val
				return {
					...val,
					arguments: newArguments,
				}
			})
		})
	})

	const titleComponent = useMemo(() => {
		let showMenu = breadcrumbItems
		let showDropdownMenu = [] as string[]
		if (showMenu.length >= 3) {
			showMenu = breadcrumbItems.slice(0, 3)
			showDropdownMenu = breadcrumbItems.slice(3)
		}
		return (
			<p className="title">
				{showMenu.map((item, index) => {
					const isLastItem = index === breadcrumbItems.length - 1
					return (
						<>
							<span
								onClick={() => {
									handleBreadcrumb(index)
								}}
							>
								{item}
							</span>
							{!isLastItem && <TSIcon type="ts-arrow-right" />}
						</>
					)
				})}
				{showDropdownMenu.length > 0 && (
					<>
						<Dropdown
							className="nodrag"
							overlayStyle={{ zIndex: 9999 }}
							menu={{
								items: showDropdownMenu.map((item, index) => ({
									key: String(index + 4),
									label: item,
								})),
								onClick: ({ key }) => handleBreadcrumb(Number(key)),
							}}
						>
							<TSIcon type="ts-more-dot" />
						</Dropdown>
						<TSIcon type="ts-arrow-right" />
						<span>{i18next.t("common.pleaseSelect", { ns: "delightfulFlow" })}</span>
					</>
				)}
				{breadcrumbItems.length === 0 &&
					i18next.t("expression.transformToNewType", { ns: "delightfulFlow" })}
			</p>
		)
	}, [breadcrumbItems, handleBreadcrumb])

	const onConfirm = useMemoizedFn(() => {
		const targetTrans = generateString(values)
		onSelect(source, targetTrans)
		setOpen(false)
	})

	const onOpenChange = useMemoizedFn((visible: boolean) => {
		setOpen(visible)
		// Reset the chain when closing
		if (!visible) {
			resetValues()
		}
	})

	return (
		<Popover
			placement="right"
			showArrow={false}
			classNames={{ root: "delightful-type-transformer" }}
			onOpenChange={onOpenChange}
			open={open}
			content={
				<Wrap onClick={(e) => e.stopPropagation()}>
					{titleComponent}
					<ul className="nowheel">
						{panelOptions.map((item, index) => {
							return (
								<li key={item.value}>
									<span>
										{item.icon && <div className="delightful-icon">{item.icon}</div>}
										{item.label}
									</span>

									{item.withArguments && (
										<Tooltip
											title={i18next.t("common.argumentsSetting", {
												ns: "delightfulFlow",
											})}
										>
											<Input
												className="arguments-input"
												width={20}
												defaultValue={item.arguments}
												onChange={(e) =>
													onArgumentsChange(e.target.value, index)
												}
											/>
										</Tooltip>
									)}
									<span
										onClick={() => {
											handleSelected(item)
										}}
										className="select"
									>
										{i18next.t("common.select", { ns: "delightfulFlow" })}
									</span>
								</li>
							)
						})}
					</ul>
					<div className="footer">
						<Button type="primary" size="small" onClick={onConfirm}>
							{i18next.t("common.confirm", { ns: "delightfulFlow" })}
						</Button>
					</div>
				</Wrap>
			}
		>
			{children}
		</Popover>
	)
}

export default Transformer


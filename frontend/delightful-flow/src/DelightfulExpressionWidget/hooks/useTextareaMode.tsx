/* eslint-disable guard-for-in */
/**
 * Manage state when the expression widget runs in Textarea mode
 */
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import { Splitor } from "@/common/BaseUI/DropdownRenderer/Reference/constants"
import { Input, InputRef, Popover } from "antd"
import { useMemoizedFn, useResetState, useUpdateEffect } from "ahooks"
import _ from "lodash"
import React, { MutableRefObject, useEffect, useMemo, useRef, useState } from "react"
import {
	ExpressionMode,
	KeyCodeMap,
	TextAreaModePanelHeight,
	TextAreaModeTrigger,
} from "../constant"
import { findRootNodeAndValueByValue, findTargetTriggerField, getLastName } from "../helpers"
import {
	EXPRESSION_ITEM,
	EXPRESSION_VALUE,
	EditRef,
	FIELDS_NAME,
	InputExpressionValue,
	VALUE_TYPE,
} from "../types"
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"

interface Option {
	value: string
	label: string
	children?: Option[]
}

type UseTextareaModeProps = {
	mode: ExpressionMode
	options: DataSourceOption[]
	onChange: (value: InputExpressionValue) => void
	valueFieldName: keyof InputExpressionValue
	expressionVal: InputExpressionValue
	allowModifyField: boolean
	disabled: boolean
	valueType: VALUE_TYPE
	editRef: React.MutableRefObject<EditRef>
}

export type SelectPanelRef = {
	openSelectPanel: () => void
	closeSelectPanel: () => void
	withPopOver: (WrappedComponent: React.ReactNode, config: EXPRESSION_ITEM) => React.ReactNode
}

export default function useTextareaMode({
	mode,
	options,
	onChange,
	valueFieldName,
	expressionVal,
	allowModifyField,
	disabled,
	valueType,
	editRef,
}: UseTextareaModeProps) {
	const selectPanelRef = useRef<SelectPanelRef>({} as SelectPanelRef)
	const [selectPanelHeight, setSelectPanelHeight] = useState(TextAreaModePanelHeight)
	/** Map of popover visibility per node */
	const [popVisibleMap, setPopVisibleMap] = useState({} as Record<string, boolean>)
	/** Dynamic map of input refs */
	const [inputRefMap, setInputRefMap] = useState({} as Record<string, MutableRefObject<InputRef>>)
	/** Currently edited node */
	const [currentNode, setCurrentNode] = useState({} as EXPRESSION_ITEM)
	const [inputValue, setInputValue] = useState("")
	/** Current trigger marker */
	const [currentTrigger, setCurrentTrigger, resetCurrentTrigger] = useResetState({
		uniqueId: "",
		offset: -1,
	})

	/** Whether the dropdown is open */
	const [openDropdown, setOpenDropdown] = useState(false)

	/** User input after the @ symbol */
	const [userInput, setUserInput] = useState([] as string[])

	/** Close the popover for the current node */
	const closeCurrentNodeEdit = useMemoizedFn(() => {
		if (!currentNode || !currentNode.uniqueId) return
		/** Close editor layer */
		setPopVisibleMap({
			...popVisibleMap,
			[currentNode.uniqueId]: false,
		})
	})

	/** Watch expression value changes to decide whether to show the dropdown */
	useUpdateEffect(() => {
		const expressionKey = FIELDS_NAME[valueType] as string
		const expressionFields = (expressionVal?.[expressionKey] || []) as EXPRESSION_VALUE[]
		const triggerValue = expressionFields.find((field) =>
			field.value.includes(TextAreaModeTrigger),
		)
		const cursor = editRef?.current?.getCursor?.()
		const targetTriggerField = findTargetTriggerField(expressionFields, cursor)
		if (targetTriggerField) {
			// console.log("targetTriggerField", targetTriggerField, expressionFields)
			setCurrentTrigger(targetTriggerField)
		}
		// Toggle reference dropdown visibility
		setOpenDropdown(!!targetTriggerField)
		// Parse user input
		if (triggerValue) {
			const userInputAfterTrigger = triggerValue.value.split("@")[1]
			const userInputAfterSplit = userInputAfterTrigger.split(Splitor)
			setUserInput(userInputAfterSplit)
		}
	}, [expressionVal])

	// useUpdateEffect(() => {
	// 	console.log("triggerUpdate", currentTrigger)
	// }, [currentTrigger])

	/** Handle node double-click */
	const handleDoubleClickNode = useMemoizedFn((config: EXPRESSION_ITEM) => {
		if (disabled) return
		/** Close any existing popovers */
		// eslint-disable-next-line no-restricted-syntax
		for (const uniqueId in popVisibleMap) {
			if (popVisibleMap[uniqueId]) _.set(popVisibleMap, uniqueId, false)
		}
		/** Open the double-clicked node's editor */
		_.set(popVisibleMap, config.uniqueId, true)
		// Cache inputRef the first time it opens
		if (!inputRefMap[config.uniqueId]) {
			_.set(inputRefMap, config.uniqueId, React.createRef())
		}
		setInputValue(config.value)
		setCurrentNode(config)
		setPopVisibleMap({ ...popVisibleMap })

		const currentRef = inputRefMap[config.uniqueId]
		// Focus immediately if ref already exists
		if (currentRef && currentRef.current) {
			setTimeout(() => {
				currentRef.current.focus()
			}, 300)
		}
	})

	/** Open cascader panel */
	const openSelectPanel = useMemoizedFn(() => {
		setOpenDropdown(true)
	})

	/** Close cascader panel */
	const closeSelectPanel = useMemoizedFn(() => {
		setOpenDropdown(false)
	})

	useEffect(() => {
		const cleanup = flowEventBus.on(FLOW_EVENTS.NODE_SELECTED, () => {
			closeSelectPanel()
		})
		return () => {
			cleanup()
		}
	}, [])
	const onPressEnter = useMemoizedFn((evt: any) => {
		evt.stopPropagation()
		/** Save on Enter and hide */
		if ([KeyCodeMap.ENTER].includes(evt.keyCode)) {
			console.log("currentNode", currentNode)
			console.log("expressionVal", expressionVal)
			if (valueFieldName && expressionVal && currentNode) {
				const curValues = expressionVal[
					valueFieldName as keyof InputExpressionValue
				] as any[]
				if (curValues.length > 0) {
					const found = curValues.find(
						(item) => item.uniqueId === currentNode.uniqueId,
					) as EXPRESSION_VALUE
					if (!found) {
						/** Close editor layer */
						closeCurrentNodeEdit()
						return
					}
					found.value = inputValue

					/** Check if input matches a data source */
					const result = findRootNodeAndValueByValue(options, inputValue)
					/** If matched, update node data accordingly */
					if (result) {
						// eslint-disable-next-line no-restricted-syntax
						for (const key in result.value) {
							_.set(found, key, result.value[key])
						}
					} else {
						/** If not matched, fall back to key as value */
						found.name = getLastName(inputValue)
					}
					onChange({
						...expressionVal,
						[valueFieldName]: curValues,
					})

					/** Close editor layer */
					closeCurrentNodeEdit()
				}
			}
		}
	})

	/** Popover content */
	const EditContent = useMemoizedFn((id: string) => {
		return (
			<Input
				value={inputValue}
				onChange={(e) => setInputValue(e.target.value)}
				// @ts-ignore
				ref={inputRefMap[id]}
				autoFocus
				onPressEnter={onPressEnter}
				onClick={(e) => {
					e.stopPropagation()
				}}
				onBlur={() => {
					/** Close the editing layer */
					closeCurrentNodeEdit()
				}}
			/>
		)
		// eslint-disable-next-line react-hooks/exhaustive-deps
	})

	/** HOC to wrap child nodes with popover editing */
	const withPopOver = useMemoizedFn(
		(WrappedComponent: React.ReactNode, config: EXPRESSION_ITEM) => {
			/** In common mode, return the original component */
			if (!allowModifyField) return WrappedComponent
			const isPopoverVisible = popVisibleMap[config.uniqueId]

			/** In textarea mode, show popover to edit data */
			return (
				<Popover
					content={() => EditContent(config.uniqueId)}
					open={isPopoverVisible}
					trigger={undefined}
				>
					{WrappedComponent}
				</Popover>
			)
		},
	)

	selectPanelRef.current = {
		openSelectPanel,
		closeSelectPanel,
		withPopOver,
	}

	/** Patch newly added nodes to strip special markers */
	const patchToAddLabel = useMemoizedFn((updatedExpressionVal: InputExpressionValue) => {
		// In TextArea mode users type "$ " to trigger; remove it after selection and close popover
		if (valueFieldName) {
			const curValues = updatedExpressionVal[
				valueFieldName as keyof InputExpressionValue
			] as EXPRESSION_VALUE[]
			if (curValues.length > 0) {
				// Strip the "$ " marker directly
				const triggerItem = curValues.find(
					(item) => item.uniqueId === currentTrigger.uniqueId,
				)
				// console.log(
				// 	"curValues",
				// 	curValues,
				// 	triggerItem,
				// 	triggerItem?.value?.replace?.(/\$\s+/, ""),
				// )
				if (!triggerItem) return
				const strArr = triggerItem.value.split("")
				strArr.splice(currentTrigger.offset, 1)
				triggerItem.value = strArr.join("")
				resetCurrentTrigger()
			}
		}
	})

	/** In textarea mode, keep cascader expansion state aligned */
	const extraCascaderProps = useMemo(() => {
		return { open: openDropdown } as Record<string, any>
	}, [openDropdown])

	return {
		extraCascaderProps,
		openSelectPanel,
		closeSelectPanel,
		selectPanelRef,
		patchToAddLabel,
		withPopOver,
		handleDoubleClickNode,
		closeCurrentNodeEdit,
		userInput,
	}
}


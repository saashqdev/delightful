import { EXPRESSION_VALUE, InputExpressionValue } from "@/MagicExpressionWidget/types"
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import ErrorContent from "@/common/BaseUI/ErrorComponent/ErrorComponent"
import { ConfigProvider, message } from "antd"
import i18next from "i18next"
import _, { get, isEmpty, isPlainObject, set } from "lodash"
import React, {
	forwardRef,
	useCallback,
	useEffect,
	useImperativeHandle,
	useMemo,
	useRef,
	useState,
} from "react"
import { ErrorBoundary } from "react-error-boundary"
import { RelationGroup } from "./Children/RelationGroup"
import {
	DEFAULT_CONDITION_DATA,
	DEFAULT_CONDITION_FIELD,
	DEFAULT_CONVERT_FIELD,
	RELATION_COMP_TYPE,
	RELATION_LOGICS_MAP,
	posSeparator,
} from "./constants"
import { GlobalProvider } from "./context/Global/Provider"
import { isEqualToDefaultCondition } from "./helpers"
import { CustomConditionContainerStyle } from "./style"
import { Expression } from "./types/expression"
import { ThemeProvider } from "antd-style"
import { CLASSNAME_PREFIX } from "@/common/constants"

export enum ConditionEditMode {
	/** Inline display */
	Inline = "inline",
	/** Card layout */
	Card = "card",
}

export interface ChangeRef {
	addConditionGroup: (pos: string) => void
	addConditionItem: (pos: string) => void
	removeConditionItem: (pos: string) => void
	convertConditionItem: (pos: string) => void
	switchConditionItemLogic: (pos: string) => void
	updateConditionData: (
		pos: string,
		value: Expression.CompareNode | Expression.OperationNode,
		updateImmediately?: boolean,
	) => void
}

export type ConditionInstance = {
	setValue: (value: any) => void
	getValue: () => any
	resetValue: () => void
}

export interface CacheDictionary {
	key: string
	data: Expression.ConditionSource
}

export interface OptionsProps {
	termWidth: number
	openConvertButton: boolean
	maxGroupDepth: number
	expressionSource: DataSourceOption[]
}

export interface CustomConditionContainerProps {
	maxGroupDepth?: number
	openConvertButton?: boolean
	termWidth?: number
	onChange: (value: any) => void
	dataSource?: DataSourceOption[]
	value?: Expression.Condition
	mode?: ConditionEditMode
	/** Read-only mode */
	readonly?: boolean
	/** Disallow operations on the left condition for these internal positions */
	leftDisabledPos?: string[]
	/** Disallow convert/delete for these internal positions */
	disabledOperationPos?: string[]
	/** Current zoom ratio (used when embedded in flow) */
	zoom?: number
}

/**
	 * @description      Convert a string path to an array path
	 * @params        pathStr      String path
 */
const indexToArray = (pathStr: string) => `${pathStr}`.split("-").map((n) => +n)

const getSpaceCondition = (children: Expression.Condition[]) => {
	const result = {
		ops: RELATION_LOGICS_MAP.AND,
		children: _.cloneDeep(children) || [],
	} as Expression.LogicNode

	return result
}

function CustomConditionContainer(
	{
		maxGroupDepth = 3,
		openConvertButton = true,
		termWidth = 540,
		dataSource,
		onChange,
		readonly = false,
		leftDisabledPos = [],
		disabledOperationPos = [],
		zoom = 1,
		...props
	}: CustomConditionContainerProps,
	ref: React.Ref<ConditionInstance> | undefined,
) {
	const [conditionData, setConditionData] = useState<Expression.LogicNode>(getSpaceCondition([]))
	const [cacheDictionary, setCacheDictionary] = useState({} as CacheDictionary)
	const [expressionSource, setExpressionSource] = useState([] as DataSourceOption[])

	const subFormRef = useRef()
	const changeRef = useRef({} as ChangeRef)

	const value = useMemo(() => {
		if (props.value) return props.value
		return DEFAULT_CONDITION_DATA
	}, [props.value])

	const defaultConditionField = useMemo(() => {
		return DEFAULT_CONDITION_FIELD
	}, [])

	const defaultConvertField = useMemo(() => {
		return DEFAULT_CONVERT_FIELD
	}, [])

	if (Object.isFrozen(conditionData)) {
		const unFrozenObj = {} as Expression.LogicNode
		Object.assign(unFrozenObj, conditionData)
		setConditionData(unFrozenObj)
	}

	const readonlyCheck = useCallback(() => {
		if (readonly) {
			message.warning(i18next.t("common.readonlyTips", { ns: "magicFlow" }))
			return false
		}
		return true
	}, [readonly])

	const checkIsEmptyCondition = useCallback((condition: typeof DEFAULT_CONDITION_DATA) => {
		// Identical to default value, treat as empty
		if (_.isEqual(DEFAULT_CONDITION_DATA, condition)) return true

		const isEmptyExpression = (expression: InputExpressionValue) => {
			const cloneExpression = _.cloneDeep(expression)
			cloneExpression.const_value = (cloneExpression.const_value || []).filter(
				(val: EXPRESSION_VALUE) => {
					const cloneValue = { ...val }
					// Remove newlines, escaped newlines, and whitespace
					if (cloneValue) {
						cloneValue.value = cloneValue.value
							.replace(/\n/g, "") // Replace newlines with an empty string
							.replace(/\\n/g, "") // Replace '\\n' with an empty string
							.replace(/\s/g, "") // Remove spaces
					}
					// Filter out empty values
					return cloneValue.value
				},
			)
			cloneExpression.expression_value = (cloneExpression.expression_value || []).filter(
				(val: EXPRESSION_VALUE) => {
					const cloneValue = { ...val }
					// Remove newlines, escaped newlines, and whitespace
					if (cloneValue) {
						cloneValue.value = cloneValue.value
							.replace(/\n/g, "") // Replace newlines with an empty string
							.replace(/\\n/g, "") // Replace '\\n' with an empty string
							.replace(/\s/g, "") // Remove spaces
					}
					// Filter out empty values
					return cloneValue.value
				},
			)

			return (
				cloneExpression.const_value.length === 0 &&
				cloneExpression.expression_value.length === 0
			)
		}

		const compareChild = condition.children[0] as Expression.CompareNode

		if (compareChild.type === RELATION_COMP_TYPE.COMPARE) {
			return (
				isEmptyExpression(compareChild.left_operands) &&
				isEmptyExpression(compareChild.right_operands)
			)
		}
		return isEmptyExpression((compareChild as any).operands)
	}, [])

	const isEmptyCondition = useCallback(
		(data: any) => {
			// If the current value equals the default
			if (isEqualToDefaultCondition(data)) {
				// If both sides are empty, pass null to the parent
				const empty = checkIsEmptyCondition(data)
				if (empty) {
					onChange(null)
					return true
				}
			}
			return false
		},
		[checkIsEmptyCondition, onChange],
	)

	const ComponentChange = useCallback(
		async (data: any, updateImmediately = true) => {
			_.assignIn(conditionData, data)
			onChange(data)
			if (updateImmediately) {
				setConditionData(_.cloneDeep(data))

				// If the current value equals the default
				const empty = isEmptyCondition(data)
				if (empty) return
			}
		},
		[conditionData, isEmptyCondition, onChange],
	)

	/**
	 * Check whether the condition matches the default structure
	 */
	const isDefaultField = useCallback(
		(currentCondition: Expression.CompareNode) => {
			const defaultFieldKeys = Object.keys(defaultConditionField)
			return Object.keys(currentCondition).every((key) => defaultFieldKeys.includes(key))
		},
		[defaultConditionField],
	)

	/**
	 * @description: Add a sibling condition node
	 */
	const addConditionItem = useCallback(
		(pos: string) => {
			const tempConditionData = _.cloneDeep(conditionData)
			const defaultData = _.cloneDeep(defaultConditionField)
			let currentConditionByPos = null
			if (pos === "") {
				// Root node
				currentConditionByPos = tempConditionData
			} else {
				const path = indexToArray(pos).join(".children.")
				currentConditionByPos = get(tempConditionData.children, path)
			}

			currentConditionByPos.children.push(defaultData)

			ComponentChange(tempConditionData)
		},
		[ComponentChange, conditionData, defaultConditionField],
	)

	/**
	 * @description: Add a condition group
	 */
	const addConditionGroup = useCallback(
		(pos: any) => {
			if (!readonlyCheck()) return
			if (!pos) return
			const path = indexToArray(pos)
			const currentPath = path.join(".children.")
			const itemIndex = path.pop() as number // Track parent node index
			const parentPath = path.join(".children.")
			const tempConditionData = _.cloneDeep(conditionData)
			const defaultData = _.cloneDeep(defaultConditionField)
			const currentCondition = get(tempConditionData.children, currentPath)
			const newCondition = {
				ops: RELATION_LOGICS_MAP.AND,
				children: [currentCondition, defaultData],
			} as Expression.LogicNode

			if (parentPath === "") {
				// Root node
				if (tempConditionData.children.length === 1) {
					// Root node with only one child
					addConditionItem("")
					return
				}
				tempConditionData.children.splice(itemIndex, 1, newCondition)
			} else {
				get(tempConditionData.children, parentPath).children.splice(
					itemIndex,
					1,
					newCondition,
				)
			}
			ComponentChange(tempConditionData)
		},
		[ComponentChange, addConditionItem, conditionData, defaultConditionField, readonlyCheck],
	)

	/**
	 * @description: Delete a condition node

	 */
	const removeConditionItem = useCallback(
		(pos: any) => {
			if (!readonlyCheck()) return
			if (!pos) return
			const path = indexToArray(pos)
			const tempConditionData = _.cloneDeep(conditionData)
			const itemIndex = path.pop() as number // Track parent node index
			const parentPath = path.join(".children.")
			if (parentPath === "") {
				if (tempConditionData.children.length === 1) {
					message.warning(i18next.t("common.cannotDelete", { ns: "magicFlow" }))
					return
				}
				// Root node
				tempConditionData.children.splice(itemIndex, 1)
				if (tempConditionData.children.length === 1) {
					const firstChild = tempConditionData.children[0] as Expression.LogicNode
					if (firstChild.ops) {
						tempConditionData.children = firstChild.children
					}
				}
			} else {
				const parentCondition = get(tempConditionData.children, parentPath)
				parentCondition.children.splice(itemIndex, 1)

				// After deletion, lift the remaining child if only one remains
				if (parentCondition.children.length === 1) {
					set(tempConditionData.children, parentPath, parentCondition.children[0])
				} else {
					set(tempConditionData.children, parentPath, parentCondition)
				}
			}
			ComponentChange(tempConditionData)
		},
		[ComponentChange, conditionData, readonlyCheck],
	)

	/**
	 * @description: Toggle between default display component and converted component
	 */
	const convertConditionItem = useCallback(
		(pos: string) => {
			if (!readonlyCheck()) return
			if (pos === "") return // Root node
			const tempConditionData = _.cloneDeep(conditionData)
			const path = indexToArray(pos).join(".children.")
			const currentConditionByPos = get(tempConditionData.children, path)

			const isDefault = isDefaultField(currentConditionByPos)
			// TODO update convert logic
			set(
				tempConditionData.children,
				path,
				isDefault ? _.cloneDeep(defaultConvertField) : _.cloneDeep(defaultConditionField),
			)

			ComponentChange(tempConditionData)
		},
		[
			ComponentChange,
			conditionData,
			defaultConditionField,
			defaultConvertField,
			isDefaultField,
			readonlyCheck,
		],
	)

	const updateConditionData: ChangeRef["updateConditionData"] = useCallback(
		(pos, val, updateImmediately = true) => {
			if (!readonlyCheck()) return
			const tempConditionData = _.cloneDeep(conditionData)
			const path = indexToArray(pos).join(".children.")
			set(tempConditionData.children, path, val)
			ComponentChange(tempConditionData, updateImmediately)
		},
		[ComponentChange, conditionData, readonlyCheck],
	)

	/**
	 * @description: Switch the logical operator on a condition
	 */
	const switchConditionItemLogic = useCallback(
		(pos: string) => {
			if (!readonlyCheck()) return
			const tempConditionData = _.cloneDeep(conditionData)

			if (pos === "") {
				// Root node
				ComponentChange(
					{
						...tempConditionData,
						ops:
							tempConditionData.ops === RELATION_LOGICS_MAP.AND
								? RELATION_LOGICS_MAP.OR
								: RELATION_LOGICS_MAP.AND,
					},
					false,
				)
			} else {
				const path = indexToArray(pos).join(".children.")
				const currentConditionByPos = get(tempConditionData.children, path)
				set(tempConditionData.children, path, {
					...currentConditionByPos,
					ops:
						currentConditionByPos.ops === RELATION_LOGICS_MAP.AND
							? RELATION_LOGICS_MAP.OR
							: RELATION_LOGICS_MAP.AND,
				})
				ComponentChange(tempConditionData, false)
			}
		},
		[ComponentChange, conditionData, readonlyCheck],
	)

	/**
	 * @description: Reset conditionData
	 */
	const resetConditionData = useCallback(() => {
		// TODO clarify default value shape
		const defaultData = _.cloneDeep(defaultConditionField)
		const tempConditionData = getSpaceCondition([defaultData as any])
		ComponentChange(tempConditionData)
	}, [ComponentChange, defaultConditionField])

	const HandleInitialConditions = useCallback(() => {
		const initialValue =
			!isEmpty(value) && Array.isArray((value as Expression.LogicNode).children)
				? (value as Expression.LogicNode).children
				: []
		if (initialValue.length === 0 && conditionData.children.length === 0) {
			// Build the initial row
			addConditionItem("")
		}
	}, [value, conditionData.children.length, addConditionItem])

	useEffect(() => {
		if (isPlainObject(value) && !isEmpty(value)) {
			setConditionData(_.cloneDeep(value) as Expression.LogicNode)
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	useEffect(() => {
		HandleInitialConditions()
		return () => {}
	}, [HandleInitialConditions])

	/** Set expression data source */
	useEffect(() => {
		if (!dataSource) return
		setExpressionSource(dataSource)
	}, [dataSource])

	useEffect(() => {
		changeRef.current = {
			addConditionGroup,
			addConditionItem,
			removeConditionItem,
			convertConditionItem,
			switchConditionItemLogic,
			updateConditionData,
		}
	}, [
		addConditionGroup,
		addConditionItem,
		removeConditionItem,
		convertConditionItem,
		switchConditionItemLogic,
		updateConditionData,
	])

	useImperativeHandle(ref, () => ({
		setValue(v: any) {
			onChange(v)
		},
		getValue() {
			const empty = isEmptyCondition(conditionData)
			if (empty) return null
			return conditionData
		},
		resetValue: resetConditionData,
	}))

	// Positions where a title may be shown
	const showTitlePosList: string[] = useMemo(() => {
		return new Array(maxGroupDepth).fill(0).reduce((acc, cur) => {
			if (acc.length === 0) {
				acc.push("0")
				return acc
			}
			const lastItem = acc[acc.length - 1]
			acc.push(`${lastItem}${posSeparator}${cur}`)
			return acc
		}, [] as string[])
	}, [maxGroupDepth])

	return (
		<ErrorBoundary
			fallbackRender={({ error }) => {
				console.log("error", error)
				return <ErrorContent />
			}}
		>
			<ConfigProvider prefixCls={CLASSNAME_PREFIX}>
				<CustomConditionContainerStyle>
					<GlobalProvider
						leftDisabledPos={leftDisabledPos}
						disabledOperationPos={disabledOperationPos}
						showTitlePosList={showTitlePosList}
					>
						<RelationGroup
							pos=""
							changeRef={changeRef}
							conditionData={conditionData}
							options={{
								maxGroupDepth,
								openConvertButton,
								termWidth,
								expressionSource,
							}}
							cacheDictionary={cacheDictionary}
							readonly={readonly}
						/>
					</GlobalProvider>
				</CustomConditionContainerStyle>
			</ConfigProvider>
		</ErrorBoundary>
	)
}

export default forwardRef(CustomConditionContainer)

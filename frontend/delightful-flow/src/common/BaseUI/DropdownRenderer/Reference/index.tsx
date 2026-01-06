import { NodeVersionMap } from "@/common/context/NodeMap/Context"
import { SnowflakeId } from "@/DelightfulExpressionWidget/helpers"
import {
	EditRef,
	EXPRESSION_VALUE,
	LabelTypeMap,
	MethodArgsItem,
	RenderConfig,
	VALUE_TYPE,
} from "@/DelightfulExpressionWidget/types"
import { getLatestNodeVersion, getReferencePrefix, judgeIsVariableNode } from "@/DelightfulFlow/utils"
import { getFormTypeToTitle } from "@/DelightfulJsonSchemaEditor/constants"
import Schema from "@/DelightfulJsonSchemaEditor/types/Schema"
import { IconTreeTriangleDown } from "@douyinfe/semi-icons"
import { Tooltip, Tree, TreeDataNode } from "antd"
import { Icon } from "@iconify/react"
import { IconChevronRight } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import i18next from "i18next"
import _ from "lodash"
import React, { useMemo, useRef } from "react"
import SearchInput from "../SearchInput"
import FunctionTips from "./components/FunctionTips"
import Transformer from "./components/Transformer"
import { Splitor } from "./constants"
import useDropdownRender from "./hooks/useDropdownRender"
import useRender from "./hooks/useRender"
import { RendererGlobalStyle, RendererWrapper } from "./style"

export type DataSourceOption = {
	title: string
	key: string | number
	nodeId: string | number
	// Current node type
	nodeType: string | number
	children?: DataSourceOption[]
	// Current field type
	type?: string
	// Whether this is a root node
	isRoot?: boolean
	// Whether this is a constant
	isConstant?: boolean
	// Schema for the data source; omitted when isConstant is true
	rawSchema?: Schema
	// Render type (used for special cases like members/multi-select)
	renderType?: LabelTypeMap
	// Whether this is a global variable
	isGlobal?: boolean
	// Data source description
	desc?: string
	// Whether this is a function data source
	isMethod?: boolean
	// Whether this option is selectable
	selectable?: boolean
} & Partial<MethodOption>

// Fields only for function blocks
export type MethodOption = {
	args: EXPRESSION_VALUE["args"]
	return_type: string
	arg: MethodArgsItem[]
	methodRender: React.ReactElement
}

export type ReferenceCascaderOnChange = (value: EXPRESSION_VALUE & { schemaType?: string }) => void

type CascaderDropdownProps = {
	editRef: React.MutableRefObject<EditRef>
	dataSource?: DataSourceOption[]
	// 是否携带参数的schema类型
	withSchemaType?: boolean
	onChange: ReferenceCascaderOnChange
	nodeMap: NodeVersionMap
	userInput: string[]
	// 自定义渲染配置（如渲染成员、多选、单选、checkbox等）
	renderConfig?: RenderConfig
	// 当前值类型
	valueType?: VALUE_TYPE
	// 当前弹层是否打开
	dropdownOpen?: boolean
}

const CascaderDropdown = ({
	editRef,
	dataSource = [],
	onChange,
	nodeMap,
	userInput,
	renderConfig,
	valueType,
	dropdownOpen,
}: CascaderDropdownProps) => {
	const inputRef = useRef<HTMLInputElement>()

	const {
		keyword,
		onSearchChange,
		onExpand,
		expandedKeys,
		autoExpandParent,
		setKeyword,
		filterDataSource,
	} = useDropdownRender({
		dataSource,
		userInput,
	})

	const { RenderComponent } = useRender({
		renderConfig,
		onChange,
		valueType,
		dropdownOpen,
	})

	/** Return different key values based on option type
	 * 1. For constants, return variables.key
	 * 2. For functions, return key
	 */
	const getValueKey = useMemoizedFn((option: DataSourceOption) => {
		const prefix = getReferencePrefix(option)
		if (prefix) {
			// Handle the case where a node is directly selected
			if (prefix === option.key) return prefix
			return `${prefix}${Splitor}${option.key}`
		}
		return option.key
	})

	/** Return the value type based on option type
	 * 1. Functions → function block type
	 * 2. References → block type
	 * 3. Constants → text type
	 *
	 * Default fallback → block type
	 */
	const getValueTypeByOption = useMemoizedFn((option: DataSourceOption) => {
		if (option.isMethod) {
			return LabelTypeMap.LabelFunc
		}
		if (option.isConstant) {
			return LabelTypeMap.LabelText
		}
		return LabelTypeMap.LabelNode
	})

	/** Provide extra config based on option type
	 * For functions, return args info
	 */
	const getExtraConfigByOption = useMemoizedFn(
		(
			option: DataSourceOption & {
				rawTitle?: string
			},
		) => {
			if (option.isMethod) {
				return {
					args: [] as EXPRESSION_VALUE["args"],
					name: option.rawTitle as string,
					rawOption: option,
				}
			}
			return {}
		},
	)

	// Handle selection
		const valueKey = getValueKey(option)
		const valueType = getValueTypeByOption(option)
		const extraConfig = getExtraConfigByOption(option)
		const currentNode = editRef?.current?.getCurrentNode?.()
		return {
			type: valueType,
			uniqueId: currentNode || SnowflakeId(),
			value: valueKey as string,
			trans,
			...extraConfig,
		}
	})

	const getTooltipContent = useMemoizedFn((option: DataSourceOption) => {
		if (!option.selectable) return ""
		const formTypeToTitle = getFormTypeToTitle()
		return option.type ? `${option.title} | ${formTypeToTitle[option.type]}` : option.title
	})

	// 选中事件
	const onSelect = useMemoizedFn((node, trans?: string) => {
		const option = node as DataSourceOption
		if (!option.selectable) return
		const changeValue = getChangeValuesByOption(option, trans)
		onChange(changeValue)
		setKeyword("")
	})

	const treeData = useMemo(() => {
		const formTypeToTitle = getFormTypeToTitle()
		const loop = (data: TreeDataNode[], deep = 0): TreeDataNode[] =>
			data.map((item) => {
				/***
				 * Compute highlighted segments
				 */
				const tmpItem = item as DataSourceOption
				// When no type is present, treat it as a node and handle specially
				const isNode = !tmpItem.type
				const formItemType = tmpItem.type
				const strTitle = item.title as string
				const tooltipContent = getTooltipContent(tmpItem)
				const index = strTitle.indexOf(keyword)
				const beforeStr = strTitle.substring(0, index)
				const afterStr = strTitle.slice(index + keyword.length)
				const typeString = formItemType ? formTypeToTitle[formItemType] : ""
				let title =
					index > -1 ? (
						<>
							<Tooltip title={tooltipContent} placement="left">
								<span className="left">
									{beforeStr}
									<span className="site-tree-search-value">{keyword}</span>
									{afterStr}
								</span>
								<span className="center">{typeString}</span>
							</Tooltip>
							{!isNode && (
								<Transformer source={tmpItem} onSelect={onSelect}>
									<IconChevronRight className="right" />
								</Transformer>
							)}
						</>
					) : (
						<>
							<Tooltip title={tooltipContent} placement="left">
								<span className="left">
									<span>{strTitle}</span>
								</span>
							</Tooltip>
							<span className="center">{typeString}</span>
							{!isNode && (
								<Transformer source={tmpItem} onSelect={onSelect}>
									<IconChevronRight className="right" />
								</Transformer>
							)}
						</>
					)
				if (tmpItem.isMethod) {
					title = <FunctionTips targetOption={tmpItem} keyword={keyword} />
				}
				const latestNodeVersion = getLatestNodeVersion(tmpItem.nodeType)
				const node = nodeMap[tmpItem.nodeType]?.[latestNodeVersion]?.schema

				const isVariableNode = judgeIsVariableNode(node?.id)
				const isConstant = tmpItem?.isConstant
				const isSpecialType = isVariableNode || isConstant
				const icon = !tmpItem.isRoot ? (
					<Icon icon="tabler:variable" className="icon-variable" strokeWidth={2} />
				) : (
					<span className={`icon-app ${isSpecialType ? "is-variable-icon" : ""}`}>
						{node?.icon}
					</span>
				)

				/**
				 * Handle children recursively
				 */
				if (item.children) {
					return {
						...item,
						title,
						children: loop(item.children, deep + 1),
						icon,
						className:
							deep === 0
								? `is-application ${isVariableNode ? "is-variable" : ""}`
								: "",
						rawTitle: tmpItem.title,
					}
				}

				return {
					...item,
					title,
					icon,
					className:
						deep === 0 ? `is-application ${isVariableNode ? "is-variable" : ""}` : "",
					rawTitle: tmpItem.title,
				}
			})

		return loop(filterDataSource)
	}, [keyword, filterDataSource])
	return (
		<RendererWrapper className="nowheel" onClick={(e) => e.stopPropagation()}>
			<RendererGlobalStyle />
			<>
				<div className="search-wrapper">
					{RenderComponent ? (
						<>
							<div className="title">
								{i18next.t("expression.const", { ns: "magicFlow" })}
							</div>
							{RenderComponent}
							<div className="title">
								{i18next.t("expression.expression", { ns: "magicFlow" })}
							</div>
						</>
					) : null}
					<SearchInput
						placeholder={i18next.t("expression.searchVariables", { ns: "magicFlow" })}
						value={keyword}
						onChange={onSearchChange}
						refInstance={inputRef}
					/>
				</div>
				<Tree
					showIcon
					treeData={treeData}
					onExpand={onExpand}
					expandedKeys={expandedKeys}
					autoExpandParent={autoExpandParent}
					switcherIcon={<IconTreeTriangleDown />}
					virtual
					// Occupy the full row
					blockNode
					onSelect={(selectKeys, { node }) => onSelect(node)}
					onClick={(e, node) => {
						// If the node is not selectable, toggle expand/collapse on click
						if (!node.selectable) {
							e.preventDefault()
							const cloneKeys = _.cloneDeep(expandedKeys)
							const index = cloneKeys.findIndex((key) => node.key === key)
							if (node.expanded) {
								if (index === -1) return
								cloneKeys.splice(index, 1)
								onExpand(cloneKeys)
							} else {
								onExpand([...cloneKeys, node.key])
							}
						}
					}}
				/>
			</>
		</RendererWrapper>
	)
}

export default CascaderDropdown

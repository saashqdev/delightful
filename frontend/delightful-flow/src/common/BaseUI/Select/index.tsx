/**
 * Intended for use within flow nodes only
 */
import { Select } from "antd"
import { IconChevronDown, IconX } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import React, { ReactElement, forwardRef, useEffect, useMemo, useRef, useState, memo } from "react"
import BaseDropdownRenderer from "../DropdownRenderer/Base"
import styles from "./index.module.less"
import { GlobalStyle } from "./style"
import { FLOW_EVENTS, flowEventBus } from "./constants"

// External event handler types
export type SelectEventHandlers = {
	onNodeSelected?: (nodeId?: string) => void
	onEdgeSelected?: (edgeId?: string) => void
	onCanvasClicked?: (position?: { x: number; y: number }) => void
}

type TsSelectProps = {
	className?: string
	suffixIcon?: React.ReactElement
	popupClassName?: string
		dropdownRenderProps?: {
			// Placeholder text for the search box
			placeholder?: string
			// Actual render component
			component?: () => ReactElement
			// Whether to show the search box
			showSearch?: boolean
			// Wrapper component for options
			OptionWrapper: React.FC<any>
			[key: string]: any
		}
		// Optional external handler to close the dropdown
	eventHandlers?: SelectEventHandlers
	[key: string]: any
}

// Wrap with memo to improve performance
const DelightfulSelectComponent = forwardRef((props: TsSelectProps, ref: any) => {
	const { dropdownRenderProps, eventHandlers, ...restSelectProps } = props

	const {
		placeholder: dropdownPlaceholder,
		showSearch,
		component: DropdownRenderComp = BaseDropdownRenderer,
		OptionWrapper,
		...restDropdownProps
	} = (dropdownRenderProps || {})!

	const [open, setOpen] = useState(false)
	const containerRef = useRef<HTMLDivElement>(null) // Container reference

	// Filter out options marked as invisible
	const filterOptions = useMemo(() => {
		// @ts-ignore
		return restSelectProps?.options?.filter((option) => {
			if (!Reflect.has(option, "visible")) return true
			return Reflect.get(option, "visible")
		})
	}, [restSelectProps.options])

	// Decide whether to show the suffix icon based on allowClear and current value
	const showSuffixIcon = useMemo(() => {
		if (!Reflect.has(restSelectProps, "allowClear")) return true
		const allowClear = Reflect.get(restSelectProps, "allowClear")
		return allowClear && !restSelectProps.value
	}, [restSelectProps.allowClear, restSelectProps.value])

	// Intercept onChange to add custom behavior
	const onChangeHooks = useMemoizedFn((event) => {
		restSelectProps.onChange?.(event)
		setOpen(false)
	})

	// Create an event listener system instead of relying directly on Context
	const closeDropdown = useMemoizedFn(() => {
		setOpen(false)
	})

	const handleNodeSelected = useMemoizedFn((e: CustomEvent) => {
		closeDropdown()
		eventHandlers?.onNodeSelected?.(e.detail)
	})

	const handleEdgeSelected = useMemoizedFn((e: CustomEvent) => {
		closeDropdown()
		eventHandlers?.onEdgeSelected?.(e.detail)
	})

	const handleCanvasClicked = useMemoizedFn((e: CustomEvent) => {
		closeDropdown()
		eventHandlers?.onCanvasClicked?.(e.detail)
	})

	// Listen to external events to close the dropdown
	useEffect(() => {
		// Cleanup function list
		const cleanupFunctions = []

		const nodeClickCleanup = flowEventBus.on(FLOW_EVENTS.NODE_SELECTED, handleNodeSelected)
		const edgeClickCleanup = flowEventBus.on(FLOW_EVENTS.EDGE_SELECTED, handleEdgeSelected)
		const canvasClickCleanup = flowEventBus.on(FLOW_EVENTS.CANVAS_CLICKED, handleCanvasClicked)
		cleanupFunctions.push(nodeClickCleanup, edgeClickCleanup, canvasClickCleanup)

		// Return cleanup function
		return () => {
			cleanupFunctions.forEach((cleanup) => cleanup())
		}
	}, [eventHandlers])

	// Close dropdown when clicking outside
	useEffect(() => {
		const handleClickOutside = (e: MouseEvent) => {
			if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
				setOpen(false)
			}
		}

		document.addEventListener("mousedown", handleClickOutside)
		return () => {
			document.removeEventListener("mousedown", handleClickOutside)
		}
	}, [])

	return (
		<>
			<GlobalStyle />
			<div ref={containerRef}>
				<Select
					ref={ref}
					{...restSelectProps}
					className={`${restSelectProps.className || ""} nodrag ${styles.selectWrapper}`}
					suffixIcon={
						showSuffixIcon ? restSelectProps.suffixIcon || <IconChevronDown /> : null
					}
					popupClassName={`nowheel ${restSelectProps.popupClassName || ""}`}
					getPopupContainer={(triggerNode) => triggerNode.parentNode}
					open={open}
					onClick={(e) => {
						if (!open) {
							e.stopPropagation()
							setOpen(true)
							restSelectProps?.onClick?.(e)
						}
					}}
					onChange={onChangeHooks}
					dropdownRender={
						dropdownRenderProps
							? () => (
									<DropdownRenderComp
										options={filterOptions}
										placeholder={dropdownPlaceholder}
										value={restSelectProps.value}
										onChange={onChangeHooks}
										showSearch={showSearch}
										multiple={restSelectProps.mode === "multiple"}
										OptionWrapper={OptionWrapper}
										{...restDropdownProps}
									/>
							  )
							: // Wrap to prevent onClick from bubbling
							  (menu) => <div onClick={(e) => e.stopPropagation()}>{menu}</div>
					}
					clearIcon={<IconX size={16} className={styles.clearIcon} />}
				/>
			</div>
		</>
	)
})

// Memoized component with custom comparator
const DelightfulSelect = memo(DelightfulSelectComponent)

// Preserve static properties
const EnhancedDelightfulSelect: any = DelightfulSelect
EnhancedDelightfulSelect.Option = Select.Option
EnhancedDelightfulSelect.OptGroup = Select.OptGroup

export default EnhancedDelightfulSelect


import { InputRef } from "antd"
import { useMemoizedFn } from "ahooks"
import _ from "lodash"
import React, { forwardRef, useEffect, useImperativeHandle, useMemo, useRef, useState } from "react"
import { Names } from "../types"
import { NamesSelectProps } from "./Select"
import NamesItem from "./components/NamesItem/NamesItem"
import "./styles/options.less"
import { Timeout } from "ahooks/lib/useRequest/src/types"

/**
 *
 * @param {Function} itemClick Click single selection event callback
 * @param {Ref} parent Parent Ref mainly used to get input field height, then move dropdown layer down
 * @param {Array} values Values
 * @param {Array} options Dropdown options
 * @param {Boolean || Function} filterOption Whether filtering is allowed
 * @param {Function} onSearch Search function
 * @param {Component} footer Component
 * @param {Object} extraOptions // Previous step selection configuration
 * {
 *		showExtra, //Whether to show previous step selection
 * 		step, // Current step number
 * 		fieldTypes // Supported field types
 * }
 */

type SelectOptionRef = {
	inputRef: React.RefObject<InputRef>
	onChange: (e: any) => void
	setInputValue: React.Dispatch<React.SetStateAction<string>>
}

type SelectOptionsProps = {
	value: NamesSelectProps["value"]
	itemClick: (val: Names) => void
} & Pick<NamesSelectProps, "options" | "filterOption">

const SelectOptions = forwardRef<SelectOptionRef, SelectOptionsProps>((props, ref) => {
	const { value, options, itemClick, filterOption = false, ...rest } = props
	const [inputValue, setInputValue] = useState("")
	const timer = useRef<Timeout>()
	const [displayOptions, setDisplayOptions] = useState(options || [])
	const inputRef = useRef<InputRef>(null)

	const copyOptions = useMemo(() => {
		return [...(options || [])]
	}, [options])

	useEffect(() => {
		setDisplayOptions(options)
	}, [options])

	const onChange = useMemoizedFn((e) => {
		const val = e.target.value
		setInputValue(val)

		if (timer.current) clearTimeout(timer.current)
		const value = _.trim(val)
		timer.current = setTimeout(() => {
			const filterOptions = copyOptions.filter((item) => item.label.indexOf(value) > -1)
			setDisplayOptions(filterOptions)

			// TODO When no options exist, support adding user input items
			// if (filterOptions.length) {
			// 	const existEqualOption = filterOptions.some(item => item.label === value)
			// 	setShowAddOptionBtn(!existEqualOption && !!value)
			// } else {
			// 	setShowAddOptionBtn(!!value)
			// }
		}, 100)
	})

	const onPressEnter = useMemoizedFn(() => {
		if (!inputValue.length) return
		const foundItem = displayOptions.find((item) => item.label === inputValue)
		if (foundItem) {
			itemClick({
				id: foundItem.id,
				name: foundItem.label,
			})
			setInputValue("")
			return
		}
		// if (!isEnableAdd) return
		// addOption()
	})

	useImperativeHandle(
		ref,
		() => ({
			inputRef,
			onChange,
			setInputValue,
		}),
		[onChange, setInputValue],
	)

	return (
		<div className="delightful-names-options" onWheel={(e) => e.stopPropagation()}>
			<ul
				className="ul"
				onContextMenu={(e) => e.preventDefault()}
				onClick={(e) => e.stopPropagation()}
			>
				<div className="container nowheel">
					{displayOptions.map((item) => {
						return (
							<NamesItem
								key={item.id}
								item={item}
								value={value}
								itemClick={itemClick}
								{...rest}
							/>
						)
					})}
					{!displayOptions.length && <li className="empty">No search results</li>}
				</div>
			</ul>
		</div>
	)
})

export default SelectOptions


import React, { useRef } from "react"

import { IconCheck } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { checkboxSelectOptions } from "./constants"
import "./index.less"
import { CheckboxSelectProps } from "./types"

const CheckboxSelect = ({ value, onChange }: CheckboxSelectProps) => {
	const selectContainerRef = useRef<HTMLDivElement>(null)
	const wrapperRef = useRef<HTMLDivElement>(null)

	const onSelectItem = useMemoizedFn((val: any) => {
		/** Handle single selection */
		onChange?.(val)
	})

	return (
		<div className="delightful-checkbox-select" ref={wrapperRef}>
			<div
				className="checkbox-select"
				ref={selectContainerRef}
				onMouseDown={(e) => e.preventDefault()}
			>
				<div className="dropdown-list">
					{checkboxSelectOptions.map((option) => {
						return (
							<div
								className="dropdown-item"
								onClick={() => {
									onSelectItem(option.value)
								}}
							>
								<div className="label">{option.label}</div>
								{value === option.value && <IconCheck className="tick" />}
							</div>
						)
					})}
				</div>
			</div>
		</div>
	)
}

export default CheckboxSelect


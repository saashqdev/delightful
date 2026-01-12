import DelightfulSelect from "@/common/BaseUI/Select"
import { SelectProps } from "antd/lib/select"
import React, { useState } from "react"

interface TagsSelect extends SelectProps {
	onChange: (value: string[]) => void
}

const TagsSelect = ({ ...props }: TagsSelect) => {
	const [inputValue, setInputValue] = useState<string>("")

	const handleInputChange = (value: string) => {
		setInputValue(value)
	}

	const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
		if (e.key === "Tab") {
			e.preventDefault() // Prevent default tab key behavior

			// Check if the same value already exists
			if (!props?.value?.includes(inputValue.trim()) && inputValue.trim() !== "") {
				props?.onChange([...props?.value, inputValue.trim()])
				setInputValue("")
			}
		}
	}

	return (
		<DelightfulSelect
			mode="tags"
			multiple
			searchValue={inputValue}
			open={false}
			onInputKeyDown={handleKeyDown}
			onInput={(e: any) => handleInputChange(e.target.value)}
			{...props}
		/>
	)
}

export default TagsSelect


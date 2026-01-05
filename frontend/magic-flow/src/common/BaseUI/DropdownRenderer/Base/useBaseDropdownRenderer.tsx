import { useMemoizedFn } from "ahooks"
import React, { useMemo, useState } from "react"
import { BaseDropdownOption } from "."

type DropdownRenderProps = {
	options: BaseDropdownOption[]
	showSearch: boolean
}

export default function useBaseDropdownRenderer({ options, showSearch }: DropdownRenderProps) {
	// Search keyword
	const [keyword, setKeyword] = useState("")

	// Handle search keyword changes
	const onSearchChange = useMemoizedFn((e: React.ChangeEvent<HTMLInputElement>) => {
		const { value } = e.target
		setKeyword(value)
	})

	const filterOptions = useMemo(() => {
		// If search is disabled, return all options
		if (!showSearch) return options
		return options.filter((option) => {
			//@ts-ignore
			return option?.realLabel?.includes(keyword)
		})
	}, [options, keyword])

	return {
		keyword,
		setKeyword,
		onSearchChange,
		filterOptions,
	}
}

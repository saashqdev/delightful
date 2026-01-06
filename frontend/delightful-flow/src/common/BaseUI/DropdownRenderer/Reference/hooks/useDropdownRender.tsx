import { useDebounceFn, useMemoizedFn, useMount, useUpdateEffect } from "ahooks"
import React, { useMemo, useState } from "react"
import { DataSourceOption } from ".."
import { getDefaultExpandedKeys, getRelationDataSource, getRelationExpandKeys } from "../helpers"

type DropdownRenderProps = {
	dataSource: DataSourceOption[]
	userInput: string[]
}

export default function useDropdownRender({ dataSource, userInput }: DropdownRenderProps) {
	// Data source after filtering
	const [filterDataSource, setFilterDataSource] = useState(dataSource)
	const [expandedKeys, setExpandedKeys] = useState<React.Key[]>([])
	const [autoExpandParent, setAutoExpandParent] = useState(true)
	// Search keyword
	const [keyword, setKeyword] = useState("")

	const onExpand = useMemoizedFn((newExpandedKeys: React.Key[]) => {
		setExpandedKeys(newExpandedKeys)
		setAutoExpandParent(false)
	})

	useUpdateEffect(() => {
		setFilterDataSource(getRelationDataSource(dataSource, keyword))
	}, [keyword, dataSource])

	const allExpandKeys = useMemo(() => {
		return getDefaultExpandedKeys(filterDataSource)
	}, [filterDataSource])

	useMount(() => {
		setExpandedKeys(allExpandKeys)
	})

	useUpdateEffect(() => {
		setExpandedKeys(allExpandKeys)
	}, [userInput])

	// Debounced expand calculation
	const { run: updateExpandKeysByKeyword } = useDebounceFn(
		(value: string) => {
			const newExpandedKeys = getRelationExpandKeys(filterDataSource, value)
			// console.log("newExpandedKeys", dataSource, value, newExpandedKeys)
			setExpandedKeys(newExpandedKeys)
		},
		{
			wait: 500,
		},
	)

	// Handle keyword changes
	const onSearchChange = useMemoizedFn((e: React.ChangeEvent<HTMLInputElement>) => {
		const { value } = e.target
		updateExpandKeysByKeyword(value)
		setKeyword(value)
		setAutoExpandParent(true)
		setExpandedKeys(allExpandKeys)
	})

	return {
		keyword,
		setKeyword,
		onSearchChange,
		onExpand,
		expandedKeys,
		autoExpandParent,
		filterDataSource,
	}
}


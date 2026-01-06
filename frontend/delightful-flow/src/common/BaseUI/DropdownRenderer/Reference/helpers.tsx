import { ExpressionSource, LabelTypeMap } from "@/DelightfulExpressionWidget/types"
import { TreeDataNode } from "antd"
import _ from "lodash"
import React from "react"
import { Splitor } from "./constants"

// Collect all node keys into expandedKeys
export const getDefaultExpandedKeys = (data: TreeDataNode[]) => {
	const keys = [] as React.Key[]
	data.forEach((item) => {
		// Keep function blocks collapsed by default
		if ((item.key as string)?.includes?.(LabelTypeMap.LabelFunc)) return
		if (item.children) {
			keys.push(item.key)
			keys.push(...getDefaultExpandedKeys(item.children))
		}
	})
	return keys
}

// [parentKey, childKey] => ['parentKey_childKey']
const getJoinedParentKeys = (keys: React.Key[]) => {
	return keys.reduce((acc, key) => {
		if (acc.length === 0) {
			acc.push(`${key}`)
			return acc
		}
		const lastKey = acc[acc.length - 1]
		acc.push(`${lastKey}${Splitor}${key}`)
		return acc
	}, [] as string[])
}

// Get all expandKeys related to the search keyword
export const getRelationExpandKeys = (
	data: TreeDataNode[],
	searchKeyword: string,
	parentKeys = [] as string[],
) => {
	const keys = [] as React.Key[]

	data.forEach((item) => {
		if ((item.title as string).includes(searchKeyword)) {
			keys.push(...(parentKeys || ([] as React.Key[])))
			keys.push(item.key)
		}
		if (item.children) {
			keys.push(
				...getRelationExpandKeys(item.children, searchKeyword, [
					...parentKeys,
					item.key as string,
				]),
			)
		}
	})

	const uniqKeys = [...new Set(keys)]

	return uniqKeys
}

// Get all data sources related to user input
export const getRelationDataSource = (dataSource: ExpressionSource, title: string) => {
	const relationDataSource = [] as ExpressionSource
	const cloneDataSource = _.cloneDeep(dataSource)

	cloneDataSource.forEach((item) => {
		// console.log(title, item)
		if ((item.title as string).includes(title) || item?.children?.length) {
			let children = item.children
			if (children?.length) {
				children = getRelationDataSource(children, title)
			}
			relationDataSource.push({ ...item, children })
		}
	})

	return relationDataSource
}

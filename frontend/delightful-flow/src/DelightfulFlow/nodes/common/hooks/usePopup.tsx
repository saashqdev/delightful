/**
 * Handle Node type dropdown state and behavior
 */

import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import { useMemoizedFn, useUpdateEffect } from "ahooks"
import { useEffect, useState } from "react"

type DropdownProps = {
	currentNode: DelightfulFlow.Node
	isSelected: boolean
}

export default function usePopup({ currentNode, isSelected }: DropdownProps) {
	const [openPopup, setOpenPopup] = useState(false)

	const [nodeName, setNodeName] = useState(currentNode?.name as string)

	const onNodeWrapperClick = useMemoizedFn(() => {
		setOpenPopup(false)
	})

	const closePopup = useMemoizedFn(() => {
		setOpenPopup(false)
	})

	const onDropdownClick = useMemoizedFn((event) => {
		event.preventDefault()
		event.stopPropagation()

		setOpenPopup(true)
	})

	useUpdateEffect(() => {
		if (!isSelected) {
			setOpenPopup(false)
		}
	}, [isSelected])

	useEffect(() => {
		if (currentNode?.name) {
			setNodeName(currentNode?.name)
		}
	}, [currentNode?.name])

	return {
		openPopup,
		onNodeWrapperClick,
		onDropdownClick,
		nodeName,
		setOpenPopup,
		closePopup,
	}
}


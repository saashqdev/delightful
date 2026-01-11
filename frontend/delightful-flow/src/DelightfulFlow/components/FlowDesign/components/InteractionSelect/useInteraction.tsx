/**
 * user偏好settings相关status和行为管理
 */
import { localStorageKeyMap } from "@/DelightfulFlow/constants"
import { useMemoizedFn, useMount } from "ahooks"
import { useEffect, useState } from "react"
import { Interactions } from "."
import { FLOW_EVENTS, flowEventBus } from "@/common/BaseUI/Select/constants"

export default function useInteraction() {
	const [interaction, setInteraction] = useState(Interactions.TouchPad)

	const [openInteractionSelect, setOpenInteractionSelect] = useState(false)

	const onInteractionChange = useMemoizedFn((updatedInteraction: Interactions) => {
		localStorage.setItem(localStorageKeyMap.InteractionMode, updatedInteraction)
		setInteraction(updatedInteraction)
	})

	const getInteractionMode = useMemoizedFn(() => {
		const storageInteraction = localStorage.getItem(localStorageKeyMap.InteractionMode)
		if (storageInteraction) {
			return storageInteraction as any
		}
		return Interactions.TouchPad
	})

	useMount(() => {
		setInteraction(getInteractionMode())
	})

	// listener外部event以关闭下拉菜单
	useEffect(() => {
		// cleanupfunctionarray
		const cleanupFunctions = []

		// 使用event总线注册和cleanupevent
		const cleanup = flowEventBus.on(FLOW_EVENTS.NODE_SELECTED, () => {
			setOpenInteractionSelect(false)
		})
		cleanupFunctions.push(cleanup)

		// returncleanupfunction
		return () => {
			cleanupFunctions.forEach((cleanup) => cleanup())
		}
	}, [])

	return {
		interaction,
		onInteractionChange,
		setOpenInteractionSelect,
		openInteractionSelect,
	}
}


/**
 * User preference settings related state and behavior management
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

	// Listen for external events to close dropdown menu
	useEffect(() => {
		// Cleanup function array
		const cleanupFunctions = []

		// Use event bus to register and cleanup events
		const cleanup = flowEventBus.on(FLOW_EVENTS.NODE_SELECTED, () => {
			setOpenInteractionSelect(false)
		})
		cleanupFunctions.push(cleanup)

		// Return cleanup function
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


import { useEffect, useRef, useState } from "react"
import { useMemoizedFn } from "ahooks"
import { autorun } from "mobx"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { interfaceStore } from "@/opensource/stores/interface"
import MessageFilePreviewStore from "@/opensource/stores/chatNew/messagePreview/FilePreviewStore"
import { calculatePanelSizes, LAYOUT_CONSTANTS, PanelIndex } from "../utils/panelSizeCalculator"

/**
 * Panel size management Hook
 * Responsible for managing panel size calculation and state updates in chat interface
 */
export function usePanelSizes() {
	const totalWidth = useRef(window.innerWidth - LAYOUT_CONSTANTS.WINDOW_MARGIN)
	const mainMinWidth = calculatePanelSizes.getMainMinWidth(conversationStore.topicOpen)

	const [sizes, setSizes] = useState<(number | undefined)[]>(
		calculatePanelSizes.getTwoPanelSizes(
			totalWidth.current,
			interfaceStore.chatSiderDefaultWidth,
		),
	)

	// Handle sidebar resize
	const handleSiderResize = useMemoizedFn((size: number[]) => {
		interfaceStore.setChatSiderDefaultWidth(size[0])

		setSizes((prevSizes) =>
			calculatePanelSizes.handleSiderResize(
				prevSizes,
				size,
				totalWidth.current,
				mainMinWidth,
			),
		)
	})

	// Handle input box resize
	const handleInputResize = useMemoizedFn((size: number[]) => {
		interfaceStore.setChatInputDefaultHeight(size[2])
	})

	// Handle layout adjustment for topic panel toggle
	useEffect(() => {
		return autorun(() => {
			const currentMainMinWidth = calculatePanelSizes.getMainMinWidth(
				conversationStore.topicOpen,
			)

			if (conversationStore.topicOpen) {
				setSizes((prevSizes) => {
					// Two-panel mode, no adjustment needed
					if (prevSizes.length < 3) return prevSizes

					const [siderWidth, mainWidth] = prevSizes

					// When topic is open, ensure main panel meets minimum width requirement
					const adjustedMainWidth = Math.max(mainWidth ?? 0, currentMainMinWidth)
					const adjustedFilePreviewWidth =
						totalWidth.current - (siderWidth ?? 0) - adjustedMainWidth

					return [siderWidth, adjustedMainWidth, adjustedFilePreviewWidth]
				})
			} else {
				setSizes((prevSizes) => {
					// Two-panel mode, no adjustment needed
					if (prevSizes.length < 3) return prevSizes

					const [siderWidth, , filePreviewWidth] = prevSizes

					// When topic is closed, maintain original layout
					return [
						siderWidth,
						totalWidth.current - (siderWidth ?? 0) - filePreviewWidth!,
						filePreviewWidth,
					]
				})
			}
		})
	}, [])

	// Handle layout adjustment for file preview panel toggle
	useEffect(() => {
		return autorun(() => {
			if (MessageFilePreviewStore.open) {
				// File preview open: use default ratio allocation
				setSizes(
					calculatePanelSizes.getFilePreviewOpenSizes(
						totalWidth.current,
						interfaceStore.chatSiderDefaultWidth,
					),
				)
			} else {
				// File preview closed: return to two-panel mode
				setSizes((prevSizes) =>
					calculatePanelSizes.getTwoPanelSizes(
						totalWidth.current,
						prevSizes[PanelIndex.Sider] ?? interfaceStore.chatSiderDefaultWidth,
					),
				)
			}
		})
	}, [])

	return {
		sizes,
		totalWidth: totalWidth.current,
		mainMinWidth,
		handleSiderResize,
		handleInputResize,
	}
}

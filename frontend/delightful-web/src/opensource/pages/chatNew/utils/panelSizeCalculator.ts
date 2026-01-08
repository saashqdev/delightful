// Layout constants
export const LAYOUT_CONSTANTS = {
	MAIN_MIN_WIDTH_WITH_TOPIC: 600,
	MAIN_MIN_WIDTH_WITHOUT_TOPIC: 400,
	FILE_PREVIEW_RATIO: 0.4,
	MAIN_PANEL_RATIO: 0.6,
	WINDOW_MARGIN: 100,
} as const

// Panel index enum
export const enum PanelIndex {
	Sider = 0,
	Main = 1,
	FilePreview = 2,
}

// Utility functions for calculating panel sizes
export const calculatePanelSizes = {
	// Calculate main panel minimum width
	getMainMinWidth: (hasTopicOpen: boolean): number => {
		return hasTopicOpen
			? LAYOUT_CONSTANTS.MAIN_MIN_WIDTH_WITH_TOPIC
			: LAYOUT_CONSTANTS.MAIN_MIN_WIDTH_WITHOUT_TOPIC
	},

	// Calculate two-panel layout
	getTwoPanelSizes: (totalWidth: number, siderWidth: number): [number, number] => {
		return [siderWidth, totalWidth - siderWidth]
	},

	// Calculate three-panel layout (including file preview)
	getThreePanelSizes: (
		totalWidth: number,
		siderWidth: number,
		filePreviewWidth: number,
		mainMinWidth: number,
	): [number, number, number] => {
		const remainingWidth = totalWidth - siderWidth
		const adjustedMainWidth = Math.max(remainingWidth - filePreviewWidth, mainMinWidth)
		const adjustedFilePreviewWidth = totalWidth - siderWidth - adjustedMainWidth

		return [siderWidth, adjustedMainWidth, adjustedFilePreviewWidth]
	},

	// Calculate default layout when file preview is open
	getFilePreviewOpenSizes: (totalWidth: number, siderWidth: number): [number, number, number] => {
		const availableWidth = totalWidth - siderWidth
		const mainWidth = availableWidth * LAYOUT_CONSTANTS.MAIN_PANEL_RATIO
		const filePreviewWidth = availableWidth * LAYOUT_CONSTANTS.FILE_PREVIEW_RATIO

		return [siderWidth, mainWidth, filePreviewWidth]
	},

	// Handle size recalculation when sidebar is resized
	handleSiderResize: (
		prevSizes: (number | undefined)[],
		newSizes: number[],
		totalWidth: number,
		mainMinWidth: number,
	): (number | undefined)[] => {
		// Two-panel mode
		if (newSizes.length === 2) {
			return newSizes
		}

		// Three-panel mode
		if (newSizes.length === 3) {
			const [newSiderWidth, , newFilePreviewWidth] = newSizes
			const [prevSiderWidth, , prevFilePreviewWidth] = prevSizes

			// Sidebar width unchanged, file preview panel is being adjusted
			if (prevSiderWidth === newSiderWidth) {
				return calculatePanelSizes.getThreePanelSizes(
					totalWidth,
					newSiderWidth,
					newFilePreviewWidth,
					mainMinWidth,
				)
			}

			// File preview width unchanged, sidebar is being adjusted
			if (prevFilePreviewWidth === newFilePreviewWidth) {
				return calculatePanelSizes.getThreePanelSizes(
					totalWidth,
					newSiderWidth,
					newFilePreviewWidth,
					mainMinWidth,
				)
			}

			// Default recalculation
			return calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				newSiderWidth,
				newFilePreviewWidth,
				mainMinWidth,
			)
		}

		return prevSizes
	},
}

// Default export for convenience
export default calculatePanelSizes

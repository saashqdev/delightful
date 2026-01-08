import { describe, it, expect } from "vitest"
// @ts-ignore
import { calculatePanelSizes } from "../panelSizeCalculator"

describe("calculatePanelSizes", () => {
	describe("getMainMinWidth", () => {
		it("should return correct min width when topic is open", () => {
			expect(calculatePanelSizes.getMainMinWidth(true)).toBe(600)
		})

		it("should return correct min width when topic is closed", () => {
			expect(calculatePanelSizes.getMainMinWidth(false)).toBe(400)
		})
	})

	describe("getTwoPanelSizes", () => {
		it("should calculate two panel sizes correctly", () => {
			const totalWidth = 1200
			const siderWidth = 240
			const result = calculatePanelSizes.getTwoPanelSizes(totalWidth, siderWidth)

			expect(result).toEqual([240, 960])
		})

		it("should handle edge cases", () => {
			const result = calculatePanelSizes.getTwoPanelSizes(500, 300)
			expect(result).toEqual([300, 200])
		})
	})

	describe("getThreePanelSizes", () => {
		it("should calculate three panel sizes with adequate space", () => {
			const totalWidth = 1200
			const siderWidth = 240
			const filePreviewWidth = 300
			const mainMinWidth = 400

			const result = calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				siderWidth,
				filePreviewWidth,
				mainMinWidth,
			)

			expect(result).toEqual([240, 660, 300])
		})

		it("should enforce main panel minimum width", () => {
			const totalWidth = 800
			const siderWidth = 200
			const filePreviewWidth = 500 // This would cause main panel to be smaller than minimum width
			const mainMinWidth = 400

			const result = calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				siderWidth,
				filePreviewWidth,
				mainMinWidth,
			)

			// Main panel should maintain minimum width, file preview panel is compressed
			expect(result).toEqual([200, 400, 200])
		})

		it("should handle minimum space requirements", () => {
			const totalWidth = 700
			const siderWidth = 200
			const filePreviewWidth = 200
			const mainMinWidth = 600 // Exceeds available space

			const result = calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				siderWidth,
				filePreviewWidth,
				mainMinWidth,
			)

			expect(result).toEqual([200, 600, -100]) // File preview compressed to negative value, requires further handling in actual use
		})
	})

	describe("getFilePreviewOpenSizes", () => {
		it("should calculate file preview open sizes with correct ratios", () => {
			const totalWidth = 1200
			const siderWidth = 240

			const result = calculatePanelSizes.getFilePreviewOpenSizes(totalWidth, siderWidth)

			// Available width: 1200 - 240 = 960
			// Main panel: 960 * 0.6 = 576
			// File preview: 960 * 0.4 = 384
			expect(result).toEqual([240, 576, 384])
		})

		it("should handle small total width", () => {
			const totalWidth = 600
			const siderWidth = 200

			const result = calculatePanelSizes.getFilePreviewOpenSizes(totalWidth, siderWidth)

			// Available width: 600 - 200 = 400
			// Main panel: 400 * 0.6 = 240
			// File preview: 400 * 0.4 = 160
			expect(result).toEqual([200, 240, 160])
		})
	})

	describe("handleSiderResize", () => {
		it("should handle two panel resize", () => {
			const prevSizes = [240, 960]
			const newSizes = [300, 900]
			const totalWidth = 1200
			const mainMinWidth = 400

			const result = calculatePanelSizes.handleSiderResize(
				prevSizes,
				newSizes,
				totalWidth,
				mainMinWidth,
			)

			expect(result).toEqual([300, 900])
		})

		it("should handle three panel resize when sider width unchanged", () => {
			const prevSizes = [240, 660, 300]
			const newSizes = [240, 560, 400]
			const totalWidth = 1200
			const mainMinWidth = 400

			const result = calculatePanelSizes.handleSiderResize(
				prevSizes,
				newSizes,
				totalWidth,
				mainMinWidth,
			)

			// Sidebar width unchanged, should recalculate main and file preview panels
			expect(result).toEqual([240, 560, 400])
		})

		it("should handle three panel resize when file preview width unchanged", () => {
			const prevSizes = [240, 660, 300]
			const newSizes = [280, 620, 300]
			const totalWidth = 1200
			const mainMinWidth = 400

			const result = calculatePanelSizes.handleSiderResize(
				prevSizes,
				newSizes,
				totalWidth,
				mainMinWidth,
			)

			// File preview width unchanged, should recalculate
			expect(result).toEqual([280, 620, 300])
		})

		it("should handle edge case with insufficient space", () => {
			const prevSizes = [200, 400, 200]
			const newSizes = [400, 200, 200] // Sidebar becomes very wide
			const totalWidth = 800
			const mainMinWidth = 500 // Main panel needs more space

			const result = calculatePanelSizes.handleSiderResize(
				prevSizes,
				newSizes,
				totalWidth,
				mainMinWidth,
			)

			// Should ensure main panel minimum width
			expect(result).toEqual([400, 500, -100])
		})

		it("should return previous sizes for invalid input", () => {
			const prevSizes = [240, 660, 300]
			const newSizes: number[] = [] // Empty array
			const totalWidth = 1200
			const mainMinWidth = 400

			const result = calculatePanelSizes.handleSiderResize(
				prevSizes,
				newSizes,
				totalWidth,
				mainMinWidth,
			)

			expect(result).toEqual(prevSizes)
		})
	})

	describe("integration scenarios", () => {
		it("should handle complete workflow: open file preview -> resize -> close file preview", () => {
			const totalWidth = 1200
			const siderWidth = 240

			// 1. Initial two-panel layout
			const initialSizes = calculatePanelSizes.getTwoPanelSizes(totalWidth, siderWidth)
			expect(initialSizes).toEqual([240, 960])

			// 2. Open file preview
			let threePanelSizes = calculatePanelSizes.getFilePreviewOpenSizes(
				totalWidth,
				siderWidth,
			)
			expect(threePanelSizes).toEqual([240, 576, 384])

			// 3. Adjust sidebar
			const newSizes = [300, 540, 360]
			threePanelSizes = calculatePanelSizes.handleSiderResize(
				threePanelSizes,
				newSizes,
				totalWidth,
				400,
			) as [number, number, number]
			expect(threePanelSizes).toEqual([300, 540, 360])

			// 4. Close file preview
			const finalSizes = calculatePanelSizes.getTwoPanelSizes(totalWidth, threePanelSizes[0])
			expect(finalSizes).toEqual([300, 900])
		})

		it("should maintain consistency when topic opens and closes", () => {
			const totalWidth = 1200
			const siderWidth = 240
			const filePreviewWidth = 300

			// Minimum width when topic is closed
			let minWidth = calculatePanelSizes.getMainMinWidth(false)
			expect(minWidth).toBe(400)

			let sizes = calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				siderWidth,
				filePreviewWidth,
				minWidth,
			)
			expect(sizes).toEqual([240, 660, 300])

			// Minimum width when topic is open
			minWidth = calculatePanelSizes.getMainMinWidth(true)
			expect(minWidth).toBe(600)

			sizes = calculatePanelSizes.getThreePanelSizes(
				totalWidth,
				siderWidth,
				filePreviewWidth,
				minWidth,
			)
			expect(sizes).toEqual([240, 660, 300]) // Already meets minimum width requirement
		})
	})
})

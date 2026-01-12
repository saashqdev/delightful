import { renderHook } from "@testing-library/react"
import { describe, expect, it, vi } from "vitest"
import { useTableI18n } from "../useTableI18n"

// Mock react-i18next
vi.mock("react-i18next", () => ({
	useTranslation: vi.fn(() => ({
		t: (key: string) => {
			const translations: Record<string, string> = {
				"markdownTable.showMore": "Show More",
				"markdownTable.rowDetails": "Row Details",
				"markdownTable.clickToExpand": "Click to Expand Full Content",
				"markdownTable.showAllColumns": "Show All Columns",
				"markdownTable.hideAllColumns": "Hide",
				"markdownTable.defaultColumn": "Column",
			}
			return translations[key] || key
		},
	})),
}))

describe("useTableI18n", () => {
	it("should return correct translation text", () => {
		const { result } = renderHook(() => useTableI18n())

		expect(result.current.showMore).toBe("Show More")
		expect(result.current.rowDetails).toBe("Row Details")
		expect(result.current.clickToExpand).toBe("Click to Expand Full Content")
		expect(result.current.showAllColumns).toBe("Show All Columns")
		expect(result.current.hideAllColumns).toBe("Hide")
		expect(result.current.defaultColumn).toBe("Column")
	})

	it("should contain all required translation keys", () => {
		const { result } = renderHook(() => useTableI18n())

		expect(result.current).toHaveProperty("showMore")
		expect(result.current).toHaveProperty("rowDetails")
		expect(result.current).toHaveProperty("clickToExpand")
		expect(result.current).toHaveProperty("showAllColumns")
		expect(result.current).toHaveProperty("hideAllColumns")
		expect(result.current).toHaveProperty("defaultColumn")
	})

	it("should return string type translation values", () => {
		const { result } = renderHook(() => useTableI18n())

		expect(typeof result.current.showMore).toBe("string")
		expect(typeof result.current.rowDetails).toBe("string")
		expect(typeof result.current.clickToExpand).toBe("string")
		expect(typeof result.current.showAllColumns).toBe("string")
		expect(typeof result.current.hideAllColumns).toBe("string")
		expect(typeof result.current.defaultColumn).toBe("string")
	})
})

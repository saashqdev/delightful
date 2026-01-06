import { describe, it, expect, vi } from "vitest"
import { fetchPaddingData } from "../request"

describe("fetchPadingData", () => {
	it("handles a single request", async () => {
		const mockFetch = vi.fn().mockResolvedValue({
			items: [1, 2, 3],
			has_more: false,
			page_token: "123",
		})

		const result = await fetchPaddingData(mockFetch)

		expect(mockFetch).toHaveBeenCalledTimes(1)
		expect(mockFetch).toHaveBeenCalledWith({ page_token: "" })
		expect(result).toEqual([1, 2, 3])
	})

	it("handles multiple requests", async () => {
		const mockFetch = vi
			.fn()
			.mockResolvedValueOnce({
				items: [1, 2, 3],
				has_more: true,
				page_token: "123",
			})
			.mockResolvedValueOnce({
				items: [4, 5, 6],
				has_more: false,
				next_offset: "",
			})

		const result = await fetchPaddingData(mockFetch)

		expect(mockFetch).toHaveBeenCalledTimes(2)
		expect(mockFetch).toHaveBeenNthCalledWith(1, { page_token: "" })
		expect(mockFetch).toHaveBeenNthCalledWith(2, { page_token: "123" })
		expect(result).toEqual([1, 2, 3, 4, 5, 6])
	})

	it("uses resolveArray option correctly", async () => {
		const mockFetch = vi.fn().mockResolvedValue({
			items: [1, 2, 3],
			has_more: false,
			page_token: "123",
		})

		const result = await fetchPaddingData(mockFetch)

		expect(mockFetch).toHaveBeenCalledTimes(1)
		expect(mockFetch).toHaveBeenCalledWith({ page_token: "" })
		expect(result).toEqual([1, 2, 3])
	})

	it("handles prevData argument", async () => {
		const mockFetch = vi.fn().mockResolvedValue({
			items: [4, 5, 6],
			has_more: false,
			next_offset: 6,
		})

		const result = await fetchPaddingData(mockFetch, [1, 2, 3])

		expect(mockFetch).toHaveBeenCalledTimes(1)
		expect(mockFetch).toHaveBeenCalledWith({ page_token: "" })
		expect(result).toEqual([1, 2, 3, 4, 5, 6])
	})

	it("handles empty data", async () => {
		const mockFetch = vi.fn().mockResolvedValue({
			items: [],
			has_more: false,
			next_offset: 0,
		})

		const result = await fetchPaddingData(mockFetch)

		expect(mockFetch).toHaveBeenCalledTimes(1)
		expect(result).toEqual([])
	})

	it("handles request errors", async () => {
		const mockFetch = vi.fn().mockRejectedValue(new Error("Network error"))

		await expect(fetchPaddingData(mockFetch)).rejects.toThrow("Network error")
	})
})

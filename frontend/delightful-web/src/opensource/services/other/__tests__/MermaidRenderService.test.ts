import { describe, it, expect, vi, beforeEach, Mock } from "vitest"
// @ts-ignore
import MermaidRenderService from "../MermaidRenderService"
import type { RenderResult } from "mermaid"

// Mock the database module
vi.mock("@/opensource/database/mermaid", () => ({
	initMermaidDb: vi.fn(() => ({
		mermaid: {
			toArray: vi.fn().mockResolvedValue([
				{
					data: "cached-chart",
					svg: "<svg>cached</svg>",
					png: "cached-png-data",
				},
			]),
			put: vi.fn().mockResolvedValue(undefined),
			where: vi.fn(() => ({
				equals: vi.fn(() => ({
					first: vi.fn().mockResolvedValue(null),
				})),
			})),
		},
	})),
}))

describe("MermaidRenderService", () => {
	let service: typeof MermaidRenderService
	let mockDb: any

	beforeEach(() => {
		// Reset the service instance
		service = MermaidRenderService
		mockDb = service.db

		// Clear cache before each test
		service.cacheSvg.clear()

		// Reset mocks
		vi.clearAllMocks()
	})

	describe("constructor", () => {
		it("should initialize the database", () => {
			// Arrange & Act & Assert
			// The constructor is already called when the module is imported
			// Just verify the service has been initialized with the database
			expect(service.db).toBeDefined()
			expect(service.cacheSvg).toBeInstanceOf(Map)
		})
	})

	describe("isErrorSvg", () => {
		it("should return true when svg contains error pattern", () => {
			// Arrange
			const errorSvg = "<svg><g></g></svg>"

			// Act
			const result = service.isErrorSvg(errorSvg)

			// Assert
			expect(result).toBe(true)
		})

		it("should return false when svg does not contain error pattern", () => {
			// Arrange
			const validSvg = '<svg><path d="M10 10"></path></svg>'

			// Act
			const result = service.isErrorSvg(validSvg)

			// Assert
			expect(result).toBe(false)
		})

		it("should return false for empty svg", () => {
			// Arrange
			const emptySvg = ""

			// Act
			const result = service.isErrorSvg(emptySvg)

			// Assert
			expect(result).toBe(false)
		})
	})

	describe("cache", () => {
		it("should cache new svg successfully", async () => {
			// Arrange
			const chart = "flowchart TD\n    A --> B"
			const renderResult: RenderResult = {
				svg: "<svg>test</svg>",
				diagramType: "flowchart",
			}

			// Act
			const result = service.cache(chart, renderResult)

			// Assert
			expect(result).toEqual({
				data: chart,
				svg: renderResult.svg,
				diagramType: renderResult.diagramType,
				png: "",
			})
			expect(service.cacheSvg.has(chart)).toBe(true)
			expect(mockDb.mermaid.put).toHaveBeenCalledWith({
				data: chart,
				svg: renderResult.svg,
				diagramType: renderResult.diagramType,
				png: "",
			})
		})

		it("should return existing cache when svg is the same", () => {
			// Arrange
			const chart = "flowchart TD\n    A --> B"
			const existingCache = {
				data: chart,
				svg: "<svg>test</svg>",
				png: "existing-png",
			}
			service.cacheSvg.set(chart, existingCache)

			const renderResult: RenderResult = {
				svg: "<svg>test</svg>",
				diagramType: "flowchart",
			}

			// Act
			const result = service.cache(chart, renderResult)

			// Assert
			expect(result).toEqual(existingCache)
			expect(mockDb.mermaid.put).not.toHaveBeenCalled()
		})

		it("should return undefined for error svg", () => {
			// Arrange
			const chart = "invalid chart"
			const errorRenderResult: RenderResult = {
				svg: "<svg><g></g></svg>",
				diagramType: "flowchart",
			}

			// Act
			const result = service.cache(chart, errorRenderResult)

			// Assert
			expect(result).toBeUndefined()
			expect(service.cacheSvg.has(chart)).toBe(false)
			expect(mockDb.mermaid.put).not.toHaveBeenCalled()
		})

		it("should handle database put error gracefully", async () => {
			// Arrange
			const consoleSpy = vi.spyOn(console, "error").mockImplementation(() => {})
			mockDb.mermaid.put.mockRejectedValue(new Error("Database error"))

			const chart = "flowchart TD\n    A --> B"
			const renderResult: RenderResult = {
				svg: "<svg>test</svg>",
				diagramType: "flowchart",
			}

			// Act
			const result = service.cache(chart, renderResult)

			// Assert
			expect(result).toBeDefined()
			expect(service.cacheSvg.has(chart)).toBe(true)

			// Wait for async operation to complete and check error handling
			await new Promise((resolve) => setTimeout(resolve, 10))
			expect(consoleSpy).toHaveBeenCalledWith("Cache failed", expect.any(Error))
			consoleSpy.mockRestore()
		})
	})

	describe("getCache", () => {
		it("should return cache from memory when available", async () => {
			// Arrange
			const chart = "test-chart"
			const cachedData = {
				data: chart,
				svg: "<svg>cached</svg>",
				png: "cached-png",
			}
			service.cacheSvg.set(chart, cachedData)

			// Act
			const result = await service.getCache(chart)

			// Assert
			expect(result).toEqual(cachedData)
			expect(mockDb.mermaid.where).not.toHaveBeenCalled()
		})

		it("should fetch from database when not in memory cache", async () => {
			// Arrange
			const chart = "test-chart"
			const dbData = {
				data: chart,
				svg: "<svg>from-db</svg>",
				png: "db-png",
			}

			const mockFirst = vi.fn().mockResolvedValue(dbData)
			const mockEquals = vi.fn().mockReturnValue({ first: mockFirst })
			mockDb.mermaid.where.mockReturnValue({ equals: mockEquals })

			// Act
			const result = await service.getCache(chart)

			// Assert
			expect(result).toEqual(dbData)
			expect(mockDb.mermaid.where).toHaveBeenCalledWith("data")
			expect(mockEquals).toHaveBeenCalledWith(chart)
			expect(mockFirst).toHaveBeenCalled()
			expect(service.cacheSvg.has(chart)).toBe(true)
		})

		it("should return undefined when not found in cache or database", async () => {
			// Arrange
			const chart = "non-existent-chart"

			const mockFirst = vi.fn().mockResolvedValue(null)
			const mockEquals = vi.fn().mockReturnValue({ first: mockFirst })
			mockDb.mermaid.where.mockReturnValue({ equals: mockEquals })

			// Act
			const result = await service.getCache(chart)

			// Assert
			expect(result).toBeUndefined()
			expect(mockDb.mermaid.where).toHaveBeenCalledWith("data")
			expect(mockEquals).toHaveBeenCalledWith(chart)
			expect(mockFirst).toHaveBeenCalled()
		})
	})

	describe("fix", () => {
		it("should return empty string for undefined input", () => {
			// Arrange
			const input = undefined

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("")
		})

		it("should return empty string for null input", () => {
			// Arrange
			const input = null as any

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("")
		})

		it("should return empty string for empty string input", () => {
			// Arrange
			const input = ""

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("")
		})

		it("should replace Chinese punctuation with English punctuation", () => {
			// Arrange
			const input =
				"This is a test, contains Chinese symbols: semicolon; exclamation! question?"

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe(
				"This is a test, contains Chinese symbols: semicolon; exclamation! question? ",
			)
		})

		it("should replace Chinese brackets and quotes", () => {
			// Arrange
			const input = 'Test (parentheses) and"quotes"also【square brackets】'

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe('Test (parentheses) and"quotes"also [square brackets] ')
		})

		it("should replace Chinese symbols with their equivalents", () => {
			// Arrange
			const input = "Temperature: 25°C, Price: ¥100"

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("Temperature: 25degrees C, Price: yuan100")
		})

		it("should handle mixed Chinese and English punctuation", () => {
			// Arrange
			const input = "Hello, world! This is a test."

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("Hello, world! This is a test.")
		})

		it("should preserve text without Chinese punctuation", () => {
			// Arrange
			const input = "This is English text with normal punctuation."

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("This is English text with normal punctuation.")
		})

		it("should handle special characters correctly", () => {
			// Arrange
			const input = "Hyphen-and tilde~ remain unchanged"

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("Hyphen-and tilde~ remain unchanged")
		})

		it("should handle multiple occurrences of the same punctuation", () => {
			// Arrange
			const input = "Multiple, commas, test, result."

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe("Multiple, commas, test, result.")
		})

		it("should fix Chinese punctuation in gantt chart with talent development plan", () => {
			// Arrange
			const input = `gantt
       title Mobile Talent Pipeline Development Plan
       dateFormat  YYYY-Q
       section iOS Development
       Mentorship Training        : active,  des1, 2023-Q3, 2024-Q1
       External Expert Workshop    :          des2, 2024-Q2, 2024-Q3
       section Flutter Architecture
       Tech Research Team      :          des3, 2023-Q4, 2024-Q2`

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe(`gantt
       title Mobile Talent Pipeline Development Plan
       dateFormat  YYYY-Q
       section iOS Development
       Mentorship Training        : active,  des1, 2023-Q3, 2024-Q1
       External Expert Workshop    :          des2, 2024-Q2, 2024-Q3
       section Flutter Architecture
       Tech Research Team      :          des3, 2023-Q4, 2024-Q2`)
		})

		it("should fix Chinese punctuation in pie chart with talent distribution", () => {
			// Arrange
			const input = `pie
    title Talent Assessment Grid Distribution
    "Superstars (3%) " :  6
    "Performance Stars (12%) " :  22
    "Core Force (28%) " :  52
    "Skilled Employees (25%) " :  47
    "Stable Employees (20%) " :  37
    "Potential Stars (8%) " :  15
    "To Develop (3%) " :  6
    "Gap Employees (1%) " :  2`

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe(`pie
    title Talent Assessment Grid Distribution
    "Superstars (3%) " :  6
    "Performance Stars (12%) " :  22
    "Core Force (28%) " :  52
    "Skilled Employees (25%) " :  47
    "Stable Employees (20%) " :  37
    "Potential Stars (8%) " :  15
    "To Develop (3%) " :  6
    "Gap Employees (1%) " :  2`)
		})

		it("should fix Chinese punctuation in gantt chart with ROI analysis", () => {
			// Arrange
			const input = `gantt
    title HR ROI Analysis Model
    section Key Metrics
    Talent Retention Rate : a1, 2023-07, 30d
    High Potential ROI : a2, after a1, 45d
    Turnover Cost Alert : a3, after a2, 20d`

			// Act
			const result = service.fix(input)

			// Assert
			expect(result).toBe(`gantt
    title HR ROI Analysis Model
    section Key Metrics
    Talent Retention Rate : a1, 2023-07, 30d
    High Potential ROI : a2, after a1, 45d
    Turnover Cost Alert : a3, after a2, 20d`)
		})
	})
})

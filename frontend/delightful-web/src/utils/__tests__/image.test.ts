import { describe, it, expect, vi, beforeEach, afterEach } from "vitest"
import { convertSvgToPng } from "../image"

describe("image utils", () => {
	// Simulate browser environment
	let origCreateElement: typeof document.createElement
	let mockCanvas: HTMLCanvasElement
	let mockContext: CanvasRenderingContext2D
	let mockImage: HTMLImageElement

	// Mock return value for toDataURL
	const mockPngUrl = "data:image/png;base64,mockPngData"

	beforeEach(() => {
		// Save original method
		origCreateElement = document.createElement

		// Mock context
		mockContext = {
			drawImage: vi.fn(),
		} as unknown as CanvasRenderingContext2D

		// Mock canvas
		mockCanvas = {
			getContext: vi.fn().mockReturnValue(mockContext),
			toDataURL: vi.fn().mockReturnValue(mockPngUrl),
			width: 0,
			height: 0,
		} as unknown as HTMLCanvasElement

		// Mock Image
		mockImage = {} as HTMLImageElement
		Object.defineProperties(mockImage, {
			onload: { value: null, writable: true },
			onerror: { value: null, writable: true },
			src: { value: "", writable: true },
			naturalWidth: { value: 300 },
			naturalHeight: { value: 200 },
		})

		// Mock document.createElement
		document.createElement = vi.fn().mockImplementation((tagName: string) => {
			if (tagName === "canvas") {
				return mockCanvas
			}
			if (tagName === "img") {
				// Use setTimeout to simulate async image load
				setTimeout(() => {
					if (mockImage.onload && typeof mockImage.onload === "function") {
						mockImage.onload.call(mockImage, new Event("load"))
					}
				}, 0)
				return mockImage
			}
			return origCreateElement.call(document, tagName)
		})

		// Mock DOMParser
		const mockDOMParser = function () {
			return {
				parseFromString() {
					return {
						documentElement: {
							hasAttribute: (attr: string) => {
								if (attr === "width") return true
								if (attr === "height") return true
								if (attr === "viewBox") return false
								return false
							},
							getAttribute: (attr: string) => {
								if (attr === "width") return "300"
								if (attr === "height") return "200"
								return null
							},
						},
					}
				},
			}
		}
		vi.stubGlobal("DOMParser", mockDOMParser)

		// Mock btoa (Base64 encoding)
		vi.stubGlobal("btoa", () => "mockBase64String")

		// Mock encodeURIComponent
		vi.stubGlobal("encodeURIComponent", () => "encodedSvg")

		// Mock unescape
		vi.stubGlobal("unescape", () => "unescapedSvg")
	})

	afterEach(() => {
		// Restore original method
		document.createElement = origCreateElement
		vi.restoreAllMocks()
	})

	describe("convertSvgToPng", () => {
		it("converts SVG to PNG successfully", async () => {
			const svg = "<svg width='100' height='100'></svg>"

			const result = await convertSvgToPng(svg)

			// Verify canvas and image elements are created
			expect(document.createElement).toHaveBeenCalledWith("canvas")
			expect(document.createElement).toHaveBeenCalledWith("img")

			// Verify canvas toDataURL is called
			expect(mockCanvas.toDataURL).toHaveBeenCalledWith("image/png")

			// Verify correct data URL is returned
			expect(result).toBe(mockPngUrl)
		})

		it("uses provided width and original aspect ratio for height", async () => {
			const svg = "<svg width='300' height='200'></svg>"
			const width = 600

			await convertSvgToPng(svg, width)

			// Verify canvas dimensions are set correctly
			expect(mockCanvas.width).toBe(600)
			expect(mockCanvas.height).toBe(400) // Preserve original 300:200 ratio

			// Verify drawImage is called with expected args
			expect(mockContext.drawImage).toHaveBeenCalledWith(mockImage, 0, 0, 600, 400)
		})

		it("caps height when height parameter is provided", async () => {
			const svg = "<svg width='300' height='200'></svg>"
			const width = 600
			const height = 300 // Ratio would be 400, but we cap at 300

			await convertSvgToPng(svg, width, height)

			// Verify canvas dimensions are capped
			expect(mockCanvas.width).toBe(600)
			expect(mockCanvas.height).toBe(300) // Capped at 300

			// Verify drawImage call
			expect(mockContext.drawImage).toHaveBeenCalledWith(mockImage, 0, 0, 600, 300)
		})

		it("caps height when provided height is below aspect ratio result", async () => {
			// Mock SVG aspect ratio 2:1
			const mockParser21 = function () {
				return {
					parseFromString() {
						return {
							documentElement: {
								hasAttribute: (attr: string) => {
									return attr === "width" || attr === "height"
								},
								getAttribute: (attr: string) => {
									if (attr === "width") return "200"
									if (attr === "height") return "100" // 2:1 ratio
									return null
								},
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParser21)

			const svg = "<svg width='200' height='100'></svg>" // 2:1 ratio
			const width = 500
			const height = 200 // Ratio would be 250, but we cap at 200

			await convertSvgToPng(svg, width, height)

			expect(mockCanvas.width).toBe(500)
			expect(mockCanvas.height).toBe(200) // Verify capped at 200
		})

		it("does not cap height when height is omitted", async () => {
			// Mock SVG ratio as 1:2
			const mockParser12 = function () {
				return {
					parseFromString() {
						return {
							documentElement: {
								hasAttribute: (attr: string) => {
									return attr === "width" || attr === "height"
								},
								getAttribute: (attr: string) => {
									if (attr === "width") return "100"
									if (attr === "height") return "200" // 1:2 ratio
									return null
								},
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParser12)

			const svg = "<svg width='100' height='200'></svg>" // 1:2 ratio
			const width = 300

			await convertSvgToPng(svg, width)

			expect(mockCanvas.width).toBe(300)
			expect(mockCanvas.height).toBe(600) // Should preserve 1:2 original ratio
		})

		it("uses viewBox ratio when width/height are missing", async () => {
			// Mock SVG with only viewBox
			const mockParserViewBox = function () {
				return {
					parseFromString() {
						return {
							documentElement: {
								hasAttribute: (attr: string) => {
									if (attr === "width") return false
									if (attr === "height") return false
									if (attr === "viewBox") return true
									return false
								},
								getAttribute: (attr: string) => {
									if (attr === "viewBox") return "0 0 400 300"
									return null
								},
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParserViewBox)

			const svg = "<svg viewBox='0 0 400 300'></svg>"
			const width = 800

			await convertSvgToPng(svg, width)

			expect(mockCanvas.width).toBe(800)
			expect(mockCanvas.height).toBe(600) // Preserve original 400:300 ratio
		})

		it("falls back to intrinsic image size when no dimensions provided", async () => {
			// Mock SVG without size information
			const mockParserNoSize = function () {
				return {
					parseFromString() {
						return {
							documentElement: {
								hasAttribute: () => false,
								getAttribute: () => null,
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParserNoSize)

			const svg = "<svg></svg>"
			const width = 600

			// Use a different mock image object to test intrinsic sizes
			const tempImage = {} as HTMLImageElement
			Object.defineProperties(tempImage, {
				onload: { value: null, writable: true },
				onerror: { value: null, writable: true },
				src: { value: "", writable: true },
				naturalWidth: { value: 400 },
				naturalHeight: { value: 300 },
			})

			// Temporarily replace mockImage
			const origMockImage = mockImage
			mockImage = tempImage

			document.createElement = vi.fn().mockImplementation((tagName: string) => {
				if (tagName === "canvas") return mockCanvas
				if (tagName === "img") {
					setTimeout(() => {
						if (mockImage.onload && typeof mockImage.onload === "function") {
							mockImage.onload.call(mockImage, new Event("load"))
						}
					}, 0)
					return mockImage
				}
				return origCreateElement.call(document, tagName)
			})

			await convertSvgToPng(svg, width)

			expect(mockCanvas.width).toBe(600)
			expect(mockCanvas.height).toBe(450) // Preserve 400:300 ratio

			// Restore original mockImage
			mockImage = origMockImage
		})

		it("handles SVG load errors", async () => {
			const svg = "<svg></svg>"

			// Simulate image load error
			document.createElement = vi.fn().mockImplementation((tagName: string) => {
				if (tagName === "canvas") {
					return mockCanvas
				}
				if (tagName === "img") {
					setTimeout(() => {
						if (mockImage.onerror && typeof mockImage.onerror === "function") {
							mockImage.onerror.call(mockImage, new Event("error"))
						}
					}, 0)
					return mockImage
				}
				return origCreateElement.call(document, tagName)
			})

			await expect(convertSvgToPng(svg)).rejects.toThrow("SVG image failed to load")
		})

		it("handles missing canvas context", async () => {
			const svg = "<svg></svg>"

			// Simulate failing to acquire canvas context
			mockCanvas.getContext = vi.fn().mockReturnValue(null)

			await expect(convertSvgToPng(svg)).rejects.toThrow("Unable to acquire canvas context")
		})

		it("handles toDataURL conversion failures", async () => {
			const svg = "<svg></svg>"

			// Simulate toDataURL failure
			mockCanvas.toDataURL = vi.fn().mockImplementation(() => {
				throw new Error("Conversion failed")
			})

			await expect(convertSvgToPng(svg)).rejects.toThrow("PNG conversion failed")
		})
	})
})

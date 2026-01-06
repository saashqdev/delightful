import { describe, it, expect, vi, beforeEach, afterEach } from "vitest"
import { convertSvgToPng } from "../image"

describe("image utils", () => {
	// 模拟浏览器环境
	// Simulate browser environment
	let origCreateElement: typeof document.createElement
	let mockCanvas: HTMLCanvasElement
	let mockContext: CanvasRenderingContext2D
	let mockImage: HTMLImageElement

	// 模拟toDataURL方法的返回值
	// Mock return value for toDataURL
	const mockPngUrl = "data:image/png;base64,mockPngData"

	beforeEach(() => {
		// 保存原始方法
		// Save original method
		origCreateElement = document.createElement

		// 模拟context
		// Mock context
		mockContext = {
			drawImage: vi.fn(),
		} as unknown as CanvasRenderingContext2D

		// 模拟canvas
		// Mock canvas
		mockCanvas = {
			getContext: vi.fn().mockReturnValue(mockContext),
			toDataURL: vi.fn().mockReturnValue(mockPngUrl),
			width: 0,
			height: 0,
		} as unknown as HTMLCanvasElement

		// 模拟Image
		// Mock Image
		mockImage = {} as HTMLImageElement
		Object.defineProperties(mockImage, {
			onload: { value: null, writable: true },
			onerror: { value: null, writable: true },
			src: { value: "", writable: true },
			naturalWidth: { value: 300 },
			naturalHeight: { value: 200 },
		})

		// 模拟document.createElement
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

		// 模拟DOMParser
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

		// 模拟btoa (Base64编码)
		// Mock btoa (Base64 encoding)
		vi.stubGlobal("btoa", () => "mockBase64String")

		// 模拟encodeURIComponent
		// Mock encodeURIComponent
		vi.stubGlobal("encodeURIComponent", () => "encodedSvg")

		// 模拟unescape
		vi.stubGlobal("unescape", () => "unescapedSvg")
		// Mock unescape
	})

	afterEach(() => {
		// 恢复原始方法
		// Restore original method
		document.createElement = origCreateElement
		vi.restoreAllMocks()
	})

	describe("convertSvgToPng", () => {
		it("converts SVG to PNG successfully", async () => {
			const svg = "<svg width='100' height='100'></svg>"

			const result = await convertSvgToPng(svg)

			// 验证创建canvas和图片元素
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
			expect(mockCanvas.height).toBe(400) // 保持300:200的原始比例

			// Verify drawImage is called with expected args
			expect(mockContext.drawImage).toHaveBeenCalledWith(mockImage, 0, 0, 600, 400)
		})

		it("caps height when height parameter is provided", async () => {
			const svg = "<svg width='300' height='200'></svg>"
			const width = 600
			const height = 300 // 比例计算应为400，但我们限制为300

			await convertSvgToPng(svg, width, height)

			// Verify canvas dimensions are capped
			expect(mockCanvas.width).toBe(600)
			expect(mockCanvas.height).toBe(300) // 被限制为300

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
									if (attr === "height") return "100" // 2:1比例
									return null
								},
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParser21)

			const svg = "<svg width='200' height='100'></svg>" // 2:1比例
			const width = 500
			const height = 200 // 按比例应为250，但我们限制为200

			await convertSvgToPng(svg, width, height)

			expect(mockCanvas.width).toBe(500)
			expect(mockCanvas.height).toBe(200) // 验证被限制为200
		})

		it("does not cap height when height is omitted", async () => {
			// 模拟SVG比例为1:2
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
									if (attr === "height") return "200" // 1:2比例
									return null
								},
							},
						}
					},
				}
			}
			vi.stubGlobal("DOMParser", mockParser12)

			const svg = "<svg width='100' height='200'></svg>" // 1:2比例
			const width = 300

			await convertSvgToPng(svg, width)

			expect(mockCanvas.width).toBe(300)
			expect(mockCanvas.height).toBe(600) // 应保持1:2的原始比例
		})

		it("uses viewBox ratio when width/height are missing", async () => {
			// 模拟只有viewBox的SVG
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
			expect(mockCanvas.height).toBe(600) // 保持400:300的原始比例
		})

		it("falls back to intrinsic image size when no dimensions provided", async () => {
			// 模拟没有尺寸信息的SVG
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

			// 使用新的mock图像对象来测试不同的天然尺寸
			const tempImage = {} as HTMLImageElement
			Object.defineProperties(tempImage, {
				onload: { value: null, writable: true },
				onerror: { value: null, writable: true },
				src: { value: "", writable: true },
				naturalWidth: { value: 400 },
				naturalHeight: { value: 300 },
			})

			// 临时替换mockImage
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
			expect(mockCanvas.height).toBe(450) // 保持400:300的比例

			// 恢复原始mockImage
			mockImage = origMockImage
		})

		it("handles SVG load errors", async () => {
			const svg = "<svg></svg>"

			// 模拟图片加载错误
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

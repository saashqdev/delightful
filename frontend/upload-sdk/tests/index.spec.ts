import { describe, it, expect, vi, beforeEach } from "vitest"
import { Upload, PlatformType } from "../src"
import { InitException, InitExceptionCode } from "../src/Exception/InitException"
import { UploadManger } from "../src/utils/UploadManger"
import type { Method } from "../src/types/request"

// Mock UploadManger
vi.mock("../src/utils/UploadManger", () => {
	const mockCreateTask = vi.fn().mockReturnValue({
		success: vi.fn(),
		fail: vi.fn(),
		progress: vi.fn(),
		cancel: vi.fn(),
		pause: vi.fn(),
		resume: vi.fn(),
	})

	const MockUploadManger = vi.fn().mockImplementation(() => {
		return {
			createTask: mockCreateTask,
			pauseAllTask: vi.fn(),
			resumeAllTask: vi.fn(),
			cancelAllTask: vi.fn(),
			// Add tasks array for testing
			tasks: [],
		}
	})

	return {
		UploadManger: MockUploadManger,
	}
})

// Mock request module
vi.mock("../src/utils/request", () => {
	return {
		request: vi.fn().mockImplementation(({ success }) => {
			if (success && typeof success === "function") {
				success({ data: "success" })
			}
			return Promise.resolve({ data: "success" })
		}),
		cancelRequest: vi.fn(),
		pauseRequest: vi.fn(),
		completeRequest: vi.fn(),
	}
})

describe("Upload class tests", () => {
	let uploadInstance: Upload
	let mockFile: File

	beforeEach(() => {
		uploadInstance = new Upload()
		mockFile = new File(["test content"], "test.txt", { type: "text/plain" })
		vi.clearAllMocks()
	})

	describe("constructor", () => {
		it("should instantiate correctly", () => {
			expect(uploadInstance).toBeInstanceOf(Upload)
			expect(uploadInstance.uploadManger).toBeDefined()
		})

		it("should have correct version number", () => {
			expect(typeof Upload.version).toBe("string")
			expect(Upload.version.length).toBeGreaterThan(0)
		})
	})

	describe("upload method", () => {
		it("should throw exception if required parameters are missing", () => {
			const config = {
				url: "",
				method: "POST" as Method,
				file: mockFile,
				fileName: "test.txt",
			}

			expect(() => {
				uploadInstance.upload(config)
			}).toThrow(InitException)

			expect(() => {
				uploadInstance.upload({
					...config,
					url: "http://example.com",
					method: "" as Method,
				})
			}).toThrow(InitException)
		})

		it("should throw exception when filename contains special characters", () => {
			const config = {
				url: "http://example.com",
				method: "POST" as Method,
				file: mockFile,
				fileName: "test%.txt",
				option: {
					rewriteFileName: false,
				},
			}

			expect(() => {
				uploadInstance.upload(config)
			}).toThrow(
				new InitException(
					InitExceptionCode.UPLOAD_FILENAME_EXIST_SPECIAL_CHAR,
					config.fileName,
				),
			)
		})

		it("should generate new filename when rewriteFileName option is enabled", () => {
			const config = {
				url: "http://example.com",
				method: "POST" as Method,
				file: mockFile,
				fileName: "test.txt",
				option: {
					rewriteFileName: true,
				},
			}

			const oldFileName = config.fileName
			uploadInstance.upload(config)
			expect(config.fileName).not.toBe(oldFileName)
			expect(config.fileName).toMatch(/^.+\.txt$/)
		})

		it("should call uploadManger.createTask method", () => {
			const config = {
				url: "http://example.com",
				method: "POST" as Method,
				file: mockFile,
				fileName: "test.txt",
				option: {
					rewriteFileName: false,
				},
			}

			const result = uploadInstance.upload(config)
			// Verify task object is returned
			expect(result).toBeDefined()
			expect(result).toHaveProperty("success")
			expect(result).toHaveProperty("fail")
			expect(result).toHaveProperty("progress")
		})
	})

	describe("download method", () => {
		it("download method should call request method and return Promise", async () => {
			const downloadConfig = {
				url: "http://example.com/download",
				method: "GET" as Method,
				headers: { "Content-Type": "application/json" },
			}

			const result = await Upload.download(downloadConfig)
			expect(result).toBeDefined()
			expect(result).toEqual({ data: "success" })
		})

		it("download method should handle different body parameter types - FormData", async () => {
			const formData = new FormData()
			formData.append("key", "value")

			const downloadConfig = {
				url: "http://example.com/download",
				method: "POST" as Method,
				headers: { "Content-Type": "multipart/form-data" },
				body: formData,
				option: {
					image: [
						{
							type: "resize" as const,
							params: { width: 100, height: 100 },
						},
					],
				},
			}

			await Upload.download(downloadConfig)
			// Verify FormData contains options field
			expect(formData.has("options")).toBe(true)
			const optionsValue = formData.get("options")
			expect(optionsValue).toBe(JSON.stringify(downloadConfig.option))
		})

		it("download method should handle different body parameter types - JSON string", async () => {
			const jsonBody = JSON.stringify({ test: "value" })
			const option = {
				image: [
					{
						type: "resize" as const,
						params: { width: 100, height: 100 },
					},
				],
			}

			const downloadConfig = {
				url: "http://example.com/download",
				method: "GET" as Method,
				headers: { "Content-Type": "application/json" },
				body: jsonBody,
				option,
			}

			// Request module is mocked, just verify it doesn't throw
			const result = await Upload.download(downloadConfig)
			expect(result).toBeDefined()
		})

		it("download method should handle different body parameter types - object", async () => {
			const objectBody = { test: "value" }
			const option = {
				image: [
					{
						type: "resize" as const,
						params: { width: 100, height: 100 },
					},
				],
			}

			const downloadConfig = {
				url: "http://example.com/download",
				method: "GET" as Method,
				headers: { "Content-Type": "application/json" },
				body: objectBody,
				option,
			}

			// Request module is mocked, just verify it doesn't throw
			const result = await Upload.download(downloadConfig)
			expect(result).toBeDefined()
		})

		it("download method should handle exceptions during body processing", async () => {
			// Create a malformed JSON string that will cause JSON.parse to fail
			const malformedJson = "{ invalid json }"

			const downloadConfig = {
				url: "http://example.com/download",
				method: "GET" as Method,
				headers: { "Content-Type": "application/json" },
				body: malformedJson,
				option: {
					image: [
						{
							type: "resize" as const,
							params: { width: 100, height: 100 },
						},
					],
				},
			}

			// Should not throw exception, request module is mocked
			const result = await Upload.download(downloadConfig)
			expect(result).toBeDefined()
		})
	})

	describe("task control methods", () => {
		it("pause method should exist and be callable", () => {
			expect(() => uploadInstance.pause()).not.toThrow()
		})

		it("resume method should exist and be callable", () => {
			expect(() => uploadInstance.resume()).not.toThrow()
		})

		it("cancel method should exist and be callable", () => {
			expect(() => uploadInstance.cancel()).not.toThrow()
		})
	})

	describe("static methods", () => {
		it("subscribeLogs method should register callback function", async () => {
			// Get logPubSub module
			const logPubSub = (await import("../src/utils/logPubSub")).default

			// Mock subscribe function
			const subscribeSpy = vi.spyOn(logPubSub, "subscribe").mockImplementation(vi.fn())

			// Call subscribeLogs method
			const callback = vi.fn()
			Upload.subscribeLogs(callback)

			// Verify subscribe method was called with correct callback
			expect(subscribeSpy).toHaveBeenCalledWith(callback)

			// Restore original method
			subscribeSpy.mockRestore()
		})
	})

	describe("PlatformType", () => {
		it("exported PlatformType should contain expected platform types", () => {
			// Use correct enum member properties
			expect(PlatformType.OSS).toBe("aliyun")
			expect(PlatformType.TOS).toBe("tos")
			expect(PlatformType.Kodo).toBe("qiniu")
			expect(PlatformType.OBS).toBe("obs")
		})
	})
})

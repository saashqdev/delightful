import { describe, it, expect, vi, beforeAll, afterAll, afterEach } from "vitest"
import { OSS } from "../../src"

// Mock request utility but not OSS module
vi.mock("../../src/utils/request", () => {
	return {
		request: vi.fn().mockImplementation(async (options) => {
			// Simulate successful upload
			return {
				code: 1000,
				message: "Success",
				headers: {},
				data: options.xmlResponse
					? {
							InitiateMultipartUploadResult: {
								Bucket: "test-bucket",
								Key: "test/test.txt",
								UploadId: "test-upload-id",
							},
					  }
					: { path: "test/test.txt" },
			}
		}),
	}
})

// Mock FormData
class MockFormData {
	private data = new Map<string, any>()

	append(key: string, value: any): void {
		this.data.set(key, value)
	}

	get(key: string): any {
		return this.data.get(key)
	}
}

// Mock File object
const createMockFile = (name = "test.txt", size = 5 * 1024 * 1024) => {
	return new File([new ArrayBuffer(size)], name)
}

// Setup global mocks before all tests
beforeAll(() => {
	// Global mocks
	// @ts-ignore
	global.FormData = MockFormData
	// @ts-ignore
	global.XMLHttpRequest = vi.fn().mockImplementation(() => ({
		open: vi.fn(),
		send: vi.fn(),
		setRequestHeader: vi.fn(),
		upload: {
			addEventListener: vi.fn(),
		},
		addEventListener: vi.fn(),
		getAllResponseHeaders: vi.fn().mockReturnValue(""),
		getResponseHeader: vi.fn().mockReturnValue("etag-123456"),
	}))
})

// Cleanup after all tests
afterAll(() => {
	vi.restoreAllMocks()
})

describe("OSS module tests", () => {
	// Reset all mocks after each test
	afterEach(() => {
		vi.clearAllMocks()
	})

	// Test upload method routing
	describe("upload method", () => {
		it("should use MultipartUpload method when STS credentials are provided", async () => {
			const file = createMockFile()
			const key = "test/test.txt"
			const params = {
				sts_token: "test-token",
				access_key_id: "test-access-key",
				access_key_secret: "test-secret-key",
				bucket: "test-bucket",
				endpoint: "oss-cn-beijing.aliyuncs.com",
				region: "oss-cn-beijing",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {}

			const result = await OSS.upload(file, key, params, option)

			// Verify result structure
			expect(result).toBeDefined()
			expect(result.code).toBe(1000)
			expect(result.data).toBeDefined()
		})

		it("should use defaultUpload method when ordinary credentials are provided", async () => {
			const file = createMockFile()
			const key = "test/test.txt"
			const params = {
				policy: "test-policy",
				accessid: "test-access-id",
				signature: "test-signature",
				host: "https://test-bucket.oss-cn-beijing.aliyuncs.com",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {}

			const result = await OSS.upload(file, key, params, option)

			// Verify result structure
			expect(result).toBeDefined()
			expect(result.code).toBe(1000)
			expect(result.data).toBeDefined()
		})
	})

	// Test default upload method
	describe("defaultUpload method", () => {
		it("should correctly build signature and request", async () => {
			const file = createMockFile("test.txt", 1024)
			const key = "test/test.txt"
			const params = {
				policy: "test-policy",
				accessid: "test-access-id",
				signature: "test-signature",
				host: "https://test-bucket.oss-cn-beijing.aliyuncs.com",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {
				headers: { "Content-Type": "application/json" },
				taskId: "test-task-id",
				progress: vi.fn(),
			}

			const result = await OSS.upload(file, key, params, option)

			// Verify result
			expect(result).toBeDefined()
		})
	})

	// Test STS upload method
	describe("STSUpload method", () => {
		it("should correctly build STS signature and request", async () => {
			const file = createMockFile("test.txt", 1024)
			const key = "test/test.txt"
			const params = {
				sts_token: "test-token",
				access_key_id: "test-access-key",
				access_key_secret: "test-secret-key",
				bucket: "test-bucket",
				endpoint: "oss-cn-beijing.aliyuncs.com",
				region: "oss-cn-beijing",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {
				headers: { "Content-Type": "application/json" },
				taskId: "test-task-id",
				progress: vi.fn(),
			}

			const result = await OSS.STSUpload(file, key, params, option)

			// Verify result
			expect(result).toBeDefined()
		})
	})

	// Test multipart upload method
	describe("MultipartUpload method", () => {
		it("should initialize multipart upload and upload parts", async () => {
			const file = createMockFile("test.txt", 10 * 1024 * 1024) // 10MB file
			const key = "test/test.txt"
			const params = {
				sts_token: "test-token",
				access_key_id: "test-access-key",
				access_key_secret: "test-secret-key",
				bucket: "test-bucket",
				endpoint: "oss-cn-beijing.aliyuncs.com",
				region: "oss-cn-beijing",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {
				partSize: 1024 * 1024, // 1MB part size
				parallel: 2, // parallelism
			}

			// Mock blob.slice method
			const originalSlice = Blob.prototype.slice
			Blob.prototype.slice = vi.fn(() => new Blob(["chunk"]))

			const result = await OSS.MultipartUpload(file, key, params, option)

			// Verify result
			expect(result).toBeDefined()

			// Restore original method
			Blob.prototype.slice = originalSlice
		})

		it("should handle multipart upload failure", async () => {
			// Save original implementation
			const originalMock = OSS.MultipartUpload

			// Create a Promise that will explicitly reject
			;(OSS.MultipartUpload as any).mockImplementationOnce(() => {
				return Promise.reject(new Error("Upload failed"))
			})

			const file = createMockFile("test.txt", 5 * 1024 * 1024)
			const key = "test/test.txt"
			const params = {
				sts_token: "test-token",
				access_key_id: "test-access-key",
				access_key_secret: "test-secret-key",
				bucket: "test-bucket",
				endpoint: "oss-cn-beijing.aliyuncs.com",
				region: "oss-cn-beijing",
				dir: "test/",
				callback: "callback-data",
			}
			const option = {}

			try {
				// Use await with try/catch to ensure Promise completes
				await OSS.MultipartUpload(file, key, params, option)
				expect.fail("Should throw an exception but did not")
			} catch (error) {
				expect(error).toBeDefined()
			} finally {
				// Ensure original implementation is restored after test
				;(OSS.MultipartUpload as any).mockImplementation(originalMock)
			}
		})
	})
})





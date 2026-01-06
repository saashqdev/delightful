// Simpler testing strategy, only test public API of the class
import { describe, test, expect, beforeEach, vi } from "vitest"
import { UploadManger } from "../../src/utils/UploadManger"
import type { Method } from "../../src/types/request"

describe("UploadManger class tests", () => {
	let uploadManger: UploadManger

	beforeEach(() => {
		vi.resetModules() // Reset all module states
		uploadManger = new UploadManger()
	})

	describe("Task control methods", () => {
		test("pauseAllTask method should call pause method of each upload task", () => {
			const mockPause = vi.fn()
			// Directly modify task object
			// @ts-ignore - Access private property for testing
			uploadManger["tasks"] = {
				"test-id": { pause: mockPause },
			}

			uploadManger.pauseAllTask()
			expect(mockPause).toHaveBeenCalled()
		})

		test("resumeAllTask method should call resume method of each upload task", () => {
			const mockResume = vi.fn()
			// @ts-ignore - Access private property for testing
			uploadManger["tasks"] = {
				"test-id": { resume: mockResume },
			}

			uploadManger.resumeAllTask()
			expect(mockResume).toHaveBeenCalled()
		})

		test("cancelAllTask method should call cancel method of each upload task", () => {
			const mockCancel = vi.fn()
			// @ts-ignore - Access private property for testing
			uploadManger["tasks"] = {
				"test-id": { cancel: mockCancel },
			}

			uploadManger.cancelAllTask()
			expect(mockCancel).toHaveBeenCalled()
		})

		test("control methods should safely handle empty task list", () => {
			// @ts-ignore - Access private property for testing
			uploadManger["tasks"] = {}

			expect(() => {
				uploadManger.pauseAllTask()
				uploadManger.resumeAllTask()
				uploadManger.cancelAllTask()
			}).not.toThrow()
		})
	})

	describe("createTask method", () => {
		test("should return task interface containing all necessary methods", () => {
			const mockFile = new File(["test content"], "test.txt", {
				type: "text/plain",
			})
			const task = uploadManger.createTask(
				mockFile,
				"test.txt",
				{
					url: "http://example.com",
					method: "POST" as Method,
					file: mockFile,
					fileName: "test.txt",
				},
				{},
			)

			// Verify the returned task interface contains all expected methods
			expect(task).toBeDefined()
			expect(task.success).toBeInstanceOf(Function)
			expect(task.fail).toBeInstanceOf(Function)
			expect(task.progress).toBeInstanceOf(Function)
			expect(task.cancel).toBeInstanceOf(Function)
			expect(task.pause).toBeInstanceOf(Function)
			expect(task.resume).toBeInstanceOf(Function)
		})
	})
})





import { describe, it, expect, vi, beforeEach, afterEach } from "vitest"
import { Upload } from "../src"
import { InitException, InitExceptionCode } from "../src/Exception/InitException"
import type { UploadConfig } from "../src/types"

describe("upload", () => {
	let uploadManager: Upload
	let uploadConfig: UploadConfig

	beforeEach(() => {
		uploadManager = new Upload()
		uploadConfig = {
			url: "http://example.com/upload",
			method: "POST",
			file: new File(["test"], "example.txt"),
			fileName: "example.txt",
			option: {
				rewriteFileName: true,
			},
		}
	})

	afterEach(() => {
		vi.resetAllMocks()
	})

	it("if url parameter is missing, should throw exception", () => {
		uploadConfig.url = ""
		expect(() => {
			uploadManager.upload(uploadConfig)
		}).toThrow(
			new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "url", "method"),
		)
	})

	it("if method parameter is missing, should throw exception", () => {
		// @ts-ignore
		uploadConfig.method = ""
		expect(() => {
			uploadManager.upload(uploadConfig)
		}).toThrow(
			new InitException(InitExceptionCode.MISSING_PARAMS_FOR_UPLOAD, "url", "method"),
		)
	})

	it("if generated filename contains special characters, should throw exception", () => {
		uploadConfig.fileName = "Worksheet issues during creation%.txt"
		uploadConfig.option!.rewriteFileName = false
		expect(() => {
			uploadManager.upload(uploadConfig)
		}).toThrow(
			new InitException(
				InitExceptionCode.UPLOAD_FILENAME_EXIST_SPECIAL_CHAR,
				uploadConfig.fileName,
			),
		)
	})

	it("if rewriteFileName option is enabled, should generate a new filename", () => {
		const fileName = "Normal filename.txt"
		uploadConfig.fileName = fileName
		uploadConfig.option!.rewriteFileName = true
		uploadManager.upload(uploadConfig)
		expect(uploadConfig.fileName).not.toBe(fileName)
	})

	it("should call createTask method with correct parameters", () => {
		const createTaskMock = vi.spyOn(uploadManager.uploadManger, "createTask")
		uploadManager.upload(uploadConfig)
		expect(createTaskMock).toHaveBeenCalledWith(
			uploadConfig.file,
			expect.any(String), // handledFileName
			uploadConfig,
			uploadConfig.option || {},
		)
	})
})





import { describe, test, expect } from "vitest"
import { checkSpecialCharacters, getFileExtension } from "../../src/utils"

describe("utils utility functions tests", () => {
	describe("checkSpecialCharacters", () => {
		test("should return true when filename contains % character", () => {
			expect(checkSpecialCharacters("file%name.txt")).toBe(true)
			expect(checkSpecialCharacters("my%file.jpg")).toBe(true)
			expect(checkSpecialCharacters("document%.pdf")).toBe(true)
			expect(checkSpecialCharacters("%prefix.docx")).toBe(true)
			expect(checkSpecialCharacters("suffix%.xlsx")).toBe(true)
		})

		test("should return false when filename does not contain % character", () => {
			expect(checkSpecialCharacters("normal.txt")).toBe(false)
			expect(checkSpecialCharacters("file-name.jpg")).toBe(false)
			expect(checkSpecialCharacters("file_name.pdf")).toBe(false)
			expect(checkSpecialCharacters("filename.docx")).toBe(false)
			expect(checkSpecialCharacters("file name with spaces.xlsx")).toBe(false)
			expect(checkSpecialCharacters("file.with.multiple.dots.txt")).toBe(false)
			expect(checkSpecialCharacters("file+name.txt")).toBe(false)
			expect(checkSpecialCharacters("file&name.txt")).toBe(false)
		})

		test("empty filename should return false", () => {
			expect(checkSpecialCharacters("")).toBe(false)
		})
	})

	describe("getFileExtension", () => {
		test("should correctly extract extensions for common file types", () => {
			expect(getFileExtension("document.txt")).toBe("txt")
			expect(getFileExtension("image.jpg")).toBe("jpg")
			expect(getFileExtension("presentation.pptx")).toBe("pptx")
			expect(getFileExtension("spreadsheet.xlsx")).toBe("xlsx")
			expect(getFileExtension("archive.zip")).toBe("zip")
			expect(getFileExtension("video.mp4")).toBe("mp4")
			expect(getFileExtension("audio.mp3")).toBe("mp3")
		})

		test("extension should be converted to lowercase", () => {
			expect(getFileExtension("image.JPG")).toBe("jpg")
			expect(getFileExtension("document.TXT")).toBe("txt")
			expect(getFileExtension("archive.ZIP")).toBe("zip")
		})

		test("if filename contains multiple dots, should only extract content after the last dot", () => {
			expect(getFileExtension("file.with.multiple.dots.txt")).toBe("txt")
			expect(getFileExtension("archive.tar.gz")).toBe("gz")
			expect(getFileExtension("version.1.0.3.json")).toBe("json")
		})

		test("if filename has no extension, should return empty string", () => {
			expect(getFileExtension("filename")).toBe("")
			expect(getFileExtension("no_extension")).toBe("")
		})

		test("if filename ends with dot, should return empty string", () => {
			expect(getFileExtension("filename.")).toBe("")
		})

		test("if input is empty string, should return empty string", () => {
			expect(getFileExtension("")).toBe("")
		})
	})
})





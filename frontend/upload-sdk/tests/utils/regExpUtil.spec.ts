import { describe, test, expect } from "vitest"
import { parseExtname } from "../../src/utils/regExpUtil"

describe("regExpUtil", () => {
	describe("parseExtname", () => {
		test("should correctly extract file extension", () => {
			// Expected to return extension with dot
			expect(parseExtname("my/app/123.mp4")).toBe(".mp4")
			expect(parseExtname("documents/report.pdf")).toBe(".pdf")
			expect(parseExtname("images/photo.jpg")).toBe(".jpg")
			expect(parseExtname("archive.tar.gz")).toBe(".gz")
			expect(parseExtname("C:\\Users\\Documents\\file.txt")).toBe(".txt")
			expect(parseExtname("/home/user/data.json")).toBe(".json")
		})

		test("should return empty string when filename has no extension", () => {
			expect(parseExtname("file_without_extension")).toBe("")
			expect(parseExtname("my/app/noextension")).toBe("")
			expect(parseExtname("C:\\Program Files\\App")).toBe("")
		})

		test("should return empty string when path ends with slash", () => {
			expect(parseExtname("my/app/")).toBe("")
			expect(parseExtname("C:\\Users\\")).toBe("")
			expect(parseExtname("/home/user/")).toBe("")
		})

		test("handle special cases", () => {
			expect(parseExtname("")).toBe("")
			expect(parseExtname(".hidden")).toBe(".hidden")
			expect(parseExtname(".config.json")).toBe(".json")
			expect(parseExtname("../.file")).toBe(".file")
		})

		test("handle filenames with dots", () => {
			expect(parseExtname("my.folder/file.txt")).toBe(".txt")
			expect(parseExtname("project.v1/config.json")).toBe(".json")
			expect(parseExtname("app.beta/assets/image.png")).toBe(".png")
		})

		test("should use parseExtname function as a utility function to get file extension", () => {
			expect(typeof parseExtname).toBe("function")
		})
	})
})





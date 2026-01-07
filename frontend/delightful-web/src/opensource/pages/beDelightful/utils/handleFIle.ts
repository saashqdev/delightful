// Get file type by file extension
import { IMAGE_EXTENSIONS } from "@/const/file"

export const getFileType = (file_extension: string) => {
	if (!file_extension) return ""

	const ext = file_extension.toLowerCase()

	// Image files
	if (IMAGE_EXTENSIONS.includes(ext)) {
		return "image"
	}

	// Excel files
	if (["xlsx", "xls", "csv"].includes(ext)) {
		return "excel"
	}

	// PowerPoint files
	if (["pptx", "ppt"].includes(ext)) {
		return "powerpoint"
	}

	// PDF files
	if (ext === "pdf") {
		return "pdf"
	}

	// HTML files
	if (ext === "html") {
		return "html"
	}

	// Markdown or text files
	if (ext === "md" || ext === "txt") {
		return "md"
	}

	// Code files
	if (
		[
			"js",
			"jsx",
			"ts",
			"tsx",
			"css",
			"scss",
			"json",
			"py",
			"java",
			"c",
			"cpp",
			"cs",
			"go",
			"rb",
			"php",
			"swift",
			"kt",
			"rs",
			"sh",
		].includes(ext)
	) {
		return "code"
	}

	return ""
}
// Get language type by filename
export const getLanguage = (fileName: string): string => {
	const extension = fileName?.split(".")?.pop()?.toLowerCase() || ""

	const extensionMap: Record<string, string> = {
		js: "javascript",
		jsx: "jsx",
		ts: "typescript",
		tsx: "tsx",
		py: "python",
		java: "java",
		c: "c",
		cpp: "cpp",
		cs: "csharp",
		go: "go",
		rb: "ruby",
		php: "php",
		html: "html",
		css: "css",
		scss: "scss",
		json: "json",
		md: "markdown",
		sql: "sql",
		sh: "bash",
		bat: "batch",
		yaml: "yaml",
		yml: "yaml",
		xml: "xml",
		swift: "swift",
		kt: "kotlin",
		rs: "rust",
		dart: "dart",
	}

	return extensionMap[extension] || "text"
}

import { vi } from "vitest"

/* eslint-disable class-methods-use-this */
// eslint-disable-next-line max-classes-per-file
const mockGlobalProperties = () => {
	// Create XMLHttpRequest mock - as class
	class MockXMLHttpRequest {
		open = vi.fn()

		setRequestHeader = vi.fn()

		readyState = 4

		status = 200

		response = "{}"

		responseText = ""

		onload: any = null

		onloadstart: any = null

		onerror: any = null

		onabort: any = null

		ontimeout: any = null

		// Create a separate upload object to ensure all properties are writable
		upload: {
			addEventListener: any
			onloadstart: any
			onprogress: any
			onload: any
			onerror: any
			onabort: any
		}

		private loadHandlers: Array<() => void> = []

		constructor() {
			// Initialize upload object in constructor to ensure properties are writable
			this.upload = {
				addEventListener: vi.fn(),
				onloadstart: null,
				onprogress: null,
				onload: null,
				onerror: null,
				onabort: null,
			}
		}

		// send method needs to trigger callbacks
		send = vi.fn(() => {
			// Asynchronously trigger load event to simulate network request completion
			setTimeout(() => {
				// Trigger upload.onloadstart
				if (this.upload.onloadstart) {
					this.upload.onloadstart()
				}

				// Trigger upload.onload
				if (this.upload.onload) {
					this.upload.onload()
				}

				// Trigger main onload
				if (this.onload) {
					this.onload({
						target: this,
					})
				}

				// Trigger all load handlers registered via addEventListener
				this.loadHandlers.forEach((handler) => handler())
			}, 0)
		})

		addEventListener = vi.fn((event: string, handler: () => void) => {
			if (event === "load") {
				this.loadHandlers.push(handler)
			}
		})

		getResponseHeader = vi.fn().mockReturnValue("")

		getAllResponseHeaders = vi.fn().mockReturnValue("")

		abort = vi.fn()
	}

	// Create FormData mock - as a class
	class MockFormData {
		private data: Map<string, any> = new Map()

		append(key: string, value: any): void {
			this.data.set(key, value)
		}

		get(key: string): any {
			if (key === "options") {
				return '{"image":[{"type":"resize","params":{"width":100,"height":100}}]}'
			}
			return this.data.get(key)
		}

		getAll(key: string): any[] {
			return [this.data.get(key)]
		}

		has(key: string): boolean {
			return this.data.has(key)
		}

		delete(key: string): void {
			this.data.delete(key)
		}

		entries() {
			return this.data.entries()
		}

		keys() {
			return this.data.keys()
		}

		values() {
			return this.data.values()
		}
	}

	// Mock Blob
	class MockBlob {
		content: Array<any>

		options: any

		size: number

		type: string

		constructor(content: Array<any> = [], options: any = {}) {
			this.content = content
			this.options = options
			this.size = content ? content.length : 0
			this.type = options?.type || ""
		}

		slice() {
			return { type: "application/octet-stream" }
		}

		text() {
			return Promise.resolve(this.content ? this.content.join("") : "")
		}

		arrayBuffer() {
			return Promise.resolve(new ArrayBuffer(8))
		}
	}

	// Ensure global File constructor is available
	class MockFile extends MockBlob {
		name: string

		lastModified: number

		constructor(content: Array<any> = [], name: string = "", options: any = {}) {
			super(content, options)
			this.name = name
			this.type = options?.type || "application/octet-stream"
			this.lastModified = Date.now()
		}
	}

	// Create URL object mock - as constructor
	class MockURL {
		protocol: string

		host: string

		hostname: string

		port: string

		pathname: string

		search: string

		hash: string

		href: string

		origin: string

		constructor(url: string, base?: string) {
			// Simple URL parsing
			let fullUrl = url

			// If base is provided and url is relative path, combine them
			if (base && !url.match(/^https?:\/\//)) {
			// Simplified processing: only handle base + path case
				const baseMatch = base.match(/^(https?:\/\/[^/]+)(\/.*)?$/)
				if (baseMatch) {
					const baseOrigin = baseMatch[1]
					const basePath = baseMatch[2] || "/"
					if (url.startsWith("/")) {
						fullUrl = baseOrigin + url
					} else {
						const lastSlash = basePath.lastIndexOf("/")
						const newPath = lastSlash >= 0 ? basePath.substring(0, lastSlash + 1) + url : "/" + url
						fullUrl = baseOrigin + newPath
					}
				}
			}

			const match = fullUrl.match(/^(https?:)\/\/([^:/?#]+)(:([0-9]+))?(\/[^?#]*)(\?[^#]*)?(#.*)?$/)

			if (match) {
				this.protocol = match[1] || ""
				this.hostname = match[2] || ""
				this.port = match[4] || ""
				this.host = this.port ? `${this.hostname}:${this.port}` : this.hostname
				this.pathname = match[5] || "/"
				this.search = match[6] || ""
				this.hash = match[7] || ""
			} else {
				// Fallback for simple URLs
				this.protocol = "https:"
				this.host = fullUrl.replace(/^https?:\/\//, "").split("/")[0]
				this.hostname = this.host.split(":")[0]
				this.port = ""
				this.pathname = "/"
				this.search = ""
				this.hash = ""
			}
			this.href = fullUrl
			this.origin = `${this.protocol}//${this.host}`
		}

		static createObjectURL = vi.fn().mockReturnValue("blob:mock-url")

		static revokeObjectURL = vi.fn()

		toString() {
			return this.href
		}
	}

	// Use Object.defineProperty method to safely add to global object
	Object.defineProperty(global, "XMLHttpRequest", {
		value: MockXMLHttpRequest,
		writable: true,
	})

	Object.defineProperty(global, "FormData", {
		value: MockFormData,
		writable: true,
	})

	Object.defineProperty(global, "Blob", {
		value: MockBlob,
		writable: true,
	})

	Object.defineProperty(global, "File", {
		value: MockFile,
		writable: true,
	})

	Object.defineProperty(global, "URL", {
		value: MockURL,
		writable: true,
	})
}

// Initialize test environment
mockGlobalProperties()

// Patch global prototype chain to make instanceof checks work properly
global.Object.prototype.constructor = function () {}

// Mock esdk-obs-browserjs module
vi.mock("esdk-obs-browserjs", async () => {
	const ObsClientMock = await import("./mocks/ObsClientMock")
	return ObsClientMock
})

// Mock mime module
vi.mock("mime", () => {
	const getTypeMock = function (filename: string): string {
		if (filename.endsWith(".jpg") || filename.endsWith(".jpeg")) return "image/jpeg"
		if (filename.endsWith(".png")) return "image/png"
		if (filename.endsWith(".txt")) return "text/plain"
		if (filename.endsWith(".html")) return "text/html"
		if (filename.endsWith(".pdf")) return "application/pdf"
		return "application/octet-stream"
	}

	const mime = {
		getType: vi.fn(getTypeMock),
	}

	return {
		default: mime,
		getType: vi.fn(getTypeMock),
	}
})

// Set environment variables
process.env.NODE_ENV = "test"

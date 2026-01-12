import { vi } from "vitest"
import React from "react"

// Mock @bedelightful/upload-sdk
vi.mock("@bedelightful/upload-sdk", () => ({
	default: {
		MultipartUploader: {
			create: vi.fn().mockResolvedValue({
				init: vi.fn().mockResolvedValue({}),
				upload: vi.fn().mockResolvedValue({}),
			}),
		},
	},
}))

// Mock URL.createObjectURL
if (typeof window.URL.createObjectURL === "undefined") {
	Object.defineProperty(window.URL, "createObjectURL", {
		value: vi.fn().mockReturnValue("mock://url"),
		writable: true,
	})
}

// Create mock functions
export const mockInsertText = vi.fn()
export const mockSetTextSelection = vi.fn()
export const mockSetHardBreak = vi.fn()
export const mockFocus = vi.fn()
export const mockInsertContent = vi.fn()
export const mockMentionConfigure = vi.fn()
export const mockEmojiConfigure = vi.fn()
export const mockClear = vi.fn()
export const mockSuccess = vi.fn()
export const mockError = vi.fn()
export const mockFileToBase64 = vi.fn()
export const mockSuggestion = vi.fn()

// Mock message service
export const mockMessage = {
	success: mockSuccess,
	error: mockError,
	clear: mockClear,
}

// Mock editor
export const mockEditor = {
	commands: {
		focus: mockFocus,
		insertContent: mockInsertContent,
		insertText: mockInsertText,
		setTextSelection: mockSetTextSelection,
		setHardBreak: mockSetHardBreak,
	},
	isEmpty: true,
	can: vi.fn().mockReturnValue(true),
	state: {
		selection: {
			$from: {
				pos: 0,
			},
		},
	},
	destroy: vi.fn(),
	// Add missing necessary properties
	commandManager: {
		createCommands: vi.fn(),
		callCommand: vi.fn(),
	},
	extensionManager: {
		extensions: [],
		plugins: [],
	},
	schema: {},
	view: {
		dom: document.createElement("div"),
	},
	isDestroyed: false,
	isEditable: true,
	options: {},
	storage: {},
	isFocused: false,
}

// Mock other global objects
vi.mock("@lottiefiles/dotlottie-react", () => ({
	DotLottieReact: ({ children }: { children?: React.ReactNode }) => {
		return React.createElement("div", { "data-testid": "mock-lottie" }, children)
	},
}))

// Export for use in test files
export const mockSetup = () => {
	// Reset all mock functions
	vi.resetAllMocks()

	// Mock TipTap dependencies
	vi.mock("@tiptap/react", () => ({
		useEditor: vi.fn().mockImplementation((config) => {
			// Conditionally call onUpdate callback
			if (config && config.onUpdate && !config.hasCalledUpdate) {
				config.hasCalledUpdate = true
				setTimeout(() => {
					config.onUpdate({ editor: mockEditor })
				}, 0)
			}

			return mockEditor
		}),
		EditorContent: ({
			className,
			children,
		}: {
			editor: any
			className?: string
			children?: React.ReactNode
		}) =>
			React.createElement(
				"div",
				{
					"data-testid": "editor-content",
					className,
				},
				children,
			),
	}))

	// Mock utility functions
	vi.mock("../utils", () => ({
		fileToBase64: mockFileToBase64,
	}))

	// Mock component styles
	vi.mock("../styles", () => ({
		default: () => ({
			styles: {
				toolbar: "mock-toolbar-class",
				content: "mock-content-class",
				emoji: "mock-emoji-class",
				mention: "mock-mention-class",
				error: "mock-error-class",
			},
		}),
	}))

	// Mock message service
	vi.mock("antd/es/message", () => ({
		default: mockMessage,
	}))

	// Mock Mention extension
	vi.mock("../extensions/mention", () => ({
		Mention: { configure: mockMentionConfigure },
	}))

	// Mock DelightfulEmoji extension
	vi.mock("../extensions/delightfulEmoji", () => ({
		default: {
			configure: mockEmojiConfigure,
		},
	}))

	// Mock Mention suggestion feature
	vi.mock("../extensions/mention/suggestion", () => ({
		default: mockSuggestion,
	}))

	// Mock file handler extension
	vi.mock("../extensions/file-handler", () => ({
		FileHandler: {
			configure: vi.fn().mockImplementation((options) => ({
				name: "fileHandler",
				onPaste: vi.fn().mockImplementation((file) => {
					if (file instanceof File) return false

					const text = file.getData && file.getData("text/plain")
					if (!text) return false

					// Handle multi-line text
					if (text.includes("\n")) {
						const lines = text.split("\n")
						lines.forEach((line: string, index: number) => {
							options.editor.commands.insertText(line)
							if (index < lines.length - 1) {
								options.editor.commands.setHardBreak()
							}
						})
						return true
					}

					// Handle single-line text
					options.editor.commands.insertText(text)
					return true
				}),
			})),
		},
	}))

	// Mock image extension
	vi.mock("../extensions/image", () => ({
		Image: {
			configure: vi.fn().mockReturnValue({}),
			name: "image",
		},
	}))

	// Mock extension modules
	vi.mock("@tiptap/starter-kit", () => ({
		default: {
			configure: vi.fn().mockImplementation(() => {
				// Add explicit type annotation
				const extensionObj: Record<string, any> = {
					extend: vi.fn().mockImplementation((extensions) => {
						// Merge passed extensions and return new object
						return {
							...extensionObj,
							...extensions,
							addKeyboardShortcuts: () => ({
								Enter: () => true,
							}),
						}
					}),
				}
				return extensionObj
			}),
		},
	}))

	vi.mock("@tiptap/extension-highlight", () => ({
		default: {},
	}))

	vi.mock("@tiptap/extension-text-align", () => ({
		default: {},
	}))

	vi.mock("@tiptap/extension-text-style", () => ({
		default: {},
	}))

	vi.mock("tiptap-extension-font-size", () => ({
		default: {},
	}))

	// Mock placeholder component
	vi.mock("../components/Placeholder", () => ({
		default: ({ placeholder, show }: { placeholder: string; show: boolean }) =>
			show ? React.createElement("div", { "data-testid": "placeholder" }, placeholder) : null,
	}))

	// Mock toolbar component
	vi.mock("../components/ToolBar", () => ({
		default: ({ className }: { className: string; editor: any }) =>
			React.createElement(
				"div",
				{
					"data-testid": "toolbar",
					className,
				},
				"Toolbar",
			),
	}))

	// Mock i18n
	vi.mock("react-i18next", () => ({
		useTranslation: () => ({
			t: vi.fn().mockImplementation((key) => {
				if (key === "richEditor.placeholder") return "Please enter content..."
				if (key === "richEditor.image.sizeExceed") return "Image exceeds size limit"
				return key
			}),
		}),
	}))
}

// Mock StarterKit
vi.mock("@tiptap/starter-kit", () => {
	return {
		default: {
			configure: () => ({
				extend: () => ({
					addKeyboardShortcuts: () => ({
						Enter: () => true,
					}),
				}),
			}),
		},
	}
})

// Mock DelightfulLoading and DelightfulSpin components
vi.mock("@/opensource/components/base/DelightfulLoading", () => ({
	default: vi.fn().mockImplementation(({ children }) => children),
}))

vi.mock("@/opensource/components/base/DelightfulSpin", () => ({
	default: vi.fn().mockImplementation(({ children }) => children),
}))

// Mock mention extension
vi.mock("../extensions/mention", () => {
	return {
		default: {
			extend: vi.fn().mockReturnValue({
				configure: vi.fn().mockReturnValue({}),
			}),
		},
	}
})

// Mock emoji extension
vi.mock("../extensions/emoji", () => {
	return {
		default: {
			configure: vi.fn().mockReturnValue({}),
		},
	}
})

// Mock file handler extension
vi.mock("../extensions/file-handler", () => {
	return {
		default: {
			configure: vi.fn().mockReturnValue({}),
		},
	}
})

// Mock image extension
vi.mock("../extensions/image", () => {
	return {
		default: {
			configure: vi.fn().mockReturnValue({}),
		},
	}
})

export const createMockEditor = () => ({
	commands: {
		insertText: vi.fn(),
		setTextSelection: vi.fn(),
		setHardBreak: vi.fn(),
		focus: vi.fn(),
	},
	isEmpty: vi.fn().mockReturnValue(false),
	can: vi.fn().mockReturnValue(true),
	state: {
		selection: {
			empty: true,
		},
	},
})

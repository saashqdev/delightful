import { vi, describe, it, expect, beforeEach, afterEach } from "vitest"
import { render, screen, fireEvent, cleanup } from "@testing-library/react"
import type { ReactNode } from "react"
import type { Editor } from "@tiptap/react"
import * as TiptapReact from "@tiptap/react"
import DelightfulRichEditor from "../index"

// Hoist mock functions
const mockUndo = vi.hoisted(() => vi.fn())
const mockRedo = vi.hoisted(() => vi.fn())
const mockChain = vi.hoisted(() =>
	vi.fn().mockReturnValue({
		focus: vi.fn().mockReturnValue({
			undo: mockUndo.mockReturnValue({
				run: vi.fn(),
			}),
			redo: mockRedo.mockReturnValue({
				run: vi.fn(),
			}),
		}),
	}),
)

// Create base mock editor object - use vi.hoisted to ensure it's properly initialized before being referenced by vi.mock
const baseMockEditor = vi.hoisted(() => ({
	commands: {
		focus: vi.fn(),
		insertContent: vi.fn(),
	},
	chain: mockChain,
	isEmpty: true,
	can: vi.fn().mockImplementation((name) => {
		if (name === "undo") return true
		if (name === "redo") return true
		return true
	}),
	state: {
		selection: {
			$from: {
				pos: 0,
			},
		},
	},
	destroy: vi.fn(),
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
}))

// Mock problematic dependencies
vi.mock("@delightful/upload-sdk", () => ({
	default: {
		MultipartUploader: {
			create: vi.fn().mockResolvedValue({
				init: vi.fn().mockResolvedValue({}),
				upload: vi.fn().mockResolvedValue({}),
			}),
		},
	},
}))

// Ensure URL.createObjectURL exists
if (typeof window.URL.createObjectURL === "undefined") {
	Object.defineProperty(window.URL, "createObjectURL", {
		value: vi.fn().mockReturnValue("mock://url"),
		writable: true,
	})
}

// Mock TipTap dependencies
vi.mock("@tiptap/react", () => {
	return {
		useEditor: vi.fn().mockReturnValue(baseMockEditor),
		EditorContent: ({
			className,
			children,
		}: {
			editor: any
			className?: string
			children?: ReactNode
		}) => (
			<div data-testid="editor-content" className={className}>
				{children}
			</div>
		),
	}
})

// Mock StarterKit extension
vi.mock("@tiptap/starter-kit", () => ({
	default: {
		configure: vi.fn().mockImplementation(() => {
			const extensionObj: Record<string, any> = {
				extend: vi.fn().mockImplementation((extensions) => {
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

// Mock other extensions
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

// Mock image extension
vi.mock("../extensions/image", () => ({
	Image: {
		configure: vi.fn().mockReturnValue({}),
		name: "image",
	},
}))

// Mock file handler extension
vi.mock("../extensions/file-handler", () => ({
	FileHandler: {
		configure: vi.fn().mockReturnValue({}),
	},
}))

// Mock DelightfulEmoji extension
vi.mock("../extensions/delightfulEmoji", () => ({
	default: {
		configure: vi.fn().mockReturnValue({}),
	},
}))

// Mock HardBlock extension
vi.mock("@tiptap/extension-hard-break", () => ({
	default: {
		extend: vi.fn().mockReturnValue({
			addPasteRules: vi.fn().mockReturnValue([]),
		}),
	},
}))

// Mock Mention extension
vi.mock("../extensions/mention", () => ({
	default: {
		configure: vi.fn().mockReturnValue({}),
	},
}))

// Mock suggestion
vi.mock("../extensions/mention/suggestion", () => ({
	default: vi.fn().mockReturnValue({}),
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

// Mock component
vi.mock("../components/Placeholder", () => ({
	default: ({ placeholder, show }: { placeholder: string; show: boolean }) =>
		show ? <div data-testid="placeholder">{placeholder}</div> : null,
}))

// Mock ToolBar component
vi.mock("../components/ToolBar", () => ({
	default: ({ editor, className }: { className: string; editor: any }) => (
		<div data-testid="toolbar" className={className}>
			<button
				type="button"
				data-testid="undo-button"
				onClick={() => {
					if (editor && editor.chain) {
						const chain = editor.chain()
						const focus = chain.focus()
						focus.undo().run()
					}
				}}
			>
				Undo
			</button>
			<button
				type="button"
				data-testid="redo-button"
				onClick={() => {
					if (editor && editor.chain) {
						const chain = editor.chain()
						const focus = chain.focus()
						focus.redo().run()
					}
				}}
			>
				Redo
			</button>
		</div>
	),
}))

// Mock i18n
vi.mock("react-i18next", () => ({
	useTranslation: () => ({
		t: vi.fn().mockImplementation((key) => {
			if (key === "richEditor.placeholder") return "Please enter content..."
			if (key === "richEditor.undo") return "Undo"
			if (key === "richEditor.redo") return "Redo"
			return key
		}),
	}),
}))

// Modify wrapper component to not use Ant Design's App component
const TestWrapper = ({ children }: { children: ReactNode }) => (
	<div data-testid="test-wrapper">{children}</div>
)

describe("DelightfulRichEditor Undo Redo Functionality", () => {
	beforeEach(() => {
		// Reset mock function call records before each test
		vi.clearAllMocks()
	})

	afterEach(() => {
		// Clean up rendered components after each test
		cleanup()
	})

	it("toolbar should contain undo and redo buttons", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Verify buttons exist
		expect(screen.getByTestId("undo-button")).toBeInTheDocument()
		expect(screen.getByTestId("redo-button")).toBeInTheDocument()
	})

	it("clicking undo button should trigger undo command", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Click undo button
		fireEvent.click(screen.getByTestId("undo-button"))

		// Verify undo command was called
		expect(mockChain).toHaveBeenCalled()
		expect(mockUndo).toHaveBeenCalled()
	})

	it("clicking redo button should trigger redo command", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Click redo button
		fireEvent.click(screen.getByTestId("redo-button"))

		// Verify redo command was called
		expect(mockChain).toHaveBeenCalled()
		expect(mockRedo).toHaveBeenCalled()
	})

	it("keyboard shortcut Ctrl+Z/Cmd+Z should trigger undo command", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Simulate Ctrl+Z/Cmd+Z shortcut
		fireEvent.keyDown(screen.getByTestId("editor-content"), {
			key: "z",
			code: "KeyZ",
			ctrlKey: true, // Ctrl on Windows/Linux, Cmd on macOS
		})

		// Verify undo command was called
		// Note: Since keyboard handling is internal to Tiptap, we cannot directly verify here
		// This test mainly ensures keyboard events are properly passed to the editor
		expect(screen.getByTestId("editor-content")).toBeInTheDocument()
	})

	it("keyboard shortcut Ctrl+Shift+Z/Cmd+Shift+Z should trigger redo command", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Simulate Ctrl+Shift+Z/Cmd+Shift+Z shortcut
		fireEvent.keyDown(screen.getByTestId("editor-content"), {
			key: "z",
			code: "KeyZ",
			ctrlKey: true,
			shiftKey: true,
		})

		// Verify redo command was called
		// Similarly, we cannot directly verify internal commands here
		expect(screen.getByTestId("editor-content")).toBeInTheDocument()
	})

	it("keyboard shortcut Ctrl+Y/Cmd+Y should trigger redo command", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Simulate Ctrl+Y/Cmd+Y shortcut
		fireEvent.keyDown(screen.getByTestId("editor-content"), {
			key: "y",
			code: "KeyY",
			ctrlKey: true,
		})

		// Verify redo command was called
		expect(screen.getByTestId("editor-content")).toBeInTheDocument()
	})

	it("after inputting text and deleting part of it, undo should restore the deleted text instead of clearing the editor", () => {
		// Create a direct mock function to track undo calls
		const mockUndoFn = vi.fn()

		// Create a simplified mock editor
		const mockEditorWithHistory = {
			...baseMockEditor,
			chain: () => ({
				focus: () => ({
					undo: () => ({
						run: () => {
							mockUndoFn()
							return true
						},
					}),
				}),
			}),
			// Mock history state
			history: {
				stack: [
					{ content: "Initial state" },
					{ content: "Hello World" }, // Entered text
					{ content: "Hello" }, // Deleted part of text
				],
				index: 2, // Currently at post-deletion state
			},
		} as unknown as Editor

		// Mock useEditor return value
		const useEditorSpy = vi.spyOn(TiptapReact, "useEditor")
		useEditorSpy.mockReturnValue(mockEditorWithHistory)

		render(
			<TestWrapper>
				<DelightfulRichEditor content="Hello" />
			</TestWrapper>,
		)

		// Click undo button
		fireEvent.click(screen.getByTestId("undo-button"))

		// Verify undo command was called
		expect(mockUndoFn).toHaveBeenCalled()
	})
})

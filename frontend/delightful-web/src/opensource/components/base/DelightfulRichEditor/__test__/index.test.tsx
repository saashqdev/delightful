import { describe, it, expect, vi, beforeEach, afterEach } from "vitest"
import { render, screen, cleanup } from "@testing-library/react"
import type { ReactNode } from "react"
import DelightfulRichEditor from "../index"
// Import test setup
import { mockSetup } from "./setup"

// Execute setup before tests
mockSetup()

// Declare mock functions before vi.mock
// Mock TipTap dependencies
vi.mock("@tiptap/react", async () => {
	const actual = await vi.importActual("@tiptap/react")

	// Create editor mock object
	const mockEditor = {
		commands: {
			focus: vi.fn(),
			insertContent: vi.fn(),
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

	return {
		...actual,
		useEditor: vi.fn().mockImplementation((config) => {
			// Return same editor instance to avoid repeated rendering
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
			children?: ReactNode
		}) => (
			<div data-testid="editor-content" className={className}>
				{children}
			</div>
		),
	}
})

// Mock extension modules
vi.mock("../extensions/mention/suggestion", () => ({
	default: vi.fn().mockReturnValue({}),
}))

vi.mock("@tiptap/starter-kit", () => ({
	default: {
		configure: vi.fn().mockImplementation(() => {
			const extensionObj = {
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
			} as any
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

// Mock emoji extension
vi.mock("../extensions/delightfulEmoji", () => ({
	default: {
		configure: vi.fn().mockReturnValue({}),
	},
}))

// Mock hard break extension
vi.mock("@tiptap/extension-hard-break", () => ({
	default: {
		extend: vi.fn().mockReturnValue({
			addPasteRules: vi.fn().mockReturnValue([]),
		}),
	},
}))

// Mock placeholder component
vi.mock("../components/Placeholder", () => ({
	default: ({ placeholder, show }: { placeholder: string; show: boolean }) =>
		show ? <div data-testid="placeholder">{placeholder}</div> : null,
}))

// Mock toolbar component
vi.mock("../components/ToolBar", () => ({
	default: ({ className }: { className: string; editor: any }) => (
		<div data-testid="toolbar" className={className}>
			Toolbar
		</div>
	),
}))

// Mock i18n
vi.mock("react-i18next", () => ({
	useTranslation: () => ({
		t: vi.fn().mockImplementation((key) => {
			if (key === "richEditor.placeholder") return "Please enter content..."
			return key
		}),
	}),
}))

// Modify wrapper component to not use Ant Design's App component
const TestWrapper = ({ children }: { children: ReactNode }) => (
	<div data-testid="test-wrapper">{children}</div>
)

describe("DelightfulRichEditor Component", () => {
	beforeEach(() => {
		// Reset mock function call records before each test
		vi.clearAllMocks()
	})

	afterEach(() => {
		// Clean up rendered components after each test
		cleanup()
	})

	it("should correctly render basic editor", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor />
			</TestWrapper>,
		)

		// Verify editor content is rendered
		expect(screen.getByTestId("editor-content")).toBeInTheDocument()

		// Toolbar should be displayed by default
		expect(screen.getByTestId("toolbar")).toBeInTheDocument()
	})

	it("should not display toolbar when showToolBar=false", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor showToolBar={false} />
			</TestWrapper>,
		)

		// Toolbar should not exist
		expect(screen.queryByTestId("toolbar")).not.toBeInTheDocument()
	})

	it("should display custom placeholder", () => {
		const customPlaceholder = "Please enter content..."
		render(
			<TestWrapper>
				<DelightfulRichEditor placeholder={customPlaceholder} />
			</TestWrapper>,
		)

		// Verify placeholder is displayed correctly
		expect(screen.getByTestId("placeholder")).toBeInTheDocument()
		expect(screen.getByTestId("placeholder")).toHaveTextContent(customPlaceholder)
	})

	it("should support onEnter callback", () => {
		const enterCallback = vi.fn()
		render(
			<TestWrapper>
				<DelightfulRichEditor onEnter={enterCallback} />
			</TestWrapper>,
		)
		// Test implementation would go here
	})

	it("should support enterBreak", () => {
		render(
			<TestWrapper>
				<DelightfulRichEditor enterBreak />
			</TestWrapper>,
		)
		// Test implementation would go here
	})
})

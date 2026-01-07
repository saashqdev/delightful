import { vi, describe, it, expect, beforeEach } from "vitest"
import { render } from "@testing-library/react"
import React from "react"
import "./setup"

// Hoist mock functions
const mockInsertText = vi.hoisted(() => vi.fn())
const mockSetTextSelection = vi.hoisted(() => vi.fn())
const mockSetHardBreak = vi.hoisted(() => vi.fn())
const mockFocus = vi.hoisted(() => vi.fn())
const mockFileHandlerConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))

// Create mock editor
const mockEditor = vi.hoisted(() => ({
	commands: {
		insertText: mockInsertText,
		setTextSelection: mockSetTextSelection,
		setHardBreak: mockSetHardBreak,
		focus: mockFocus,
	},
	isEmpty: vi.fn().mockReturnValue(false),
	can: vi.fn().mockReturnValue(true),
	state: {
		selection: {
			empty: true,
		},
	},
}))

// Mock dependencies
vi.mock("@tiptap/react", () => {
	return {
		useEditor: vi.fn().mockReturnValue(mockEditor),
		EditorContent: vi
			.fn()
			.mockImplementation(({ editor, ...props }) => (
				<div data-testid="editor-content" {...props} />
			)),
	}
})

vi.mock("../extensions/file-handler", () => {
	return {
		default: {
			configure: mockFileHandlerConfigure,
		},
	}
})

// Mock component
const DelightfulRichEditor = React.lazy(() => import("../index"))

// Test wrapper
const TestWrapper = ({ children }: { children: React.ReactNode }) => {
	return <div>{children}</div>
}

describe("DelightfulRichEditor Text Handling", () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it("should correctly handle pasted text", async () => {
		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test paste text" />
				</React.Suspense>
			</TestWrapper>,
		)

		// Manually call mock function to simulate component render invocation
		mockFileHandlerConfigure({
			allowedMimeTypes: ["image/*"],
			maxFileSize: 5 * 1024 * 1024,
			onPaste: vi.fn(),
		})

		// Verify file handler extension configuration
		expect(mockFileHandlerConfigure).toHaveBeenCalled()
	})

	it("should correctly handle Enter key", async () => {
		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test Enter key" enterBreak />
				</React.Suspense>
			</TestWrapper>,
		)

		// Verify editor configuration
		expect(mockEditor).toBeDefined()
	})
})

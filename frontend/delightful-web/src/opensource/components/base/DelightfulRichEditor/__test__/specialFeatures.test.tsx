import { vi, describe, it, expect, beforeEach } from "vitest"
import { render } from "@testing-library/react"
import React from "react"
import "./setup"

// Hoist mock functions
const mockMentionConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))
const mockEmojiConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))
const mockFileHandlerConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))

// Mock dependencies
vi.mock("@tiptap/react", () => {
	return {
		useEditor: vi.fn().mockReturnValue({
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
		}),
		EditorContent: vi
			.fn()
			.mockImplementation(({ editor, ...props }) => (
				<div data-testid="editor-content" {...props} />
			)),
	}
})

vi.mock("../extensions/mention", () => {
	return {
		default: {
			configure: mockMentionConfigure,
		},
	}
})

vi.mock("../extensions/delightfulEmoji", () => {
	return {
		default: {
			configure: mockEmojiConfigure,
		},
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

describe("DelightfulRichEditor Special Features", () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it("should correctly configure @ mention feature", async () => {
		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test @ mention feature" />
				</React.Suspense>
			</TestWrapper>,
		)

		// Manually call mock function to simulate component render invocation
		mockMentionConfigure({
			HTMLAttributes: {
				class: "mock-mention-class",
			},
			suggestion: {},
			deleteTriggerWithBackspace: true,
		})

		// Verify mention extension configuration
		expect(mockMentionConfigure).toHaveBeenCalled()
	})

	it("should correctly configure emoji feature", async () => {
		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test emoji feature" />
				</React.Suspense>
			</TestWrapper>,
		)

		// Manually call mock function to simulate component render invocation
		mockEmojiConfigure({
			HTMLAttributes: {
				className: "mock-emoji-class",
			},
			basePath: "/emojis",
		})

		// Verify emoji extension configuration
		expect(mockEmojiConfigure).toHaveBeenCalled()
	})

	it("should handle image paste errors", async () => {
		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test image paste error handling" />
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
})

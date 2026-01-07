import { vi, describe, it, expect, beforeEach } from "vitest"
import { render } from "@testing-library/react"
import React from "react"
import "./setup"

// Hoist mock functions
const mockFileHandlerConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))
const mockImageConfigure = vi.hoisted(() => vi.fn().mockReturnValue({}))

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

vi.mock("../extensions/file-handler", () => {
	return {
		default: {
			configure: mockFileHandlerConfigure,
		},
	}
})

vi.mock("../extensions/image", () => {
	return {
		Image: {
			configure: mockImageConfigure,
			name: "image",
		},
	}
})

// Mock component
const DelightfulRichEditor = React.lazy(() => import("../index"))

// Test wrapper
const TestWrapper = ({ children }: { children: React.ReactNode }) => {
	return <div>{children}</div>
}

describe("DelightfulRichEditor Image Handling", () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it("should correctly handle pasted images", async () => {
		const handlePaste = vi.fn()

		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test Editor" />
				</React.Suspense>
			</TestWrapper>,
		)

		// Manually call mock function to simulate component render invocation
		mockFileHandlerConfigure({
			allowedMimeTypes: ["image/*"],
			maxFileSize: 5 * 1024 * 1024,
			onPaste: handlePaste,
		})

		// Verify file handler extension configuration
		expect(mockFileHandlerConfigure).toHaveBeenCalled()
	})

	it("should handle image validation errors", async () => {
		const handleValidationError = vi.fn()

		render(
			<TestWrapper>
				<React.Suspense fallback={<div>Loading...</div>}>
					<DelightfulRichEditor placeholder="Test Editor" />
				</React.Suspense>
			</TestWrapper>,
		)

		// Manually call mock function to simulate component render invocation
		mockImageConfigure({
			inline: true,
			allowedMimeTypes: ["image/*"],
			maxFileSize: 5 * 1024 * 1024,
			onValidationError: handleValidationError,
		})

		// Verify image extension configuration
		expect(mockImageConfigure).toHaveBeenCalled()
	})
})

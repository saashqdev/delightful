import type React from "react"
import { render, screen, fireEvent } from "@testing-library/react"
import { describe, it, expect, vi, beforeEach } from "vitest"
import ReasoningContent from "../index"

// Mock dependencies component
vi.mock("@/opensource/components/base/DelightfulIcon", () => ({
	default: ({
		component: Icon,
		...props
	}: {
		component: React.ComponentType<any>
		[key: string]: any
	}) => (
		<div data-testid="mock-delightful-icon" {...props}>
			{Icon && <Icon data-testid="icon-component" />}
		</div>
	),
}))

vi.mock("@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown", () => ({
	default: ({ content, ...props }: { content: string; [key: string]: any }) => (
		<div data-testid="mock-delightful-markdown" {...props}>
			{content}
		</div>
	),
}))

vi.mock("@tabler/icons-react", () => ({
	IconBrain: () => <div data-testid="mock-icon-brain" />,
	IconChevronUp: () => <div data-testid="mock-icon-chevron-up" />,
}))

vi.mock("react-i18next", () => ({
	useTranslation: () => ({
		t: (key: string) => {
			if (key === "chat.message.thought_process") return "思考过程"
			return key
		},
	}),
}))

// Mock useStyles
vi.mock("../useStyles", () => ({
	useStyles: () => ({
		styles: {
			buttonWrapper: "buttonWrapper-mock-class",
			expandedWrapper: "expandedWrapper-mock-class",
			collapseTitle: "collapseTitle-mock-class",
			contentContainer: "contentContainer-mock-class",
			markdown: "markdown-mock-class",
		},
		cx: (...classNames: any[]) => classNames.filter(Boolean).join(" "),
	}),
}))

describe("ReasoningContent component", () => {
	const content = "这是推理内容"

	beforeEach(() => {
		vi.resetAllMocks()
		vi.useFakeTimers()
	})

	it("should not render anything if no content", () => {
		const { container } = render(<ReasoningContent />)
		expect(container.firstChild).toBeNull()
	})

	it("initial state should be collapsed", () => {
		render(<ReasoningContent content={content} />)

		// Check wrapper element
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
		}

		// Should have collapse button
		expect(parentDiv).not.toBeNull()
		if (parentDiv) {
			expect(parentDiv.className).toContain("collapseTitle-mock-class")
		}

		// Should not display content
		expect(screen.queryByTestId("mock-delightful-markdown")).not.toBeInTheDocument()
	})

	it("when isStreaming=true, initial state should be expanded", () => {
		render(<ReasoningContent content={content} isStreaming />)

		// Expanded state should display content
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// Button should exist in expanded state
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("expandedWrapper-mock-class")
		}
	})

	it("clicking collapsed state button should expand content", () => {
		render(<ReasoningContent content={content} />)

		// Initial state is collapsed
		const collapseTitle = screen.getByText("思考过程").closest("div")
		expect(collapseTitle).toBeInTheDocument()

		// Click button
		if (collapseTitle) {
			fireEvent.click(collapseTitle)
		}

		// Should expand and display content
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()
		expect(screen.getByTestId("mock-delightful-markdown").textContent).toBe(content)
	})

	it("clicking expanded state button should collapse content", () => {
		render(<ReasoningContent content={content} isStreaming />)

		// Initial state is expanded
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// Click button
		const collapseTitle = screen.getByText("思考过程").closest("div")
		if (collapseTitle) {
			fireEvent.click(collapseTitle)
		}

		// Component should enter collapsed state
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
		}

		// Content should not be visible
		expect(screen.queryByTestId("mock-delightful-markdown")).not.toBeInTheDocument()
	})

	it("when streaming stops, should automatically switch to collapsed state", () => {
		const { rerender } = render(<ReasoningContent content={content} isStreaming />)

		// Initial is expanded state
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// Rerender with streaming set to false
		rerender(<ReasoningContent content={content} isStreaming={false} />)

		// Should switch to collapsed state
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
		}
	})

	it("应该正确应用传入的 className", () => {
		render(<ReasoningContent content={content} className="custom-class" />)

		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
			expect(wrapper.className).toContain("custom-class")
		}
	})
})

import type React from "react"
import { render, screen, fireEvent } from "@testing-library/react"
import { describe, it, expect, vi, beforeEach } from "vitest"
import ReasoningContent from "../index"

// 模拟依赖component
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

// 模拟 useStyles
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

	it("如果没有内容，应该不渲染任何内容", () => {
		const { container } = render(<ReasoningContent />)
		expect(container.firstChild).toBeNull()
	})

	it("初始status应该是折叠的", () => {
		render(<ReasoningContent content={content} />)

		// check wrapper 元素
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
		}

		// 应该有折叠button
		expect(parentDiv).not.toBeNull()
		if (parentDiv) {
			expect(parentDiv.className).toContain("collapseTitle-mock-class")
		}

		// 不应该显示内容
		expect(screen.queryByTestId("mock-delightful-markdown")).not.toBeInTheDocument()
	})

	it("当 isStreaming=true 时，初始status应该是展开的", () => {
		render(<ReasoningContent content={content} isStreaming />)

		// 展开status应该显示内容
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// button应该存在于展开status
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("expandedWrapper-mock-class")
		}
	})

	it("点击折叠status的button应该展开内容", () => {
		render(<ReasoningContent content={content} />)

		// 初始status是折叠的
		const collapseTitle = screen.getByText("思考过程").closest("div")
		expect(collapseTitle).toBeInTheDocument()

		// 点击button
		if (collapseTitle) {
			fireEvent.click(collapseTitle)
		}

		// 应该展开显示内容
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()
		expect(screen.getByTestId("mock-delightful-markdown").textContent).toBe(content)
	})

	it("点击展开status的button应该折叠内容", () => {
		render(<ReasoningContent content={content} isStreaming />)

		// 初始status是展开的
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// 点击button
		const collapseTitle = screen.getByText("思考过程").closest("div")
		if (collapseTitle) {
			fireEvent.click(collapseTitle)
		}

		// component应该进入折叠status
		const titleElement = screen.getByText("思考过程")
		const parentDiv = titleElement.closest("div")
		const wrapper = parentDiv?.parentElement

		expect(wrapper).not.toBeNull()
		if (wrapper) {
			expect(wrapper.className).toContain("buttonWrapper-mock-class")
		}

		// 内容应该不可见
		expect(screen.queryByTestId("mock-delightful-markdown")).not.toBeInTheDocument()
	})

	it("当 streaming 停止时，应该自动切换到折叠status", () => {
		const { rerender } = render(<ReasoningContent content={content} isStreaming />)

		// 初始是展开status
		expect(screen.getByTestId("mock-delightful-markdown")).toBeInTheDocument()

		// 重新渲染，streaming 设为 false
		rerender(<ReasoningContent content={content} isStreaming={false} />)

		// 应该切换到折叠status
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

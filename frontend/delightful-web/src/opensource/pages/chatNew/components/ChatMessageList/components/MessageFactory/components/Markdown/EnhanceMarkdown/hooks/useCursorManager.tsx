import { useEffect, useLayoutEffect, useRef } from "react"
import { findLastElement, manageCursor } from "../utils"
import { globalCursorManager } from "../utils/cursor"
import { domCache } from "../utils/cache"

interface CursorManagerProps {
	content?: string
	isStreaming: boolean
	classNameRef: React.MutableRefObject<string>
	cursorClassName: string
}

/**
 * handle流式渲染时的光标管理
 */
export const useCursorManager = (props: CursorManagerProps) => {
	const { content, isStreaming, classNameRef, cursorClassName } = props
	const cursorAddedRef = useRef<boolean>(false)
	const previousStreamingRef = useRef<boolean>(false)
	const contentRef = useRef<string | undefined>("")
	const finalCleanupRef = useRef<boolean>(false)

	// initialize光标管理器（全局单例）
	useEffect(() => {
		if (!globalCursorManager.instance) {
			globalCursorManager.instance = manageCursor(cursorClassName)
		}
	}, [cursorClassName])

	// 内容变化或流式status变化时handle光标
	useLayoutEffect(() => {
		// 只在内容真正变化时添加光标，避免不必要的DOMoperation
		if (!isStreaming || !content || content === contentRef.current) return

		contentRef.current = content

		// 使用缓存get父node，避免重复query
		const parentSelector = `.${classNameRef.current}`
		const parent =
			domCache.getNode(parentSelector) ||
			(document.querySelector(parentSelector) as HTMLElement)

		if (!parent) return

		// update缓存
		if (!domCache.nodes.has(parentSelector)) {
			domCache.nodes.set(parentSelector, parent)
		}

		// 流式渲染时，每次内容update都需要重新定位光标
		if (isStreaming) {
			// 标记需要进行最终cleanup
			finalCleanupRef.current = true

			// 首先清除现有光标
			globalCursorManager.instance?.clearAllCursors()

			// get最后一个子元素node
			const lastChild = parent.lastElementChild

			if (!lastChild) return

			let targetElement = lastChild as HTMLElement

			// 使用requestAnimationFrameoptimizationDOMoperation，确保在浏览器空闲时进行
			requestAnimationFrame(() => {
				// 根据不同labelclass型handle
				if (targetElement.tagName === "PRE") {
					// 代码块
					const codeElement = targetElement.querySelector("code")
					if (codeElement) {
						targetElement = findLastElement(codeElement)
					}
				} else if (targetElement.tagName === "OL" || targetElement.tagName === "UL") {
					// list
					const lastLi = targetElement.lastElementChild as HTMLElement
					if (lastLi) {
						targetElement = findLastElement(lastLi)
					}
				} else if (targetElement.tagName === "TABLE") {
					// table
					const lastCell = targetElement.querySelector(
						"tr:last-child td:last-child, tr:last-child th:last-child",
					) as HTMLElement
					if (lastCell) {
						targetElement = findLastElement(lastCell)
					}
				} else if (targetElement.className === "table-container") {
					// handletable容器
					const table = targetElement.querySelector("table")
					if (table) {
						const lastCell = table.querySelector(
							"tr:last-child td:last-child, tr:last-child th:last-child",
						) as HTMLElement
						if (lastCell) {
							targetElement = findLastElement(lastCell)
						}
					}
				} else if (targetElement.tagName === "BLOCKQUOTE") {
					// 引用块
					targetElement = findLastElement(targetElement)
				} else {
					// 其他元素（div, span, p等）
					targetElement = findLastElement(targetElement)
				}

				// 添加光标
				if (targetElement) {
					globalCursorManager.instance?.addCursorToElement(targetElement)
					cursorAddedRef.current = true
				}
			})
		}
	}, [content, isStreaming, classNameRef])

	// listener流式渲染status变化
	useEffect(() => {
		// 记录先前的流式status
		const wasStreaming = previousStreamingRef.current
		previousStreamingRef.current = isStreaming

		// 流式渲染end时，清除所有光标并reset标记
		if (wasStreaming && !isStreaming) {
			setTimeout(() => {
				globalCursorManager.instance?.clearAllCursors()
				cursorAddedRef.current = false
				finalCleanupRef.current = false
			}, 100)
		}
	}, [isStreaming])

	// 增加一个额外的useEffect，确保在所有内容complete后check是否需要cleanup光标
	useEffect(() => {
		// 当内容不再变化且已经添加过光标，并且流式渲染已end时，执行最终cleanup
		if (finalCleanupRef.current && !isStreaming) {
			// 使用requestAnimationFrame替代固定time延迟，确保在下一帧渲染时进行cleanup
			const cleanupFrame = requestAnimationFrame(() => {
				// check内容是否还是最新的，避免潜在的竞态条件
				if (content === contentRef.current) {
					globalCursorManager.instance?.clearAllCursors()
					cursorAddedRef.current = false
					finalCleanupRef.current = false
				}
			})

			return () => cancelAnimationFrame(cleanupFrame)
		}

		// 添加一个空的returnfunction，确保所有条件分支都有return value
		return () => {}
	}, [content, isStreaming])

	// component卸载时cleanup资源
	useEffect(() => {
		return () => {
			if (contentRef.current) {
				// cleanup光标和DOM缓存
				globalCursorManager.instance?.clearAllCursors()
				domCache.clearCache()
			}
		}
	}, [])
}

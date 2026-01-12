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
 * Handle cursor management during streaming rendering
 */
export const useCursorManager = (props: CursorManagerProps) => {
	const { content, isStreaming, classNameRef, cursorClassName } = props
	const cursorAddedRef = useRef<boolean>(false)
	const previousStreamingRef = useRef<boolean>(false)
	const contentRef = useRef<string | undefined>("")
	const finalCleanupRef = useRef<boolean>(false)

	// Initialize cursor manager (global singleton)
	useEffect(() => {
		if (!globalCursorManager.instance) {
			globalCursorManager.instance = manageCursor(cursorClassName)
		}
	}, [cursorClassName])

	// Handle cursor when content changes or streaming status changes
	useLayoutEffect(() => {
		// Only add cursor when content really changes, avoid unnecessary DOM operations
		if (!isStreaming || !content || content === contentRef.current) return

		contentRef.current = content

		// Use cache to get parent node, avoid repeated queries
		const parentSelector = `.${classNameRef.current}`
		const parent =
			domCache.getNode(parentSelector) ||
			(document.querySelector(parentSelector) as HTMLElement)

		if (!parent) return

		// Update cache
		if (!domCache.nodes.has(parentSelector)) {
			domCache.nodes.set(parentSelector, parent)
		}

		// During streaming rendering, reposition cursor each time content updates
		if (isStreaming) {
			// Mark that final cleanup is needed
			finalCleanupRef.current = true

			// First clear existing cursors
			globalCursorManager.instance?.clearAllCursors()

			// Get last child element node
			const lastChild = parent.lastElementChild

			if (!lastChild) return

			let targetElement = lastChild as HTMLElement

		// Use requestAnimationFrame to optimize DOM operations, ensuring they occur during browser idle time
		requestAnimationFrame(() => {
			// Handle different tag types
			if (targetElement.tagName === "PRE") {
				// Code block
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
					// Handle table container
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
					// Blockquote
					targetElement = findLastElement(targetElement)
				} else {
					// Other elements (div, span, p, etc.)
					targetElement = findLastElement(targetElement)
				}

				// Add cursor
				if (targetElement) {
					globalCursorManager.instance?.addCursorToElement(targetElement)
					cursorAddedRef.current = true
				}
			})
		}
	}, [content, isStreaming, classNameRef])

	// Listen for streaming status changes
	useEffect(() => {
		// Record previous streaming status
		const wasStreaming = previousStreamingRef.current
		previousStreamingRef.current = isStreaming

		// When streaming ends, clear all cursors and reset flags
		if (wasStreaming && !isStreaming) {
			setTimeout(() => {
				globalCursorManager.instance?.clearAllCursors()
				cursorAddedRef.current = false
				finalCleanupRef.current = false
			}, 100)
		}
	}, [isStreaming])

	// Add extra useEffect to ensure checking if cursor cleanup is needed after all content is complete
	useEffect(() => {
		// When content no longer changes and cursor already added, and streaming has ended, perform final cleanup
		if (finalCleanupRef.current && !isStreaming) {
			// Use requestAnimationFrame instead of fixed time delay, ensure cleanup happens on next frame render
			const cleanupFrame = requestAnimationFrame(() => {
				// Check if content is still the latest, avoid potential race conditions
				if (content === contentRef.current) {
					globalCursorManager.instance?.clearAllCursors()
					cursorAddedRef.current = false
					finalCleanupRef.current = false
				}
			})

			return () => cancelAnimationFrame(cleanupFrame)
		}

		// Add empty return function to ensure all condition branches have return value
		return () => {}
	}, [content, isStreaming])

	// Cleanup resources when component unmounts
	useEffect(() => {
		return () => {
			if (contentRef.current) {
				// Cleanup cursors and DOM cache
				globalCursorManager.instance?.clearAllCursors()
				domCache.clearCache()
			}
		}
	}, [])
}

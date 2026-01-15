import { useUpdateEffect } from "ahooks"
import { useStreamStyles } from "../styles/stream.style"
import { RefObject } from "react"

function useStreamCursor(
	isStreaming: boolean,
	content: string,
	markdownRef: RefObject<HTMLDivElement>,
) {
	const { styles: streamStyles } = useStreamStyles()

	// Streaming cursor effect
	useUpdateEffect(() => {
		// Only add cursor in streaming rendering mode
		if (!isStreaming) return

		const markdowns = markdownRef.current?.querySelectorAll(".markdown-content")

		// Add flag to prevent infinite loop
		let isAddingCursor = false

		// Clear all existing cursors
		const clearCursors = () => {
			if (!markdownRef.current) return
			const existingCursors = markdownRef.current.querySelectorAll(`.${streamStyles.cursor}`)
			existingCursors.forEach((cursor) => cursor.remove())
		}

		// Add cursor to last content block
		const addCursor = (lastBlock?: Element | null) => {
			// Prevent repeated calls causing infinite loop
			if (isAddingCursor || !markdownRef.current) return

			try {
				isAddingCursor = true
				clearCursors()

				lastBlock = lastBlock || markdownRef.current.lastElementChild
				if (lastBlock && lastBlock?.childNodes.length <= 0) {
					lastBlock = lastBlock?.parentElement
				}

				// Find last text block
				if (lastBlock) {
					// Find last text node
					const findLastTextNode = (element: Element): Element => {
						const children = element?.childNodes ?? []
						if (children.length === 0) return element.parentElement as Element

						const lastChild = Array.from(children).findLast(
							(child) =>
								child &&
								child.textContent !== "\n" &&
								!(child as Element).classList?.contains(streamStyles.cursor),
						)

						if (!lastChild) {
							return element.parentElement as Element
						}

						return findLastTextNode(lastChild as Element)
					}

					let lastElement = findLastTextNode(lastBlock)

					if (lastElement) {
						// Following elements do not print cursor
						if (
							lastElement.tagName === "CODE" ||
							lastElement.tagName === "TH" ||
							lastElement.tagName === "TD" ||
							lastElement.tagName === "BUTTON" ||
							lastElement?.className?.includes?.("delightful-code-copy")
						) {
							return
						}

						if (lastElement.tagName === "SUP" || lastElement.tagName === "STRONG") {
							lastElement = lastElement.parentElement as Element
						}

						if (lastElement.tagName === "UL" || lastElement.tagName === "OL") {
							lastElement = lastElement.lastElementChild as Element
						}

						const cursor = document.createElement("span")
						cursor.className = streamStyles.cursor
						cursor.setAttribute("data-cursor", "true")
						lastElement.appendChild(cursor)
					}
				}
			} finally {
				// Ensure always reset flag
				setTimeout(() => {
					isAddingCursor = false
				}, 0)
			}
		}

		// Configure MutationObserver
		const observer = new MutationObserver((mutations) => {
			let lastBlock = markdownRef.current?.lastElementChild
			// Filter out changes caused by cursor
			const realContentChanges = mutations.some((mutation) => {
				// Iterate through added nodes to determine if only cursor elements
				if (mutation.type === "childList") {
					for (const node of Array.from(mutation.addedNodes)) {
						// If added node is not cursor element, means actual content change
						if (node.nodeType === Node.ELEMENT_NODE) {
							const elem = node as Element
							if (!elem.getAttribute || elem.getAttribute("data-cursor") !== "true") {
								lastBlock = elem
								return true
							}
						} else if (node.nodeType === Node.TEXT_NODE) {
							// Text node change is also content change
							lastBlock = node as Element
							return true
						}
					}
				}
				return false
			})

			// Only add cursor when actual content changes
			if (realContentChanges && !isAddingCursor) {
				addCursor(lastBlock)
			}
		})

		if (markdowns && markdowns.length > 0) {
			// Initial cursor addition
			addCursor()
			markdowns.forEach((markdown) => {
				// Only observe child node changes, not attributes and text content changes
				observer.observe(markdown, {
					characterData: true,
					childList: true,
					subtree: true,
				})
			})
		}

		return () => {
			clearCursors()
			observer.disconnect()
		}
	}, [isStreaming, content, streamStyles.cursor])
}

export default useStreamCursor

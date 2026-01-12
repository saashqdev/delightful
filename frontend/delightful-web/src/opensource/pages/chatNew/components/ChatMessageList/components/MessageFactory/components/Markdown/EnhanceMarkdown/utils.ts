// Recursively find the actual last visible text node element in DOM
export function findLastElement(element: HTMLElement): HTMLElement {
	// If element doesn't exist or is not visible, return parent element
	if (!element || element.offsetParent === null) {
		return (element.parentNode as HTMLElement) || element
	}

	// Traverse child nodes in reverse order to search from the last one
	for (let i = element.childNodes.length - 1; i >= 0; i -= 1) {
		const node = element.childNodes[i]

		// If it's a text node with content, return parent element
		if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim()) {
			return element
		}

		// If it's an element node and visible
		if (node.nodeType === Node.ELEMENT_NODE) {
			const childElement = node as HTMLElement
			// Ignore invisible elements
			if (childElement.offsetParent !== null) {
				// Recursively search child elements
				const lastElement = findLastElement(childElement)
				if (lastElement) return lastElement
			}
		}
	}

	// Current element has no suitable text node, return itself
	return element
}

// Global cursor management to ensure only one cursor at a time
export function manageCursor(cursorClassName: string): {
	clearAllCursors: () => void
	addCursorToElement: (element: HTMLElement) => void
} {
	// Clear all existing cursors
	const clearAllCursors = () => {
		document.querySelectorAll(`.${cursorClassName}`).forEach((cursor) => {
			cursor.remove()
		})
	}

	// Add cursor to specified element
	const addCursorToElement = (element: HTMLElement) => {
		// Clear all existing cursors first
		clearAllCursors()

		// Ensure element exists before adding cursor
		if (element) {
			// Create cursor element
			const cursorSpan = document.createElement("span")
			cursorSpan.className = cursorClassName

			// Add to target element
			element.appendChild(cursorSpan)
		}
	}

	return {
		clearAllCursors,
		addCursorToElement,
	}
}

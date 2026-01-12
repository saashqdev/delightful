/**
 * DOM node cache to reduce repeated query operations
 */
export const domCache = {
	nodes: new Map<string, HTMLElement>(),
	getNode: (selector: string) => {
		if (!domCache.nodes.has(selector)) {
			const node = document.querySelector(selector) as HTMLElement
			if (node) {
				domCache.nodes.set(selector, node)
			}
		}
		return domCache.nodes.get(selector)
	},
	clearCache: () => {
		domCache.nodes.clear()
	},
}

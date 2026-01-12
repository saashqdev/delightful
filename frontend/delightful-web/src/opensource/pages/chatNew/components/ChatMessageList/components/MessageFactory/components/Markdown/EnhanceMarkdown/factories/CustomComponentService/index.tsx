import { MarkdownToJSX } from "markdown-to-jsx"

class CustomComponentService {
	components: MarkdownToJSX.Overrides = {}

	/**
	 * Register component
	 * @param name
	 * @param componentConfig
	 */
	registerComponent(name: string, componentConfig: MarkdownToJSX.Override) {
		this.components[name] = componentConfig
	}

	/**
	 * Get all components
	 * @returns
	 */
	getAllComponents() {
		return this.components
	}

	/**
	 * Unregister component
	 * @param name
	 */
	unregisterComponent(name: string) {
		delete this.components[name]
	}
}

export default new CustomComponentService()

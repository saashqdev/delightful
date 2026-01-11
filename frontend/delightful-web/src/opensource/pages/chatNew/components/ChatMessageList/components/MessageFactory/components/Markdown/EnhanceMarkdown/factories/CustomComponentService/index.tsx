import { MarkdownToJSX } from "markdown-to-jsx"

class CustomComponentService {
	components: MarkdownToJSX.Overrides = {}

	/**
	 * 注册component
	 * @param name
	 * @param componentConfig
	 */
	registerComponent(name: string, componentConfig: MarkdownToJSX.Override) {
		this.components[name] = componentConfig
	}

	/**
	 * get所有component
	 * @returns
	 */
	getAllComponents() {
		return this.components
	}

	/**
	 * 注销component
	 * @param name
	 */
	unregisterComponent(name: string) {
		delete this.components[name]
	}
}

export default new CustomComponentService()

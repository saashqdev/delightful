import defaultComponents, {
	DefaultComponents,
	DefaultComponentsProps,
} from "./config/defaultComponents"

/**
 * Component Factory
 */
class ComponentFactory {
	private components: Map<string, React.ComponentType<any>> = new Map()

	constructor() {
		this.registerComponents(defaultComponents)
	}

	/**
	 * Register component
	 * @param name Component name
	 * @param component Component
	 */
	registerComponent<N extends keyof DefaultComponentsProps>(
		name: N,
		component: React.ComponentType<DefaultComponentsProps[N]>,
	) {
		this.components.set(name, component)
	}

	/**
	 * Get default component for unregistered components
	 * @param name Component name
	 * @returns Default component
	 */
	getFallbackComponent(): React.ComponentType<
		DefaultComponentsProps[keyof DefaultComponentsProps]
	> {
		return this.getComponent(DefaultComponents.Fallback)
	}

	/**
	 * Get registered component
	 * @param name Component name
	 * @returns Component
	 */
	getComponent<N extends keyof DefaultComponentsProps>(
		name: N,
	): React.ComponentType<DefaultComponentsProps[N]> {
		const component = this.components.get(name)
		if (!component) {
			return this.getFallbackComponent()
		}
		return component as React.ComponentType<DefaultComponentsProps[N]>
	}

	/**
	 * Register components
	 * @param components Component list
	 */
	registerComponents<N extends keyof DefaultComponentsProps>(
		components: Record<N, React.ComponentType<DefaultComponentsProps[N]>>,
	) {
		for (const [name, component] of Object.entries(components)) {
			this.registerComponent(
				name as N,
				component as React.ComponentType<DefaultComponentsProps[N]>,
			)
		}
	}

	/**
	 * Unregister component
	 * @param name Component name
	 */
	unregisterComponent(name: string) {
		this.components.delete(name)
	}

	/**
	 * Unregister components list
	 * @param names List of component names
	 */
	unregisterComponents(names: string[]) {
		names.forEach((name) => this.unregisterComponent(name))
	}
}

export default new ComponentFactory()

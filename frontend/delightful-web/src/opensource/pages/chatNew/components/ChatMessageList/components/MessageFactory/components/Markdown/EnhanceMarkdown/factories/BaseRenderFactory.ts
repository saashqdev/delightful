import { RenderComponent } from "../components/Code/types"
import { LazyExoticComponent, ComponentType, lazy } from "react"

class BaseRenderFactory<Props> {
	/**
	 * Code components
	 */
	protected components = new Map<string, RenderComponent<Props>>()

	/**
	 * Component cache
	 */
	protected componentCache = new Map<string, LazyExoticComponent<ComponentType<Props>>>()

	constructor(initialComponents?: Record<string, RenderComponent<Props>>) {
		this.components = new Map<string, RenderComponent<Props>>(
			initialComponents ? Object.entries(initialComponents) : [],
		)
	}

	/**
	 * Get default component
	 * @returns Default component
	 */
	public getFallbackComponent(): LazyExoticComponent<ComponentType<Props>> {
		return lazy(() => Promise.resolve({ default: () => null }))
	}

	/**
	 * Register component
	 * @param lang Language
	 * @param componentConfig Component configuration
	 */
	registerComponent(lang: string, componentConfig: RenderComponent<Props>) {
		this.components.set(lang, componentConfig)
	}

	/**
	 * Get component
	 * @param type Component type
	 * @returns Component
	 */
	getComponent(type: string): LazyExoticComponent<ComponentType<Props>> {
		// Load and return component
		const codeComponent = this.components.get(type)

		// Check cache
		if (codeComponent && this.componentCache.has(codeComponent.componentType)) {
			return this.componentCache.get(codeComponent.componentType)!
		}

		// No loader
		if (!codeComponent?.loader) {
			return this.getFallbackComponent()
		}

		// create lazy component
		const LazyComponent = lazy(() =>
			codeComponent.loader().then((module) => ({
				default: module.default as ComponentType<Props>,
			})),
		)
		this.componentCache.set(codeComponent.componentType, LazyComponent)
		return LazyComponent
	}

	/**
	 * Clean cache
	 * @param usedTypes Used types
	 */
	cleanCache(usedTypes: string[]) {
		Array.from(this.componentCache.keys()).forEach((type) => {
			if (!usedTypes.includes(type)) {
				this.componentCache.delete(type)
			}
		})
	}
}

export default BaseRenderFactory

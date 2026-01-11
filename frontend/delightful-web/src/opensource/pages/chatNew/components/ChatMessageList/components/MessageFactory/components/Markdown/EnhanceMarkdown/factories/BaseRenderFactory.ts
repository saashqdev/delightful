import { RenderComponent } from "../components/Code/types"
import { LazyExoticComponent, ComponentType, lazy } from "react"

class BaseRenderFactory<Props> {
	/**
	 * 代码component
	 */
	protected components = new Map<string, RenderComponent<Props>>()

	/**
	 * component缓存
	 */
	protected componentCache = new Map<string, LazyExoticComponent<ComponentType<Props>>>()

	constructor(initialComponents?: Record<string, RenderComponent<Props>>) {
		this.components = new Map<string, RenderComponent<Props>>(
			initialComponents ? Object.entries(initialComponents) : [],
		)
	}

	/**
	 * get默认component
	 * @returns 默认component
	 */
	public getFallbackComponent(): LazyExoticComponent<ComponentType<Props>> {
		return lazy(() => Promise.resolve({ default: () => null }))
	}

	/**
	 * 注册component
	 * @param lang 语言
	 * @param componentConfig componentconfiguration
	 */
	registerComponent(lang: string, componentConfig: RenderComponent<Props>) {
		this.components.set(lang, componentConfig)
	}

	/**
	 * getcomponent
	 * @param type componentclass型
	 * @returns component
	 */
	getComponent(type: string): LazyExoticComponent<ComponentType<Props>> {
		// load并returncomponent
		const codeComponent = this.components.get(type)

		// check缓存
		if (codeComponent && this.componentCache.has(codeComponent.componentType)) {
			return this.componentCache.get(codeComponent.componentType)!
		}

		// 没有load器
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
	 * 清除缓存
	 * @param usedTypes 使用过的class型
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

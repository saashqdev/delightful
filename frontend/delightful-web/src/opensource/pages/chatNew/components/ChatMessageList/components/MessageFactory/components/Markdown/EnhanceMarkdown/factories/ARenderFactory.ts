import BaseRenderFactory from "./BaseRenderFactory"
import { AnchorHTMLAttributes, ComponentType, lazy } from "react"

class ARenderFactory extends BaseRenderFactory<AnchorHTMLAttributes<HTMLAnchorElement>> {
	/**
	 * Get matched component
	 * @param props Component properties
	 * @returns Matched component
	 */
	getMatchComponent(props: AnchorHTMLAttributes<HTMLAnchorElement>) {
		for (const [, value] of this.components.entries()) {
			if (value.matchFn?.(props)) {
				const LazyComponent = lazy(() =>
					value.loader().then((module) => ({
						default: module.default as ComponentType<
							AnchorHTMLAttributes<HTMLAnchorElement>
						>,
					})),
				)

				return LazyComponent
			}
		}
		return null
	}
}

export default new ARenderFactory()

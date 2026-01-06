import { memo, Suspense } from "react"
import ComponentFactory from "./ComponentFactory"
import { DefaultComponentsProps } from "./config/defaultComponents"

/**
 * Component render
 * @param componentName Component name
 * @returns Component
 */
type ComponentRenderProps<N extends keyof DefaultComponentsProps> = {
	componentName: N
} & DefaultComponentsProps[N]

/**
 * Component factory - component render
 * @param componentName Component name
 * @returns Component
 */
const ComponentRender = memo(
	<N extends keyof DefaultComponentsProps>({
		componentName,
		children,
		...props
	}: ComponentRenderProps<N>) => {
		const Component = ComponentFactory.getComponent(componentName)

		return (
			<Suspense fallback={null}>
				<Component {...props}>{children}</Component>
			</Suspense>
		)
	},
)

export default ComponentRender

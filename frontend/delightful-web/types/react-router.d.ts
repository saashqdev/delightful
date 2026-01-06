import type { IndexRouteObject, NonIndexRouteObject } from "react-router/dist/lib/context"

/** Extend react-router declarations */
declare module "react-router" {
	interface DelightfulIndexRouteObject extends IndexRouteObject {
		name?: string
	}

	interface DelightfulNonIndexRouteObject extends NonIndexRouteObject {
		name?: string
		children?: Array<DelightfulRouteObject>
	}

	export type DelightfulRouteObject = DelightfulIndexRouteObject | DelightfulNonIndexRouteObject
}

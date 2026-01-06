import type { IndexRouteObject, NonIndexRouteObject } from "react-router/dist/lib/context"

/** Extend react-router declarations */
declare module "react-router" {
	interface MagicIndexRouteObject extends IndexRouteObject {
		name?: string
	}

	interface MagicNonIndexRouteObject extends NonIndexRouteObject {
		name?: string
		children?: Array<MagicRouteObject>
	}

	export type MagicRouteObject = MagicIndexRouteObject | MagicNonIndexRouteObject
}

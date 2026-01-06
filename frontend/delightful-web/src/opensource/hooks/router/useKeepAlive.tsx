import { useLocation, useOutlet, Outlet } from "react-router"
import { useEffect, useMemo, useReducer } from "react"
import { keepAliveRoutes } from "@/const/keepAliveRoutes"
import { RoutePath } from "@/const/routes"
import LoadingFallback from "@/opensource/components/fallback/LoadingFallback"

// Cache state type
type CacheState = Record<string, React.ReactNode>

// Cache action type
type CacheAction =
	| { type: "ADD_CACHE"; path: string; outlet: React.ReactNode }
	| { type: "REMOVE_CACHE"; path: string }

/**
 * Keep components alive per keepAliveRoutes: hide instead of unmounting to avoid remount cost
 * @returns { Content } - component that renders cached outlets
 */
export const useKeepAlive = () => {
	const location = useLocation()
	const outlet = useOutlet()

	// Cache reducer
	const cacheReducer = (state: CacheState, action: CacheAction): CacheState => {
		switch (action.type) {
			case "ADD_CACHE":
				return { ...state, [action.path]: action.outlet }
			case "REMOVE_CACHE":
				const newState = { ...state }
				delete newState[action.path]
				return newState
			default:
				return state
		}
	}

	const [caches, dispatch] = useReducer(cacheReducer, {})

	// Determine if current path should be cached
	const shouldCache = useMemo(
		() => keepAliveRoutes.includes(location.pathname as RoutePath),
		[location.pathname],
	)

	// Manage cache when route changes
	useEffect(() => {
		if (shouldCache) {
			// Only add when not already cached
			if (!caches[location.pathname]) {
				dispatch({
					type: "ADD_CACHE",
					path: location.pathname,
					outlet,
				})
			}
		} else {
			// Remove from cache when path is not configured for caching
			if (caches[location.pathname]) {
				dispatch({ type: "REMOVE_CACHE", path: location.pathname })
			}
		}
	}, [shouldCache, location.pathname, outlet, caches])

	// Render cached content
	const Content = useMemo(() => {
		// Get all cached outlet content
		const cachedOutlets = Object.entries(caches).map(([path, outlet]) => {
			const match = path === location.pathname

			return (
				<div
					key={path}
					style={{
						display: match ? "block" : "none",
						// Ensure hidden elements won't be optimized away by the browser
						position: match ? "relative" : "absolute",
						width: match ? "auto" : 0,
						height: match ? "auto" : 0,
						overflow: "hidden",
					}}
					data-keepalive-id={`keepalive-${path}`}
				>
					{outlet}
				</div>
			)
		})

		return (
			<LoadingFallback>
				{/* Render all cached pages even when hidden */}
				{cachedOutlets}
				{/* Render regular Outlet for non-cached paths */}
				{!shouldCache && <Outlet />}
			</LoadingFallback>
		)
	}, [shouldCache, location.pathname, caches])

	return { Content }
}

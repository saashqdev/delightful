import React from "react"
import {
	ExternalContext,
	ExternalUIContext,
	ExternalConfigContext,
	ExternalRefContext
} from "./Context"

/**
 * Get the full External context (backward compatible)
 */
export const useExternal = () => {
	return React.useContext(ExternalContext)
}

/**
 * Get only UI-related context; rerenders only when UI data changes
 * Suitable for UI-only components such as Header, Toolbar, etc.
 */
export const useExternalUI = () => {
	return React.useContext(ExternalUIContext)
}

/**
 * Get only config-related context; rerenders only when config updates
 * Suitable for components that depend on config values, such as Node, Edge, etc.
 */
export const useExternalConfig = () => {
	return React.useContext(ExternalConfigContext)
}

/**
 * Get only ref-related context; refs rarely change
 * Suitable for components that need external refs
 */
export const useExternalRef = () => {
	return React.useContext(ExternalRefContext)
}

/**
 * Select specific fields from the External context
 * @param selector Selector function to pick fields from context
 * @returns Selected fields
 */
export function useExternalSelector<Selected>(
	selector: (state: React.ContextType<typeof ExternalContext>) => Selected
) {
	const context = React.useContext(ExternalContext)
	return selector(context)
}

import React from "react"
import {
	ExternalContext,
	ExternalUIContext,
	ExternalConfigContext,
	ExternalRefContext
} from "./Context"

/**
 * Get complete External context (backward compatible)
 */
export const useExternal = () => {
	return React.useContext(ExternalContext)
}

/**
 * Get only UI-related context, triggers re-render only when UI updates
 * Suitable for components that only depend on UI parts, such as Header, Toolbar, etc.
 */
export const useExternalUI = () => {
	return React.useContext(ExternalUIContext)
}

/**
 * Get only config-related context, triggers re-render only when config updates
 * Suitable for components that depend on configuration, such as Node, Edge, etc.
 */
export const useExternalConfig = () => {
	return React.useContext(ExternalConfigContext)
}

/**
 * Get only reference-related context, references almost never update
 * Suitable for components that need to access external references
 */
export const useExternalRef = () => {
	return React.useContext(ExternalRefContext)
}

/**
 * Selectively get certain fields from External context
 * @param selector Selector function to select needed fields from context
 * @returns Selected fields
 */
export function useExternalSelector<Selected>(
	selector: (state: React.ContextType<typeof ExternalContext>) => Selected
) {
	const context = React.useContext(ExternalContext)
	return selector(context)
}

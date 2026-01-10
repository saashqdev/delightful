/**
 * Custom props passed from business components
 */
import React, { useMemo } from "react"
import {
	ExternalContext,
	ExternalCtx,
	ExternalUIContext,
	ExternalConfigContext,
	ExternalRefContext,
} from "./Context"

export const ExternalProvider = ({
	header,
	nodeToolbar,
	materialHeader,
	paramsName,
	onlyRenderVisibleElements,
	layoutOnMount,
	allowDebug,
	showExtraFlowInfo,
	flowInteractionRef,
	omitNodeKeys,
	children,
}: ExternalCtx) => {
	// UI-related properties, may change frequently
	const uiValue = useMemo(() => {
		return {
			header,
			nodeToolbar,
			materialHeader,
		}
	}, [header, nodeToolbar, materialHeader])

	// Config-related properties, unlikely to change frequently
	const configValue = useMemo(() => {
		return {
			paramsName,
			onlyRenderVisibleElements,
			layoutOnMount,
			allowDebug,
			showExtraFlowInfo,
			omitNodeKeys,
		}
	}, [
		paramsName,
		onlyRenderVisibleElements,
		layoutOnMount,
		allowDebug,
		showExtraFlowInfo,
		omitNodeKeys,
	])

	// Reference-related properties, almost never change
	const refValue = useMemo(() => {
		return {
			flowInteractionRef,
		}
	}, [flowInteractionRef])

	// Complete Context value (backward compatible)
	const fullValue = useMemo(() => {
		return {
			...uiValue,
			...configValue,
			...refValue,
		}
	}, [uiValue, configValue, refValue])

	return (
		<ExternalRefContext.Provider value={refValue}>
			<ExternalConfigContext.Provider value={configValue}>
				<ExternalUIContext.Provider value={uiValue}>
					<ExternalContext.Provider value={fullValue}>
						{children}
					</ExternalContext.Provider>
				</ExternalUIContext.Provider>
			</ExternalConfigContext.Provider>
		</ExternalRefContext.Provider>
	)
}

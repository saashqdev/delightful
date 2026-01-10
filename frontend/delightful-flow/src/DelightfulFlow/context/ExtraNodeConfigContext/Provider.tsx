/**
 * Custom props passed from business components
 */
import React, { useMemo } from "react"
import { ExtraNodeConfigContext, ExtraNodeConfigCtx } from "./Context"

export const ExtraNodeConfigProvider = ({
	commonStyle,
	nodeStyleMap,
	customNodeRenderConfig,
	children,
}: ExtraNodeConfigCtx) => {
	const value = useMemo(() => {
		return {
			commonStyle,
			nodeStyleMap,
			customNodeRenderConfig,
		}
	}, [commonStyle, nodeStyleMap, customNodeRenderConfig])

	return (
		<ExtraNodeConfigContext.Provider value={value}>{children}</ExtraNodeConfigContext.Provider>
	)
}

/**
 * Custom props passed from business components
 */
import { flowStore } from "@/DelightfulFlow/store"
import { getRegisterNodeTypes } from "@/DelightfulFlow/utils"
import { useNodeMap } from "@/common/context/NodeMap/useResize"
import React, { useEffect } from "react"
import { DelightfulFlowContext } from "./Context"

export const DelightfulFlowProvider = ({ children }: React.PropsWithChildren<{}>) => {
	const { updateDisplayMaterialType, updateNodeVersionSchema } = flowStore.getState()

	const { nodeMap } = useNodeMap()

	useEffect(() => {
		const defaultDisplayMaterialTypes = getRegisterNodeTypes()
		updateDisplayMaterialType(defaultDisplayMaterialTypes)
	}, [])

	useEffect(() => {
		updateNodeVersionSchema(nodeMap)
	}, [nodeMap])

	return <DelightfulFlowContext.Provider value={flowStore}>{children}</DelightfulFlowContext.Provider>
}

/**
 * Business components supply custom props
 */
import { flowStore } from "@/DelightfulFlow/store"
import { getRegisterNodeTypes } from "@/DelightfulFlow/utils"
import { useNodeMap } from "@/common/context/NodeMap/useResize"
import React, { useEffect } from "react"
import { MagicFlowContext } from "./Context"

export const MagicFlowProvider = ({ children }: React.PropsWithChildren<{}>) => {
	const { updateDisplayMaterialType, updateNodeVersionSchema } = flowStore.getState()

	const { nodeMap } = useNodeMap()

	useEffect(() => {
		const defaultDisplayMaterialTypes = getRegisterNodeTypes()
		updateDisplayMaterialType(defaultDisplayMaterialTypes)
	}, [])

	useEffect(() => {
		updateNodeVersionSchema(nodeMap)
	}, [nodeMap])

	return <MagicFlowContext.Provider value={flowStore}>{children}</MagicFlowContext.Provider>
}

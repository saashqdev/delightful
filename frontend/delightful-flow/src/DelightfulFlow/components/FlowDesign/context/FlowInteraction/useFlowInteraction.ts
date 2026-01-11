import React from "react"
import { 
	FlowInteractionContext, 
	FlowInteractionStateContext, 
	FlowInteractionActionsContext,
	FlowInteractionStateType,
	FlowInteractionActionsType
} from "./Context"

// getstatus的hook
export const useFlowInteractionState = (): FlowInteractionStateType => {
	return React.useContext(FlowInteractionStateContext)
}

// get动作的hook
export const useFlowInteractionActions = (): FlowInteractionActionsType => {
	return React.useContext(FlowInteractionActionsContext)
}

// 原有hook，向后兼容
export const useFlowInteraction = () => {
	return React.useContext(FlowInteractionContext)
}


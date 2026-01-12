import React from "react"
import { 
	FlowInteractionContext, 
	FlowInteractionStateContext, 
	FlowInteractionActionsContext,
	FlowInteractionStateType,
	FlowInteractionActionsType
} from "./Context"

// Hook to get state
export const useFlowInteractionState = (): FlowInteractionStateType => {
	return React.useContext(FlowInteractionStateContext)
}

// Hook to get actions
export const useFlowInteractionActions = (): FlowInteractionActionsType => {
	return React.useContext(FlowInteractionActionsContext)
}

// Original hook, backward compatible
export const useFlowInteraction = () => {
	return React.useContext(FlowInteractionContext)
}


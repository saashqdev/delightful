import { DelightfulFlowInstance } from "@delightful/delightful-flow/dist/DelightfulFlow"
import React, { createContext, useContext, MutableRefObject, useMemo } from "react"

interface FlowInstanceContextProps {
	flowInstance: MutableRefObject<DelightfulFlowInstance | null>
}

const FlowInstanceContext = createContext<FlowInstanceContextProps | undefined>(undefined)

interface FlowInstanceProviderProps extends React.PropsWithChildren {
	flowInstance: MutableRefObject<DelightfulFlowInstance | null>
}

export const FlowInstanceProvider = ({ flowInstance, children }: FlowInstanceProviderProps) => {
	const value = useMemo(() => {
		return { flowInstance }
	}, [flowInstance])

	return <FlowInstanceContext.Provider value={value}>{children}</FlowInstanceContext.Provider>
}

export const useFlowInstance = (): FlowInstanceContextProps => {
	const value = useContext(FlowInstanceContext)
	return value || { flowInstance: { current: null } }
}

export default FlowInstanceProvider






import { NodeGroup } from "@/DelightfulFlow/register/node"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"
import React, { useMemo } from "react"

// Material grouping
export type MaterialGroup = Partial<Omit<NodeGroup, "nodeTypes" | "children">> & {
	detail: Pick<DelightfulFlow.Node, "input" | "output" | "id" | "custom_system_input"> // Specific node configuration
	name: string // Node name
	description: string // Node description
	avatar: string // Avatar URL
	isGroupNode: boolean
	children: MaterialGroup[]
	id: string
}

export enum AgentType {
	Person = 0,
	Enterprise = 1,
	Market = 2,
}

export type MaterialSourceCtx = React.PropsWithChildren<{
	tools?: {
		groupList: MaterialGroup[]
		searchListFn: (keyword: string) => Promise<void>
		getNextPageFn: () => Promise<void>
	}
	subFlow?: {
		list: DelightfulFlow.Flow
		searchListFn: (keyword: string) => Promise<void>
		getNextPageFn: () => Promise<void>
	}
	agent?: {
		list: Partial<DelightfulFlow.Flow>
		searchListFn: (type: AgentType, keyword: string) => Promise<void>
		getNextPageFn: () => Promise<void>
	}
}>

export const MaterialSourceContext = React.createContext({} as MaterialSourceCtx)

export const MaterialSourceProvider = ({ tools, subFlow, agent, children }: MaterialSourceCtx) => {
	const value = useMemo(() => {
		return {
			tools,
			subFlow,
			agent,
		}
	}, [tools, subFlow, agent])

	return <MaterialSourceContext.Provider value={value}>{children}</MaterialSourceContext.Provider>
}

export const useMaterialSource = () => {
	return React.useContext(MaterialSourceContext)
}

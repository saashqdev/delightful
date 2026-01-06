import type { NodeTestingCtx } from "@delightful/delightful-flow/dist/DelightfulFlow/context/NodeTesingContext/Context"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import type { Dispatch, SetStateAction } from "react"
import React from "react"

export type CustomFlowCtx = React.PropsWithChildren<{
	testNode?: (node: DelightfulFlow.Node, dataSource: Record<string, any>) => Promise<void>
	resetTestingConfig?: () => void
	testingConfig?: NodeTestingCtx
	testFlowResult: NodeTestingCtx["testingResultMap"]
	setCurrentFlow: Dispatch<SetStateAction<DelightfulFlow.Flow | undefined>>
}>

export const CustomFlowContext = React.createContext({
	resetTestingConfig: () => {},
} as CustomFlowCtx)

export default null

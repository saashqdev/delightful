import { installNodes } from "@/DelightfulFlow/register/node"
import { nodeSchemaMap } from "../constants"

export const installAllNodes = () => {
	// registerNotMaterialNodeTypes([customNodeType.Start])
	/**
	 * Register all node schemas
	 */
	// @ts-ignore
	installNodes(nodeSchemaMap)
}


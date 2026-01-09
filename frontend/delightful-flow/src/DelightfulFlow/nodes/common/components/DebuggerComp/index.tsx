import { DefaultNodeVersion } from "@/DelightfulFlow/constants"
import { useFlowData, useSingleNodeConfig } from "@/DelightfulFlow/context/FlowContext/useFlow"
import { copyToClipboard } from "@/DelightfulFlow/utils"
import { message } from "antd"
import { useCallback, memo } from "react"
import i18next from "i18next"
import { useCurrentNode } from "../../context/CurrentNode/useCurrentNode"
import "./index.less"

type DebuggerCompProps = {
	id: string
}

// Wrap with memo to avoid unnecessary rerenders
const DebuggerComp = memo(
	function DebuggerComp({ id }: DebuggerCompProps) {
		// Subscribe only to the current node config
		const nodeConfig = useSingleNodeConfig(id)
		const { debuggerMode } = useFlowData()
		const { currentNode } = useCurrentNode()

		// Use useCallback instead of useMemoizedFn to reduce dependencies
		const clickNode = useCallback(() => {
			console.log("debug node", nodeConfig)
			copyToClipboard(id)
			message.success(i18next.t("common.copySuccess", { ns: "delightfulFlow" }))
		}, [id, nodeConfig])

		// Skip rendering when not in debug mode
		if (!debuggerMode) {
			return null
		}

		// Extract node version to avoid calculations in JSX
		const nodeVersion = currentNode?.node_version || DefaultNodeVersion

		return (
			<p className="debugger-id" onClick={clickNode}>
				<span>
					{i18next.t("flow.nodeId", { ns: "delightfulFlow" })}：{id}
				</span>
				<br />
				<span>
					{i18next.t("flow.nodeVersion", { ns: "delightfulFlow" })}：{nodeVersion}
				</span>

				{/* <br />
			<span>{width + "," + height}</span>
			<br />
			<span>{x + "," + y}</span> */}
			</p>
		)
	},
	(prevProps, nextProps) => {
		// Only rerender when id changes
		return prevProps.id === nextProps.id
	},
)

export default DebuggerComp


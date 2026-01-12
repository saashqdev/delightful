/**
 * delightful-flow node business component
 */
import { DelightfulFlowInstance } from "@/DelightfulFlow"
import DelightfulFlowModal from "@/DelightfulFlow/modal/DelightfulFlowModal"
import { Button } from "antd"
import { IconCopyPlus } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import React, { useMemo, useRef } from "react"
import { NodeMapProvider } from "../../../common/context/NodeMap/Provider"
import { nodeSchemaMap } from "./constants"
import styles from "./index.module.less"
import flow from "./mock/flow"
import useToolbar from "./toolbar"
import { installAllNodes } from "./utils"

type FlowProps = { open?: boolean; onClose?: () => void }

export default function BaseFlow({ open, onClose }: FlowProps) {
	installAllNodes()
	const flowInstance = useRef(null as null | DelightfulFlowInstance)

	const toolbars = useToolbar()

	const consoleFlow = useMemoizedFn(() => {
		const flow = flowInstance?.current?.getFlow()
		console.log("Internal flow", flow)
	})

	const Buttons = useMemo(() => {
		return (
			<>
				<Button loading={false} onClick={consoleFlow}>
					Test Run
				</Button>
				<Button type="primary" loading={false}>
					release
				</Button>
				<Button
					// @ts-ignore
					theme="light"
					className={styles.copyButton}
				>
					<IconCopyPlus color="#77777b" />
				</Button>
			</>
		)
	}, [])

	const flowHeader = useMemo(() => {
		return {
			buttons: Buttons,
		}
	}, [Buttons])

	return (
		// @ts-ignore
		<NodeMapProvider nodeMap={nodeSchemaMap}>
			<DelightfulFlowModal
				ref={flowInstance}
				header={flowHeader}
				// @ts-ignore
				flow={flow}
				nodeToolbar={{
					list: toolbars,
				}}
				open={open}
				onClose={onClose}
			/>
		</NodeMapProvider>
	)
}


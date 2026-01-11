/**
 * delightful-flow node business component
 */
import DelightfulFlow, { DelightfulFlowInstance } from "@/DelightfulFlow"
import { ExtraNodeConfigProvider } from "@/DelightfulFlow/context/ExtraNodeConfigContext/Provider"
import { MaterialSourceProvider } from "@/DelightfulFlow/context/MaterialSourceContext/MaterialSourceContext"
import { NodeChangeListenerProvider } from "@/DelightfulFlow/context/NodeChangeListenerContext/NodeChangeListenerContext"
import { NodeTestingProvider } from "@/DelightfulFlow/context/NodeTesingContext/Provider"
import AgentImage from "@/common/assets/agent-avatar.jpg"
import { createI18nNext } from "@/common/locales/create"
import AppearanceProvider from "@/common/provider/AppearanceProvider"
import { Button } from "antd"
import { IconCopyPlus } from "@tabler/icons-react"
import { useEventEmitter, useMemoizedFn, useMount } from "ahooks"
import i18next from "i18next"
import { debounce } from "lodash"
import React, { useMemo, useRef, useState } from "react"
import { NodeMapProvider } from "../../../common/context/NodeMap/Provider"
import { customNodeType } from "./constants"
import useMaterialSource from "./hooks/useMaterialSource"
import useNodesTest from "./hooks/useNodesTest"
import styles from "./index.module.less"
import flow from "./mock/flow"
import useToolbar from "./toolbar"
import { installAllNodes } from "./utils"
import { generateNodeVersionSchema } from "./utils/version"
import delightfulFlowEnJson from "@/common/locales/en_US/delightfulFlow.json"

// const { init, instance } = createI18nNext("en_US")
// init().catch(console.error)

// Ensure i18next is initialized only once
if (!i18next.isInitialized) {
	i18next
		.init({
			lng: "zh", // Default language
			fallbackLng: "en",
			debug: true,
			resources: {
				en: {
					delightfulFlow: delightfulFlowEnJson,
				},
			},
			interpolation: {
				escapeValue: false,
			},
		})
		.then(() => {
			console.log("i18next initialized successfully")
		})
		.catch((error) => {
			console.error("i18next initialization error:", error)
		})
}

// // Export i18next instance for downstream components to import and use
// export { i18next }

installAllNodes()
export default function BaseFlow() {
	const flowInstance = useRef(null as null | DelightfulFlowInstance)

	const [serverFlow, setServerFlow] = useState(null)

	const nodeChangeEventListener = useEventEmitter<string>()

	const toolbars = useToolbar()

	const consoleFlow = useMemoizedFn(() => {
		const flow = flowInstance?.current?.getFlow()
		console.log("Internal flow", flow)
	})

	const handleNodeConfigChange = useMemoizedFn(
		debounce(() => {
			console.log("Node changed")
		}, 500),
	)

	nodeChangeEventListener?.useSubscription(handleNodeConfigChange)

	useMount(() => {
		setTimeout(() => {
			setServerFlow(flow as any)
		}, 500)
	})

	const Buttons = useMemo(() => {
		return (
			<>
				<Button loading={false} onClick={consoleFlow}>
					Trial Run
				</Button>
				<Button type="primary" loading={false}>
					Publish
				</Button>
				<Button
					ghost
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
			defaultImage: AgentImage,
		}
	}, [Buttons])

	const { nowTestingNodeIds, testingNodeIds, testingResultMap, position } = useNodesTest()

	const { subFlow, tools } = useMaterialSource()

	const nodeSchemaMap = useMemo(() => {
		return generateNodeVersionSchema()
	}, [])

	return (
		// <AppearanceProvider i18nInstance={instance}>
		<NodeMapProvider nodeMap={nodeSchemaMap}>
			{/* @ts-ignore */}
			<MaterialSourceProvider subFlow={subFlow} tools={tools}>
				<NodeChangeListenerProvider customListener={nodeChangeEventListener}>
					<ExtraNodeConfigProvider
						// nodeStyleMap={{
						// 	[customNodeType.Start]: {
						// 		width: "900px",
						// 	},
						// }}
						customNodeRenderConfig={{
							[customNodeType.Start]: {
								hiddenDesc: true,
							},
						}}
					>
						<NodeTestingProvider
							nowTestingNodeIds={nowTestingNodeIds}
							testingNodeIds={testingNodeIds}
							testingResultMap={testingResultMap}
							position={false}
						>
							<DelightfulFlow
								ref={flowInstance}
								header={flowHeader}
								// @ts-ignore
								flow={serverFlow}
								nodeToolbar={{
									list: toolbars,
								}}
								customParamsName={{
									params: "params",
									nodeType: "node_type",
								}}
								layoutOnMount={true}
								allowDebug
								omitNodeKeys={[
									"data",
									"expandParent",
									"extent",
									"parentId",
									"deletable",
									"position",
									"step",
									"zIndex",
									"type",
								]}
								showExtraFlowInfo={false}
							/>
						</NodeTestingProvider>
					</ExtraNodeConfigProvider>
				</NodeChangeListenerProvider>
			</MaterialSourceProvider>
		</NodeMapProvider>
		// </AppearanceProvider>
	)
}


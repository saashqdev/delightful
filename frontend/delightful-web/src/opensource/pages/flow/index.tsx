/**
 * delightful-flow node business component
 */
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { useTranslation } from "react-i18next"
import type {
	DelightfulFlowInstance,
	NodeSchema,
} from "@bedelightful/delightful-flow/dist/DelightfulFlow"
import DelightfulFlowComponent from "@bedelightful/delightful-flow/dist/DelightfulFlow"
import { MaterialSourceProvider } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/MaterialSourceContext/MaterialSourceContext"
import { NodeMapProvider } from "@bedelightful/delightful-flow/dist/common/context/NodeMap/Provider"
import { RoutePath } from "@/const/routes"
import { ToastContainer } from "react-toastify"
import { NodeChangeListenerProvider } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/NodeChangeListenerContext/NodeChangeListenerContext"
import { ExtraNodeConfigProvider } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/ExtraNodeConfigContext/Provider"
import { useEffect, useMemo, useRef, useState } from "react"
import { ConfigProvider, message } from "antd"
import { useMemoizedFn, useSize } from "ahooks"
import { FlowType, type TestResult, type TriggerConfig } from "@/types/flow"
import locale from "antd/es/locale/en_US"
import { IconChevronLeft, IconMenu2, IconRobot } from "@tabler/icons-react"
import DefaultImage from "@/assets/logos/agent-avatar.jpg"
import { cx } from "antd-style"
import { useLocation, useParams } from "react-router"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import { customNodeType, OmitNodeKeys } from "./constants"
import { installAllNodes } from "./utils"
import styles from "./index.module.less"
import TestFlowButton from "./components/TestFlowButton"
import { CustomFlowProvider } from "./context/CustomFlowContext/Provider"
import registerEditor from "./utils/editor"
import DraftListButton from "./components/DraftListButton"
import PublishFlowButton from "./components/PublishFlowButton"
import GlobalVariablesButton from "./components/GlobalVariablesButton/GlobalVariablesButton"
import PublishListButton from "./components/PublishListButton"
import { ResourceTypes } from "./components/AuthControlButton/types"
import { shadowFlow } from "./utils/helpers"
import useAgent from "./hooks/useAgent"
import useFlowDetail from "./hooks/useFlowDetail"
import useCheckType from "./hooks/useCheckType"
import useEditAgentModal from "./hooks/useEditAgentModal"
import AgentInfoButton from "./components/AgentInfoButton"
import PublishAgentButton from "./components/PublishAgentButton"
import AuthControlButton from "./components/AuthControlButton/AuthControlButton"
import QuickInstructionButton from "./components/QuickInstructionButton"
import useRights from "./hooks/useRights"
import useAutoSave from "./hooks/useAutoSave"
import useCustomTagList from "./hooks/useCustomTagList"
import "react-toastify/dist/ReactToastify.css"
import useDataInit from "./hooks/useDataInit"
import OperateMenu from "./components/OperateMenu"
import ApiKeyButton from "./components/ApikeyButton"
import useChangeListener from "./hooks/useChangeListener"
import useDraftToast from "./hooks/useDraftToast"
import { generateNodeVersionSchema } from "./utils/version"
import useEvent from "./hooks/useEvent"
import "./override.css"
import useMaterialSource from "./hooks/useMaterialSource"
import FlowAssistant from "./components/FlowAssistant/index"
import { useGlobalLanguage } from "@/opensource/models/config/hooks"
import { flowService } from "@/opensource/services"
import { ComponentVersionMap } from "./nodes"
import CommercialProvider from "./context/CommercialContext"
import FlowInstanceProvider from "./context/FlowInstanceContext"
// import flow from "./mock/flow"

registerEditor()

export type BaseFlowProps = {
	extraData?: {
		canReferenceNodeTypes: string[]
		getEnterpriseSchemaConfigMap: () => Record<string, NodeSchema>
		extraNodeInfos: any
		enterpriseNodeComponentVersionMap: Record<string, Record<string, ComponentVersionMap>>
		enterpriseNodeTypes: Record<string, string>
	}
}

export default function BaseFlow({ extraData }: BaseFlowProps) {
	const navigate = useNavigate() // Inside component
	const location = useLocation()
	const params = useParams()
	const { t } = useTranslation()
	// Whether testing is in progress
	const [isTesting, setIsTesting] = useState(false)
	// Whether to show FlowAssistant
	const [showFlowAssistant, setShowFlowAssistant] = useState(false)

	const flowInstance = useRef(null as null | DelightfulFlowInstance)

	const flowInteractionRef = useRef(null as any)

	// Listen for language changes and reinstall nodes
	const language = useGlobalLanguage(true)

	const isCommercial = useMemo(() => {
		return !!extraData
	}, [extraData])

	useEffect(() => {
		installAllNodes(extraData)
	}, [language, extraData])

	// Browser-related event interception handling
	useEvent()

	const size = useSize(document.body)

	const isCollapse = useMemo(() => {
		return size?.width && size.width <= 1500
	}, [size?.width])

	// AI assistant related state
	const { agent, defaultIcon, setAgent, initAgentPublishList } = useAgent()

	const { showFlowIsDraftToast } = useDraftToast()

	// Flow related state
	const { currentFlow, setCurrentFlow, initDraftList, initPublishList } = useFlowDetail({
		agent,
		showFlowIsDraftToast,
	})

	// Collect data required on first load and mount to store
	useDataInit({ currentFlow })

	// Whether current is Agent detail
	const { isAgent } = useCheckType()

	// Current permissions
	const { isEditRight, isAdminRight, isReleasedToMarket } = useRights({
		flow: currentFlow!,
		agent,
	})

	const { lastSaveTime, isSaving, saveDraft } = useAutoSave({
		flowInstance,
		isAgent,
		initDraftList,
		setCurrentFlow,
	})

	const { nodeChangeEventListener } = useChangeListener({ saveDraft })

	// Agent edit modal
	const { EditAgentModal, openAddAgentModal } = useEditAgentModal({
		agent,
		setAgent,
		currentFlow,
		setCurrentFlow,
	})

	const [testResult, setTestResult] = useState<TestResult["node_debug"]>()

	useEffect(() => {
		if (currentFlow?.id && !currentFlow?.icon) {
			let icon = ""
			// @ts-ignore
			if (currentFlow?.type === FlowType.Main) {
				icon = defaultIcon.bot
				// @ts-ignore
			} else if (currentFlow?.type === FlowType.Tools) {
				icon = defaultIcon.tool_set
			} else {
				icon = defaultIcon.flow
			}

			if (!icon) return

			setCurrentFlow({
				...currentFlow,
				icon,
			})
		}
	})

	const testFlow = useMemoizedFn(async (triggerConfig: TriggerConfig, closeModal: () => void) => {
		const flow = flowInstance?.current?.getFlow()
		// console.log("Internal flow", flow, serverFlow)
		if (!flow) return
		setIsTesting(true)
		const shadowedFlow = shadowFlow(flow)
		try {
			const res = await flowService.testFlow({
				...shadowedFlow,
				trigger_config: triggerConfig,
			})
			setTestResult(res.node_debug)
			setIsTesting(false)
			if (res?.success) {
				message.success(t("common.testSuccess", { ns: "flow" }))
			} else {
				message.error(t("common.testFail", { ns: "flow" }))
			}
			closeModal?.()
		} catch (err) {
			setIsTesting(false)
		}
	})

	const getDropdownItems = useMemoizedFn(() => {
		return (
			<>
				{isAgent && isAdminRight && isReleasedToMarket && (
					<DelightfulButton justify="flex-start" size="large" type="text" block>
						<AuthControlButton
							Icon
							resourceId={agent.botEntity.id as string}
							resourceType={ResourceTypes.Agent}
						/>
					</DelightfulButton>
				)}
				{isEditRight && (
					<DelightfulButton justify="flex-start" size="large" type="text" block>
						<ApiKeyButton flow={currentFlow!} Icon isAgent={isAgent} />
					</DelightfulButton>
				)}
				<div className={styles.divider} />
				<DelightfulButton justify="flex-start" size="large" type="text" block>
					<GlobalVariablesButton
						Icon
						flow={currentFlow}
						hasEditRight={isEditRight || isAdminRight}
						flowInstance={flowInstance}
					/>
				</DelightfulButton>
				{isAgent && isEditRight && (
					<DelightfulButton justify="flex-start" size="large" type="text" block>
						<QuickInstructionButton agent={agent} Icon />
					</DelightfulButton>
				)}
			</>
		)
	})

	const moreIcon = useMemo(() => {
		return (
			<DelightfulButton
				className={styles.moreIcon}
				icon={<IconMenu2 size={16} color="#000000" />}
			/>
		)
	}, [])

	const isMainFlow = useMemo(() => {
		if (!currentFlow) return true
		// @ts-ignore
		return currentFlow?.type === FlowType.Main
	}, [currentFlow])

	const Buttons = useMemo(() => {
		return (
			<>
				{isAgent && (
					<>
						<AgentInfoButton agent={agent} isAdminRight={isAdminRight} />
						{!isCollapse && isAdminRight && isReleasedToMarket && (
							<AuthControlButton
								resourceId={agent.botEntity.id as string}
								resourceType={ResourceTypes.Agent}
							/>
						)}
					</>
				)}
				{isCollapse && (
					<OperateMenu
						useIcon
						Icon={moreIcon}
						menuItems={getDropdownItems()}
						trigger="click"
					/>
				)}
				<div className={styles.line} />
				{!isCollapse && (
					<>
						<GlobalVariablesButton
							flow={currentFlow}
							hasEditRight={isEditRight || isAdminRight}
							flowInstance={flowInstance}
						/>
						{isAgent && isEditRight && <QuickInstructionButton agent={agent} />}

						<div className={styles.line} />
						{isEditRight && <ApiKeyButton flow={currentFlow!} isAgent={isAgent} />}
					</>
				)}
				{isEditRight && <PublishListButton isAgent={isAgent} flow={currentFlow} />}
				{isEditRight && (
					<>
						<DraftListButton
							flow={currentFlow}
							flowInstance={flowInstance}
							initDraftList={initDraftList}
							showFlowIsDraftToast={showFlowIsDraftToast}
						/>
						<TestFlowButton
							onFinished={testFlow}
							loading={isTesting}
							flow={currentFlow}
							flowInstance={flowInstance}
						/>
						{/* Add AI assistant button */}

						{/* {isEditRight && isCommercial && (
							<DelightfulButton
								onClick={() => setShowFlowAssistant((prev) => !prev)}
								icon={<IconRobot size={16} />}
							>
								{t("common.flowAssistant", { ns: "flow" })}
							</DelightfulButton>
						)} */}

						{/* <SaveDraftButton
							flowInstance={flowInstance}
							flow={currentFlow}
							initDraftList={initDraftList}
						/> */}
						{!isAgent && (
							<PublishFlowButton
								flowInstance={flowInstance}
								flow={currentFlow}
								isMainFlow={isMainFlow}
								initPublishList={initPublishList}
							/>
						)}
					</>
				)}

				{isAgent && isEditRight && (
					<PublishAgentButton
						agent={agent}
						setAgent={setAgent}
						flowInstance={flowInstance}
						initPublishList={initAgentPublishList}
					/>
				)}
			</>
		)
	}, [
		isAgent,
		agent,
		isAdminRight,
		isCollapse,
		isReleasedToMarket,
		moreIcon,
		getDropdownItems,
		currentFlow,
		isEditRight,
		initDraftList,
		showFlowIsDraftToast,
		testFlow,
		isTesting,
		t,
		isMainFlow,
		initPublishList,
		setAgent,
		initAgentPublishList,
	])

	const navigateBack = useMemoizedFn(() => {
		// Check if there is a previous page in the history stack
		if (window.history.length <= 1 || !window.history.state) {
			// Get type from URL or params
			const type = params.type || location.pathname.split("/").filter(Boolean)[1]

			if (type) {
				// When type parameter exists, return to the corresponding list page
				navigate(`/flow/${type}/list`)
			} else {
				// When no type parameter, default to Agent list
				navigate(RoutePath.AgentList)
			}
		} else {
			// When there is history, go back normally
			window.history.back()
		}
	})

	// Custom tag list
	const { customTags } = useCustomTagList({
		flow: currentFlow,
		isMainFlow,
		isSaving,
		lastSaveTime,
		isAgent,
		agent,
		setCurrentFlow,
	})

	const flowHeader = useMemo(() => {
		return {
			buttons: Buttons,
			backIcon: (
				<IconChevronLeft stroke={2} className={styles.backIcon} onClick={navigateBack} />
			),
			defaultImage: DefaultImage,
			editEvent: isAgent
				? () => {
						// TODO2 Agent - handle remaining interface calls and echo processing for editing basic information
						openAddAgentModal()
				  }
				: null,
			customTags,
		}
	}, [Buttons, navigateBack, isAgent, customTags, openAddAgentModal])

	const nodeSchemaMap = useMemo(() => {
		return generateNodeVersionSchema(
			extraData?.enterpriseNodeComponentVersionMap || {},
			extraData?.getEnterpriseSchemaConfigMap || (() => ({})),
			extraData?.enterpriseNodeTypes || {},
		)
	}, [extraData])

	const { subFlow, tools } = useMaterialSource()

	const nodeStyleMap = useMemo(() => {
		return {
			[customNodeType.Start]: {
				width: isMainFlow ? "480px" : "900px",
			},
		}
	}, [isMainFlow])

	return (
		<FlowInstanceProvider flowInstance={flowInstance}>
			<CommercialProvider extraData={extraData}>
				<NodeMapProvider nodeMap={nodeSchemaMap}>
					{/* @ts-ignore */}
					<MaterialSourceProvider subFlow={subFlow} tools={tools}>
						{/* @ts-ignore */}
						<NodeChangeListenerProvider customListener={nodeChangeEventListener}>
							<ExtraNodeConfigProvider nodeStyleMap={nodeStyleMap}>
								{/* @ts-ignore */}
								<ConfigProvider locale={locale}>
									<CustomFlowProvider
										testFlowResult={testResult}
										setCurrentFlow={setCurrentFlow}
									>
										<div
											className={cx(styles.flowWrapper, {
												// [styles.mainFlow]: isMainFlow,
											})}
										>
											{/* @ts-ignore */}
											<DelightfulFlowComponent
												header={flowHeader}
												showExtraFlowInfo={false}
												ref={flowInstance}
												flow={currentFlow}
												onlyRenderVisibleElements
												layoutOnMount={false}
												allowDebug
												// @ts-ignore
												flowInteractionRef={flowInteractionRef}
												omitNodeKeys={OmitNodeKeys}
											/>
										</div>

										{/* Add FlowAssistant component */}
										{isCommercial && showFlowAssistant && isEditRight && (
											<FlowAssistant
												flowInteractionRef={flowInteractionRef}
												flow={currentFlow}
												onClose={() => setShowFlowAssistant(false)}
												isAgent={isAgent}
												saveDraft={saveDraft}
												isEditRight={isEditRight}
											/>
										)}

										<ToastContainer
											toastClassName="toast"
											position="top-right"
											autoClose={2000}
											hideProgressBar
											newestOnTop={false}
											closeOnClick
											rtl={false}
											pauseOnFocusLoss
											draggable
											pauseOnHover
											className={styles.toastContainer}
										/>
										{EditAgentModal}
									</CustomFlowProvider>
								</ConfigProvider>
							</ExtraNodeConfigProvider>
						</NodeChangeListenerProvider>
					</MaterialSourceProvider>
				</NodeMapProvider>
			</CommercialProvider>
		</FlowInstanceProvider>
	)
}

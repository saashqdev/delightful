/**
 * Flow-related states
 */
import { useFlowStore } from "@/opensource/stores/flow"
import type { Bot } from "@/types/bot"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { useMemoizedFn, useMount, useUpdateEffect } from "ahooks"
import { useEffect, useState } from "react"
import { useParams } from "react-router"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import { message } from "antd"
import { RoutePath } from "@/const/routes"
import { useTranslation } from "react-i18next"
import { FlowApi } from "@/apis"
import { useOrganization } from "@/opensource/models/user/hooks"
import useCheckType from "./useCheckType"
import useLoadFlow from "./useLoadFlow"
import { unShadowFlow } from "../utils/helpers"
import { Status } from "../agent/constants"

type FlowDetailProps = {
	agent: Bot.Detail
	showFlowIsDraftToast: () => void
}

export default function useFlowDetail({ agent, showFlowIsDraftToast }: FlowDetailProps) {
	const { t } = useTranslation()
	const { id } = useParams()
	const flowId = id as string

	const { isAgent } = useCheckType()

	const navigate = useNavigate()

	const { updateFlowDraftList, updateFlowPublishList, draftList } = useFlowStore()

	const [currentFlow, setCurrentFlow] = useState<DelightfulFlow.Flow>()

	useMount(() => {
		// Reset version and draft data on initial load to avoid dirty data impact
		updateFlowPublishList([])
		updateFlowDraftList([])
	})

	const { organizationCode } = useOrganization()

	const initPublishList = useMemoizedFn(async (flowCode) => {
		const publishData = await FlowApi.getFlowPublishList(flowCode)
		updateFlowPublishList(publishData.list)
	})

	/**
	 * Load the latest draft
	 */
	const loadLatestDraft = useMemoizedFn(
		async (flowCode: string, extraProps?: Record<string, any>) => {
			const lastDraft = draftList[0]
			if (lastDraft) {
				const draftDetail = await FlowApi.getFlowDraftDetail(flowCode, lastDraft.id)

				const decodeFlow = unShadowFlow(draftDetail.delightful_flow)
				setCurrentFlow({
					...decodeFlow,
					...extraProps,
					// Old data might be id or code, so make it compatible
					id: decodeFlow.code || decodeFlow.id,
				})
				showFlowIsDraftToast()
			}
		},
	)

	const { loadConfirmUI } = useLoadFlow({ isAgent, agent, loadLatestDraft, flowId })

	const initDraftList = useMemoizedFn(async (flowCode) => {
		const draftData = await FlowApi.getFlowDraftList(flowCode)
		updateFlowDraftList(draftData.list)
		loadConfirmUI(draftData.list)
		return draftData.list
	})

	// Initialize version list and draft list
	const initFlowData = useMemoizedFn(async () => {
		// If there's a value, it means it's already initialized, no need to proceed
		if (currentFlow) return
		if (!isAgent) {
			initPublishList(flowId)
			const latestDraftList = await initDraftList(flowId)
			const data = await FlowApi.getFlow(flowId)
			// If never published, automatically load the latest draft
			if (!data.enabled && latestDraftList.length > 0) {
				loadLatestDraft(flowId, {
					enabled: data.enabled,
				})
				return
			}
			setCurrentFlow({ ...data })
			return
		}
		if (agent?.delightfulFlowEntity?.id) {
			// initPublishList(agent.delightfulFlowEntity.id)
			const latestDraftList = await initDraftList(agent.delightfulFlowEntity.id)
			// When version doesn't exist, it means never published, load the latest draft (if draft exists)
			if (!agent?.botVersionEntity && latestDraftList.length > 0) {
				loadLatestDraft(agent.delightfulFlowEntity.id, {
					user_operation: agent.botEntity.user_operation,
					icon: agent.botEntity.robot_avatar,
					name: agent.botEntity.robot_name,
					enabled: agent.botEntity.status === Status.enable,
				})
				showFlowIsDraftToast()
				return
			}

			setCurrentFlow({
				...agent.delightfulFlowEntity,
				id: agent.delightfulFlowEntity.id,
				user_operation: agent.botEntity.user_operation,
				icon: agent.botEntity.robot_avatar,
				name: agent.botEntity.robot_name,
				enabled: agent.botEntity.status === Status.enable,
			})
		}
	})

	useUpdateEffect(() => {
		message.info(t("common.organizationChangeTips", { ns: "flow" }))
		setTimeout(() => {
			navigate(RoutePath.AgentList)
		}, 1000)
	}, [organizationCode])

	useEffect(() => {
		initFlowData()
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [agent])

	return {
		currentFlow,
		setCurrentFlow,
		initDraftList,
		initPublishList,
	}
}

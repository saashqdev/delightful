/**
 * Extra operations for switching drafts
 */

import { getCurrentDateTimeString } from "@/opensource/pages/flow/utils/helpers"
import type { FlowDraft } from "@/types/flow"
import type { DelightfulFlow } from "@bedelightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { useMemoizedFn } from "ahooks"
import { FlowApi } from "@/apis"

type UseDraftSwitchExtraProps = {
	flow?: DelightfulFlow.Flow
	initDraftList: (this: any, flowCode: any) => Promise<FlowDraft.Detail[]>
}

export default function useDraftSwitchExtra({ flow, initDraftList }: UseDraftSwitchExtraProps) {
	const saveDraft = useMemoizedFn(async (draftDetail: FlowDraft.Detail) => {
		// const oldFlowUpdatedAt = flow?.updated_at
		// const newDraftUpdatedAt = draftDetail?.updated_at

		// const isRollback = compareTimes(newDraftUpdatedAt, oldFlowUpdatedAt)

		// // Only rollback requires new addition
		// if(!isRollback) return

		const requestParams = {
			name: `${flow?.name}_Draft_${getCurrentDateTimeString()}`,
			description: "",
			delightful_flow: {
				...draftDetail.delightful_flow,
				// @ts-ignore
				global_variable: flow?.global_variable,
			},
		}

		if (!flow?.id) return

		await FlowApi.saveFlowDraft(requestParams, flow.id)

		initDraftList?.(flow.id)
	})

	return {
		saveDraft,
	}
}

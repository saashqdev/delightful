/**
 * Knowledge data related state and behaviors
 */

import type { Knowledge } from "@/types/knowledge"
import type { FormInstance } from "antd"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"
import { set } from "lodash-es"
import { getDefaultKnowledge } from "../helpers"
import { useCommercial } from "@/opensource/pages/flow/context/CommercialContext"

type UseKnowledgeProps = {
	form: FormInstance<any>
	onValuesChange: (values: any) => void // Callback triggered on value change
}
export default function useKnowledge({ form, onValuesChange }: UseKnowledgeProps) {
	const { currentNode } = useCurrentNode()
	const extraData = useCommercial()

	const knowledgeValueChangeHandler = useMemoizedFn(() => {
		const latestValue = form.getFieldsValue()
		if (latestValue?.knowledge_config?.knowledge_list) {
			latestValue.knowledge_config.knowledge_list = (
				latestValue.knowledge_config.knowledge_list as Knowledge.KnowledgeDatabaseItem[]
			).filter((v) => !!v)
		}
		const oldKnowledgeConfig = currentNode?.params?.knowledge_config
		set(currentNode!, ["params", "knowledge_config"], {
			...oldKnowledgeConfig,
			...latestValue.knowledge_config,
		})
	})

	const handleAdd = useMemoizedFn(() => {
		const newKnowledge = getDefaultKnowledge(!!extraData) // Get default knowledge item
		const currentKnowledgeConfig = form.getFieldValue("knowledge_config")
		const newKnowledgeConfig = {
			knowledge_config: {
				...currentKnowledgeConfig,
				knowledge_list: [...(currentKnowledgeConfig?.knowledge_list || []), newKnowledge],
			},
		}
		form.setFieldsValue(newKnowledgeConfig)
		// Manually trigger onValuesChange
		onValuesChange(newKnowledgeConfig)
	})

	return {
		knowledgeValueChangeHandler,
		handleAdd,
	}
}

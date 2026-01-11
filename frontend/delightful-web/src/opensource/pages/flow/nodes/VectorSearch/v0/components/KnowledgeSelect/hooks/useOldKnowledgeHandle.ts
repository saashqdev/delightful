import { useFlowStore } from "@/opensource/stores/flow"
import { useMemoizedFn } from "ahooks"
import { cloneDeep, castArray, set } from "lodash-es"
import { ComponentTypes } from "@/types/flow"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { defaultExpressionValue } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/constant"
import type { Knowledge } from "@/types/knowledge"
import { LabelTypeMap } from "@delightful/delightful-flow/dist/DelightfulExpressionWidget/types"
import { genDefaultComponent, generateSnowFlake } from "@/opensource/pages/flow/utils/helpers"
import { useTranslation } from "react-i18next"

/**
 * Compatibility handling for old knowledge base fields
 */
export default function useOldKnowledgeHandle() {
	const { t } = useTranslation()
	const { currentNode } = useCurrentNode()
	// Get all available knowledge bases
	const { useableDatabases } = useFlowStore()

	const findTargetDatabaseById = useMemoizedFn((id: string) => {
		return useableDatabases?.find?.((database) => {
			return database.id === id
		}) as Knowledge.KnowledgeItem
	})

	// Compatibility method for old data handling
	const handleOldKnowledge = useMemoizedFn(
		(params: Record<string, any>, oldKey = "knowledge_code", newKey = "vector_database_id") => {
			const cloneParams = cloneDeep(params)
			const oldValue = castArray(cloneParams?.[oldKey]) || []
			const newValue = cloneParams[newKey] || []
			// If new knowledge base list is empty and old knowledge base list is not empty, use old list value as new list value
			if (newValue?.length === 0 && oldValue?.length > 0) {
				const newKnowledgeResult = genDefaultComponent(ComponentTypes.Value, {
					...defaultExpressionValue,
					const_value: [
						{
							// @ts-ignore
							type: LabelTypeMap.LabelNames,
							uniqueId: generateSnowFlake(),
							value: "",
							names_value: [],
						},
					],
				})
				oldValue.forEach((databaseId: string) => {
					if (typeof databaseId === "string") {
						const targetDatabase = findTargetDatabaseById(databaseId)
						newKnowledgeResult?.structure?.const_value?.[0]?.names_value?.push?.({
							id: targetDatabase?.id,
							name:
								targetDatabase?.name ||
								t("common.invalidKnowledgeDatabase", { ns: "flow" }),
						})
					}
					return databaseId
				})
				if (currentNode) set(currentNode, ["params", newKey], newKnowledgeResult)
				cloneParams[newKey] = newKnowledgeResult
			}
			return cloneParams
		},
	)

	return {
		handleOldKnowledge,
	}
}






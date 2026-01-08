import { useEffect } from "react"
import { message, Flex } from "antd"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import type { TemporaryKnowledgeConfig } from "@/opensource/pages/vectorKnowledge/types"
import { useFormConfig } from "./useFormConfig"
import { KnowledgeApi } from "@/apis"
import { useDocumentData } from "./useDocumentData"
import { useSegmentPreview } from "./useSegmentPreview"
import { useEmbeddingModels } from "./useEmbeddingModels"
import { getFileIconByExt } from "../../../constant"
import { isFragmentConfigChanged } from "./formUtils"

/**
 * Knowledge base creation/document configuration logic
 */
export function useConfigurationLogic(
	navigateToDocumentList: (knowledgeBaseCode: string) => void,
	knowledgeBase?: TemporaryKnowledgeConfig,
	saveKnowledgeConfig?: (data: TemporaryKnowledgeConfig) => Promise<void>,
	documentConfig?: {
		knowledgeBaseCode: string
		documentCode: string
	},
) {
	const { t } = useTranslation("flow")

	// Get form configuration logic
	const {
		form,
		segmentMode,
		parentBlockType,
		handleSegmentModeChange,
		handleParentBlockTypeChange,
		handleSegmentSettingReset,
		processFormData,
		initialValues,
	} = useFormConfig()

	// Get document data logic
	const {
		documentList,
		currentDocumentDetail,
		saveDocumentConfig,
		initDocumentData,
		isOldVersion,
	} = useDocumentData(form, documentConfig)

	// Get segment preview logic
	const {
		segmentDocument,
		segmentPreviewResult,
		segmentPreviewLoading,
		handleSegmentPreview,
		handleDocumentChange,
		getFragmentList,
	} = useSegmentPreview(form, currentDocumentDetail)

	// Get embedding model logic
	const { embeddingModelOptions } = useEmbeddingModels()

	// Segment preview document options
	const segmentDocumentOptions = documentList.map((item) => ({
		label: (
			<Flex align="center" gap={8}>
				{getFileIconByExt(item.name.split(".").pop() || "", 14)}
				<div>{item.name}</div>
			</Flex>
		),
		value: item.key || item.code,
	}))

	// Save configuration
	const handleSaveConfiguration = useMemoizedFn(async () => {
		try {
			const processedFormData = await processFormData()
			if (!processedFormData) return

			// Business scenario 1: Create knowledge base
			if (knowledgeBase) {
				const submitData = {
					...knowledgeBase,
					...processedFormData,
				}
				// Save knowledge base configuration
				await saveKnowledgeConfig?.(submitData)
			} else if (currentDocumentDetail) {
				// Business scenario 2: Update knowledge base document segment settings
				// Check if fragment_config segment settings have changed, if not, skip save request and re-vectorization
				const hasChanged = isFragmentConfigChanged(
					currentDocumentDetail.fragment_config,
					processedFormData.fragment_config,
				)
				if (hasChanged) {
					// Save knowledge base document configuration
					await saveDocumentConfig(
						currentDocumentDetail,
						processedFormData.fragment_config,
					)
					// Re-vectorize document
					await KnowledgeApi.revectorizeDocument({
						knowledgeBaseCode: currentDocumentDetail.knowledge_base_code,
						documentCode: currentDocumentDetail.code,
					})
				}
				// Navigate to document list
				navigateToDocumentList(currentDocumentDetail.knowledge_base_code)
			}
		} catch (error) {
			console.error("Failed to save configuration:", error)
			message.error(t("knowledgeDatabase.saveConfigFailed"))
		}
	})

	// Handle segment preview button click
	const handleSegmentPreviewClick = useMemoizedFn(() => {
		handleSegmentPreview(documentList)()
	})

	// Initialize data
	useEffect(() => {
		initDocumentData(knowledgeBase)
	}, [])

	return {
		form,
		currentDocumentDetail,
		isOldVersion,
		segmentMode,
		parentBlockType,
		embeddingModelOptions,
		segmentDocument,
		setSegmentDocument: handleDocumentChange(documentList),
		segmentDocumentOptions,
		segmentPreviewResult,
		segmentPreviewLoading,
		getFragmentList,
		handleSaveConfiguration,
		handleSegmentModeChange,
		handleParentBlockTypeChange,
		handleSegmentSettingReset,
		handleSegmentPreviewClick,
		initialValues,
	}
}

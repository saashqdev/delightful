import { useMemo, useState } from "react"
import { useMemoizedFn } from "ahooks"
import { message } from "antd"
import { KnowledgeApi } from "@/apis"
import { Knowledge } from "@/types/knowledge"
import { TextPreprocessingRules } from "../../../constant"
import type { FormInstance } from "antd"
import type { ConfigFormValues, FragmentConfig, TemporaryKnowledgeConfig } from "../../../types"
import { useTranslation } from "react-i18next"

export function useDocumentData(
	form: FormInstance<ConfigFormValues>,
	documentConfig?: {
		knowledgeBaseCode: string
		documentCode: string
	},
) {
	const { t } = useTranslation("flow")

	// Current configured document details
	const [currentDocumentDetail, setCurrentDocumentDetail] =
		useState<Knowledge.EmbedDocumentDetail>()

	// Document list for segment preview
	const [documentList, setDocumentList] = useState<
		{
			name: string
			key: string
			code: string
		}[]
	>([])

	// Get document information based on document code
	const fetchDocumentDetails = useMemoizedFn(
		async (knowledgeBaseCode: string, documentCode: string) => {
			try {
				const res = await KnowledgeApi.getKnowledgeDocumentDetail({
					knowledge_code: knowledgeBaseCode,
					document_code: documentCode,
				})
				if (res) {
					setDocumentList([
						{
							name: res.name,
							key: res.document_file?.key || "",
							code: res.code,
						},
					])
					setCurrentDocumentDetail(res)
					form.setFieldValue("fragment_config", res.fragment_config)
					if (res.fragment_config.normal) {
						form.setFieldValue(
							["fragment_config", "normal", "replace_spaces"],
							res.fragment_config.normal.text_preprocess_rule.includes(
								TextPreprocessingRules.ReplaceSpaces,
							),
						)
						form.setFieldValue(
							["fragment_config", "normal", "remove_urls"],
							res.fragment_config.normal.text_preprocess_rule.includes(
								TextPreprocessingRules.RemoveUrls,
							),
						)
					}
					if (res.fragment_config.parent_child) {
						form.setFieldValue(
							["fragment_config", "parent_child", "replace_spaces"],
							res.fragment_config.parent_child.text_preprocess_rule.includes(
								TextPreprocessingRules.ReplaceSpaces,
							),
						)
						form.setFieldValue(
							["fragment_config", "parent_child", "remove_urls"],
							res.fragment_config.parent_child.text_preprocess_rule.includes(
								TextPreprocessingRules.RemoveUrls,
							),
						)
					}
					form.setFieldValue("embedding_config", res.embedding_config)
					form.setFieldValue("retrieve_config", res.retrieve_config)
				}
			} catch (error) {
				console.error("Failed to fetch document information:", error)
			}
		},
	)

	// Save document configuration
	const updateDocumentConfig = useMemoizedFn(
		async (documentConfig: Knowledge.EmbedDocumentDetail, fragmentConfig: FragmentConfig) => {
			try {
				const res = await KnowledgeApi.updateKnowledgeDocument({
					knowledge_code: documentConfig.knowledge_base_code,
					document_code: documentConfig.code,
					name: documentConfig.name,
					enabled: documentConfig.enabled,
					fragment_config: fragmentConfig,
				})
				if (res) {
					message.success(t("knowledgeDatabase.savedSuccess"))
				}
			} catch (error) {
				console.error("Failed to save document configuration:", error)
			}
		},
	)

	// Initialize document data
	const initDocumentData = useMemoizedFn((knowledgeBase?: TemporaryKnowledgeConfig) => {
		if (documentConfig) {
			fetchDocumentDetails(documentConfig.knowledgeBaseCode, documentConfig.documentCode)
		} else if (knowledgeBase) {
			setDocumentList(knowledgeBase.document_files)
		}
	})

	// Since the backend did not persist document information in older knowledge base versions, compatibility handling is needed:
	// Determine if it's an old version knowledge base document by checking if document_file field is null
	// Old version documents do not support re-editing segment settings, can only view segment list based on original settings
	// (Note: The API for viewing segment list is different from the segment preview API when creating knowledge base)
	const isOldVersion = useMemo(() => {
		return Boolean(currentDocumentDetail && !currentDocumentDetail.document_file)
	}, [currentDocumentDetail])

	return {
		documentList,
		setDocumentList,
		currentDocumentDetail,
		isOldVersion,
		getDocumentDetails: fetchDocumentDetails,
		saveDocumentConfig: updateDocumentConfig,
		initDocumentData,
	}
}

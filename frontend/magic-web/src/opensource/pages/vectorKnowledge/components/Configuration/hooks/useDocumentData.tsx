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

	// 当前配置的文档详情
	const [currentDocumentDetail, setCurrentDocumentDetail] =
		useState<Knowledge.EmbedDocumentDetail>()

	// 供分段预览的文档列表
	const [documentList, setDocumentList] = useState<
		{
			name: string
			key: string
			code: string
		}[]
	>([])

	// 基于文档编码获取文档信息
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
				console.error("获取文档信息失败:", error)
			}
		},
	)

	// 保存文档配置
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
				console.error("保存文档配置失败:", error)
			}
		},
	)

	// 初始化文档数据
	const initDocumentData = useMemoizedFn((knowledgeBase?: TemporaryKnowledgeConfig) => {
		if (documentConfig) {
			fetchDocumentDetails(documentConfig.knowledgeBaseCode, documentConfig.documentCode)
		} else if (knowledgeBase) {
			setDocumentList(knowledgeBase.document_files)
		}
	})

	// 由于后端在旧版本的知识库未对文档信息进行持久化，故需要做旧版本的兼容处理：
	// 根据文档的 document_file 字段是否为null，来判断是否为旧版本知识库的文档
	// 旧版本的文档不支持重新编辑分段设置，只能基于原先的分段设置查看分段列表
	// （注：查看分段列表的接口 与创建知识库时的 分段预览接口 不是同一个）
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

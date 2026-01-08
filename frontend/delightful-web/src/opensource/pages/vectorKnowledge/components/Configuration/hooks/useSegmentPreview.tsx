import { useEffect, useState } from "react"
import { message, FormInstance } from "antd"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import { KnowledgeApi } from "@/apis"
import { TextPreprocessingRules, SegmentationMode } from "../../../constant"
import type { ConfigFormValues, SegmentPreviewType, FragmentConfig } from "../../../types"
import { Knowledge } from "@/types/knowledge"

export function useSegmentPreview(
	form: FormInstance<ConfigFormValues>,
	currentDocumentDetail?: Knowledge.EmbedDocumentDetail,
) {
	const { t } = useTranslation("flow")

	// Segment preview related state
	const [segmentDocument, setSegmentDocument] = useState<string>()
	const [segmentPreviewResult, setSegmentPreviewResult] = useState<SegmentPreviewType>({
		total: 0,
		list: [],
		page: 1,
	})
	const [segmentPreviewLoading, setSegmentPreviewLoading] = useState(false)

	// Get segment preview
	const fetchSegmentPreview = useMemoizedFn(async (document: { name: string; key: string }) => {
		const { fragment_config } = await form.validateFields(["fragment_config"], {
			recursive: true,
		})
		setSegmentPreviewLoading(true)
		try {
			// Convert boolean values to array
			const apiFragmentConfig = {
				...fragment_config,
				normal:
					fragment_config.mode === SegmentationMode.General
						? {
								...fragment_config.normal,
								text_preprocess_rule: [
									...(fragment_config.normal.replace_spaces
										? [TextPreprocessingRules.ReplaceSpaces]
										: []),
									...(fragment_config.normal.remove_urls
										? [TextPreprocessingRules.RemoveUrls]
										: []),
								],
								// Remove boolean properties
								replace_spaces: undefined,
								remove_urls: undefined,
						  }
						: undefined,
				parent_child:
					fragment_config.mode === SegmentationMode.ParentChild
						? {
								...fragment_config.parent_child,
								text_preprocess_rule: [
									...(fragment_config.parent_child.replace_spaces
										? [TextPreprocessingRules.ReplaceSpaces]
										: []),
									...(fragment_config.parent_child.remove_urls
										? [TextPreprocessingRules.RemoveUrls]
										: []),
								],
// Remove boolean properties
								replace_spaces: undefined,
								remove_urls: undefined,
						  }
						: undefined,
			}

			const res = await KnowledgeApi.segmentPreview({
				fragment_config: apiFragmentConfig as FragmentConfig,
				document_file: document,
			})
			if (res) {
				setSegmentPreviewResult({
					total: res.total,
					list: res.list,
					page: 1,
				})
			}
			setSegmentPreviewLoading(false)
		} catch (error) {
			message.error(t("knowledgeDatabase.segmentPreviewFailed"))
			setSegmentPreviewLoading(false)
		}
	})

	// Click segment preview button
	const handlePreviewButtonClick = useMemoizedFn(
		(documentList: { name: string; key: string }[]) => async () => {
			// Reset segment preview result
			setSegmentPreviewResult({
				total: 0,
				list: [],
				page: 1,
			})
			if (!segmentDocument && documentList.length > 0) {
				setSegmentDocument(documentList[0].key)
			}

			if (documentList.length > 0) {
				fetchSegmentPreview({
					name: segmentDocument
						? documentList.find((item) => item.key === segmentDocument)?.name ?? ""
						: documentList[0].name,
					key: segmentDocument
						? documentList.find((item) => item.key === segmentDocument)?.key ?? ""
						: documentList[0].key,
				})
			}
		},
	)

	// Trigger preview when selected document changes
	const handleDocumentSelection = useMemoizedFn(
		(documentList: { name: string; key: string }[]) => {
			return (docKey: string) => {
				setSegmentDocument(docKey)
				if (docKey) {
					const doc = documentList.find((item) => item.key === docKey)
					if (doc) {
						fetchSegmentPreview(doc)
					}
				}
			}
		},
	)

	// View segment list of vectorized documents
	const fetchFragmentList = useMemoizedFn(
		async ({
			knowledgeBaseCode,
			documentCode,
			page,
			pageSize,
		}: {
			knowledgeBaseCode: string
			documentCode: string
			page: number
			pageSize: number
		}) => {
			try {
				if (segmentPreviewLoading) return
				setSegmentPreviewLoading(true)
				const res = await KnowledgeApi.getFragmentList({
					knowledgeBaseCode,
					documentCode,
					page,
					pageSize,
				})
				// Handle pagination
				if (res && res.page === 1) {
					setSegmentPreviewResult({
						total: res.total,
						list: res.list,
						page: 1,
					})
				} else if (res && res.page - 1 === segmentPreviewResult.page) {
					const newList = [...segmentPreviewResult.list, ...res.list]
					setSegmentPreviewResult({
						total: res.total,
						list: newList,
						page: res.page,
					})
				}
				setSegmentPreviewLoading(false)
			} catch (error) {
				message.error(t("knowledgeDatabase.getFragmentListFailed"))
				setSegmentPreviewLoading(false)
			}
		},
	)

	useEffect(() => {
		// For single document configuration scenario, directly set it as preview document
		if (currentDocumentDetail) {
			setSegmentDocument(
				currentDocumentDetail.document_file
					? currentDocumentDetail.document_file.key
					: currentDocumentDetail.code,
			)
		}
	}, [currentDocumentDetail])

	return {
		segmentDocument,
		segmentPreviewResult,
		segmentPreviewLoading,
		handleSegmentPreview: handlePreviewButtonClick,
		handleDocumentChange: handleDocumentSelection,
		setSegmentDocument,
		getFragmentList: fetchFragmentList,
	}
}

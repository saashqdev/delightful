import { useTranslation } from "react-i18next"
import { Flex, Select, Spin, Empty } from "antd"
import { IconLayoutList } from "@tabler/icons-react"
import { useVectorKnowledgeConfigurationStyles } from "../styles"
import { LoadingOutlined } from "@ant-design/icons"
import type { Knowledge } from "@/types/knowledge"
import { useRef, useEffect } from "react"

interface SegmentPreviewProps {
	segmentPreviewLoading: boolean
	segmentDocument?: string
	setSegmentDocument: (value: string) => void
	segmentDocumentOptions: { label: React.ReactNode; value: string }[]
	segmentPreviewResult: {
		total: number
		list: Knowledge.FragmentItem[]
		page: number
	}
	isOldVersion: boolean
	getFragmentList: (params: { page: number }) => void
}

/**
 * Segment Preview Component
 */
export default function SegmentPreview({
	segmentPreviewLoading,
	segmentDocument,
	setSegmentDocument,
	segmentDocumentOptions,
	segmentPreviewResult,
	isOldVersion,
	getFragmentList,
}: SegmentPreviewProps) {
	const { styles } = useVectorKnowledgeConfigurationStyles()
	const { t } = useTranslation("flow")
	const contentRef = useRef<HTMLDivElement>(null)

	// Scroll to load more
	useEffect(() => {
		if (!isOldVersion || !contentRef.current) return

		const handleScroll = () => {
			const container = contentRef.current
			if (!container) return

			// Check loading status
			if (segmentPreviewLoading) return

			const { scrollTop, scrollHeight, clientHeight } = container
			// Trigger load more when scrolling to 100px from bottom
			// Ensure current data total is less than total before making request
			if (
				scrollHeight - scrollTop - clientHeight < 100 &&
				segmentPreviewResult.list.length < segmentPreviewResult.total
			) {
				// Load next page data
				getFragmentList({ page: segmentPreviewResult.page + 1 })
			}
		}

		const contentElement = contentRef.current
		contentElement.addEventListener("scroll", handleScroll)

		return () => {
			contentElement.removeEventListener("scroll", handleScroll)
		}
	}, [isOldVersion, segmentPreviewResult, getFragmentList, segmentPreviewLoading])

	return (
		<>
			<Flex className={styles.previewHeader} align="center" justify="space-between">
				<Flex align="center" gap={4}>
					<IconLayoutList size={16} color="currentColor" />
					<div>{t("knowledgeDatabase.segmentPreview")}</div>
				</Flex>
				<Flex align="center" gap={8}>
					<Select
						className={styles.documentSelect}
						size="small"
						disabled={segmentPreviewLoading}
						value={segmentDocument}
						onChange={setSegmentDocument}
						placeholder={t("knowledgeDatabase.selectDocument")}
						options={segmentDocumentOptions}
						popupMatchSelectWidth={false}
						dropdownStyle={{ minWidth: "max-content" }}
					/>
					<Flex gap={4} className={styles.estimatedSegments}>
						<div>{segmentPreviewResult.total}</div>
						<div>{t("knowledgeDatabase.estimatedSegments")}</div>
					</Flex>
				</Flex>
			</Flex>

			{segmentPreviewLoading && segmentPreviewResult.list.length === 0 ? (
				<Flex vertical justify="center" align="center" className={styles.previewLoading}>
					<Spin indicator={<LoadingOutlined spin />} size="large" />
					<div className={styles.previewLoadingText}>
						{t("knowledgeDatabase.segmentPreviewLoading")}
					</div>
				</Flex>
			) : segmentPreviewResult.list.length > 0 ? (
				<div className={styles.previewContent} ref={contentRef}>
					{segmentPreviewResult.list.map((item, index) => (
						<div className={styles.segmentItem} key={item.id || `segment-${index}`}>
							<Flex align="center" gap={6} className={styles.segmentItemTitle}>
								<IconLayoutList size={16} color="currentColor" />
								<div>{t("knowledgeDatabase.segment")}</div>
								<div>{index + 1}</div>
								<div>/</div>
								<div>
									{t("knowledgeDatabase.segmentWordCount", {
										num: item.word_count || "*",
									})}
								</div>
							</Flex>
							<div className={styles.segmentItemContent}>{item.content}</div>
						</div>
					))}

					{/* Bottom loading status */}
					{segmentPreviewLoading && (
						<div className={styles.loadingMore}>
							<Spin size="small" />
							<span>{t("knowledgeDatabase.loadingMore")}</span>
						</div>
					)}
				</div>
			) : (
				<Flex vertical justify="center" align="center" className={styles.previewLoading}>
					<Empty description={t("knowledgeDatabase.noResults")} />
				</Flex>
			)}
		</>
	)
}

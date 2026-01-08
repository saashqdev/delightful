import { Form, Button, Flex } from "antd"
import { useVectorKnowledgeConfigurationStyles } from "./styles"
import { useConfigurationLogic } from "./hooks/useConfigurationLogic"
import SegmentSettingsSection from "./components/SegmentSettingsSection"
import EmbeddingModelSection from "./components/EmbeddingModelSection"
import SearchSettingsGroup from "./components/SearchSettingsGroup"
import SegmentPreview from "./components/SegmentPreview"
import type { TemporaryKnowledgeConfig } from "../../types"
import { useTranslation } from "react-i18next"
import { useNavigate } from "react-router-dom"
import { RoutePath } from "@/const/routes"
import { useMemoizedFn } from "ahooks"
import { useEffect } from "react"

interface Props {
	documentConfig?: {
		knowledgeBaseCode: string
		documentCode: string
	}
	knowledgeBase?: TemporaryKnowledgeConfig
	onBack: () => void
	saveKnowledgeConfig: (data: TemporaryKnowledgeConfig) => Promise<void>
}

/**
 * Vector knowledge base configuration component, has two business scenarios:
 * 1. When creating knowledge base, used to configure knowledge base segmentation settings, embedding model, retrieval settings;
 * 2. After knowledge base creation succeeds, used to re-edit document-level segmentation settings.
 */
export default function VectorKnowledgeConfiguration({
	documentConfig,
	knowledgeBase,
	onBack,
	saveKnowledgeConfig,
}: Props) {
	const { styles } = useVectorKnowledgeConfigurationStyles()

	const { t } = useTranslation("flow")

	const navigate = useNavigate()

	/** Navigate to document list */
	const navigateToDocumentList = useMemoizedFn((knowledgeBaseCode: string) => {
		navigate(`${RoutePath.VectorKnowledgeDetail}?code=${knowledgeBaseCode}`)
	})

	const {
		form,
		currentDocumentDetail,
		isOldVersion,
		segmentMode,
		parentBlockType,
		embeddingModelOptions,
		segmentDocument,
		setSegmentDocument,
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
	} = useConfigurationLogic(
		navigateToDocumentList,
		knowledgeBase,
		saveKnowledgeConfig,
		documentConfig,
	)

	// Number of items per page for segment list
	const PAGE_SIZE = 20

	// Adapt parameter interface
	const handleGetFragmentList = useMemoizedFn(({ page }: { page: number }) => {
		if (currentDocumentDetail) {
			return getFragmentList({
				knowledgeBaseCode: currentDocumentDetail.knowledge_base_code,
				documentCode: currentDocumentDetail.code,
				page,
				pageSize: PAGE_SIZE,
			})
		}
	})

	// Initialize segment list
	useEffect(() => {
		handleGetFragmentList({ page: 1 })
	}, [currentDocumentDetail])

	return (
		<Flex className={styles.wrapper}>
			{/* Left side - Knowledge base configuration */}
			<Flex vertical justify="space-between" className={styles.leftWrapper}>
				<div className={styles.container}>
					<div className={styles.title}>
						{currentDocumentDetail
							? currentDocumentDetail.name
							: t("knowledgeDatabase.createVectorKnowledge")}
						{isOldVersion && (
							<div className={styles.oldVersionTip}>
								{t("knowledgeDatabase.oldVersionTip")}
							</div>
						)}
					</div>
					<div className={styles.content}>
						<Form form={form} layout="vertical" initialValues={initialValues}>
							{/* Segment settings */}
							<SegmentSettingsSection
								disabled={isOldVersion}
								segmentMode={segmentMode}
								parentBlockType={parentBlockType}
								segmentPreviewLoading={segmentPreviewLoading}
								handleSegmentModeChange={handleSegmentModeChange}
								handleParentBlockTypeChange={handleParentBlockTypeChange}
								handleSegmentPreview={handleSegmentPreviewClick}
								handleSegmentSettingReset={handleSegmentSettingReset}
							/>

							{/* Embedding model */}
							<EmbeddingModelSection
								isDocumentConfig={!!documentConfig}
								embeddingModelOptions={embeddingModelOptions}
							/>
							{/* Search settings */}
							<div className={styles.configSection}>
								<div className={styles.configTitle} style={{ marginBottom: 4 }}>
									{t("knowledgeDatabase.searchSettings")}
								</div>
								<div className={styles.configDesc}>
									{t("knowledgeDatabase.searchSettingsDesc")}
								</div>

								{/* Search settings component group */}
								<SearchSettingsGroup isDocumentConfig={!!documentConfig} />
							</div>
						</Form>
					</div>
				</div>

				<Flex className={styles.footer} justify="flex-end" align="center" gap={10}>
					<Button className={styles.backButton} onClick={onBack}>
						{t("knowledgeDatabase.previousStep")}
					</Button>
					{!isOldVersion && (
						<Button type="primary" onClick={handleSaveConfiguration}>
							{t("knowledgeDatabase.saveAndProcess")}
						</Button>
					)}
				</Flex>
			</Flex>

			{/* Right side - Segment preview */}
			<Flex vertical className={styles.rightWrapper}>
				<SegmentPreview
					segmentPreviewLoading={segmentPreviewLoading}
					segmentDocument={segmentDocument}
					setSegmentDocument={setSegmentDocument}
					segmentDocumentOptions={segmentDocumentOptions}
					segmentPreviewResult={segmentPreviewResult}
					isOldVersion={isOldVersion}
					getFragmentList={handleGetFragmentList}
				/>
			</Flex>
		</Flex>
	)
}

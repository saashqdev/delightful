import { Divider, Flex } from "antd"
import { useTranslation } from "react-i18next"
import {
	SegmentationMode,
	ParentBlockMode,
	TextPreprocessingRules,
	RetrievalMethod,
} from "../../../constant"
import { useVectorKnowledgeEmbedStyles } from "../styles"
import type { FragmentConfig, EmbeddingModelConfig, RetrieveConfig } from "../../../types"
import { useCallback, useMemo } from "react"
import { useEmbeddingModels } from "../../Configuration/hooks/useEmbeddingModels"
import { isNil } from "lodash-es"

interface KnowledgeConfigProps {
	knowledgeConfig: {
		fragmentConfig: FragmentConfig
		embeddingConfig: EmbeddingModelConfig
		retrieveConfig: RetrieveConfig
	}
}

export default function KnowledgeConfigSection({ knowledgeConfig }: KnowledgeConfigProps) {
	const { styles } = useVectorKnowledgeEmbedStyles()
	const { t } = useTranslation("flow")

	// Preprocessing rules mapping table
	const rulesMap = useMemo(
		() => ({
			[TextPreprocessingRules.ReplaceSpaces]: t(
				"knowledgeDatabase.segmentPreprocessReplaceRule",
			),
			[TextPreprocessingRules.RemoveUrls]: t("knowledgeDatabase.segmentPreprocessDeleteRule"),
		}),
		[t],
	)

	// Retrieval method mapping table
	const methodMap = useMemo(
		() => ({
			[RetrievalMethod.SemanticSearch]: t("knowledgeDatabase.vectorSearch"),
			[RetrievalMethod.FullTextSearch]: t("knowledgeDatabase.fullTextSearch"),
			[RetrievalMethod.HybridSearch]: t("knowledgeDatabase.hybridSearch"),
			[RetrievalMethod.GraphSearch]: t("knowledgeDatabase.graphSearch"),
		}),
		[t],
	)

	// Convert preprocessing rule IDs to readable text
	const getTextPreprocessingRulesText = useCallback(
		(rules: TextPreprocessingRules[] = []) => {
			if (!rules || rules.length === 0) return "-"
			return rules
				.map((rule) => rulesMap[rule] || "")
				.filter(Boolean)
				.join("、")
		},
		[rulesMap],
	)

	// Convert retrieval method enum values to readable text
	const getRetrievalMethodText = useCallback(
		(method: RetrievalMethod | undefined) => {
			if (!method) return "-"
			return methodMap[method] || "-"
		},
		[methodMap],
	)

	const { fragmentConfig, embeddingConfig, retrieveConfig } = knowledgeConfig

	// Get segment mode
	const segmentMode =
		fragmentConfig.mode === SegmentationMode.General
			? t("knowledgeDatabase.generalPattern")
			: t("knowledgeDatabase.parentChildSegment")

	const { embeddingModels } = useEmbeddingModels()

	const targetEmbeddingModel = useMemo(() => {
		return (
			embeddingModels.find((item) => item.id === embeddingConfig?.model_id) || {
				name: embeddingConfig?.model_id,
				icon: "",
				provider: "",
			}
		)
	}, [embeddingModels, embeddingConfig?.model_id])

	const getConfigValue = (value: any) => {
		if (isNil(value)) return "-"
		return value
	}

	return (
		<div className={styles.configSection}>
			<Divider className={styles.divider} />
			<div>
				{/* Segment mode */}
				<div className={styles.configItem}>
					<div className={styles.configLabel}>{t("knowledgeDatabase.segmentMode")}</div>
					<div className={styles.configValue}>{segmentMode}</div>
				</div>

				{/* Configuration items for general mode */}
				{fragmentConfig.mode === SegmentationMode.General && (
					<>
						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.segmentMaxSize")}
							</div>
							<div className={styles.configValue}>
								{getConfigValue(fragmentConfig.normal?.segment_rule?.chunk_size)}{" "}
								Tokens
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.segmentOverlapSize")}
							</div>
							<div className={styles.configValue}>
								{getConfigValue(fragmentConfig.normal?.segment_rule?.chunk_overlap)}{" "}
								Tokens
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.segmentDelimiter")}
							</div>
							<div className={styles.configValue}>
								{getConfigValue(fragmentConfig.normal?.segment_rule?.separator)}
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.segmentPreprocessRules")}
							</div>
							<div className={styles.configValue}>
								{getTextPreprocessingRulesText(
									fragmentConfig.normal?.text_preprocess_rule,
								)}
							</div>
						</div>
					</>
				)}

				{/* Configuration items for parent-child segmentation mode */}
				{fragmentConfig.mode === SegmentationMode.ParentChild && (
					<>
						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.parentContext")}
							</div>
							<div className={styles.configValue}>
								{fragmentConfig.parent_child?.parent_mode ===
								ParentBlockMode.Paragraph
									? t("knowledgeDatabase.paragraph")
									: t("knowledgeDatabase.fullText")}
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.parentSegmentDelimiter")}
							</div>
							<div className={styles.configValue}>
								{fragmentConfig.parent_child?.parent_segment_rule?.separator}
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.parentSegmentMaxSize")}
							</div>
							<div className={styles.configValue}>
								{getConfigValue(
									fragmentConfig.parent_child?.parent_segment_rule?.chunk_size,
								)}{" "}
								Tokens
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.childSegmentDelimiter")}
							</div>
							<div className={styles.configValue}>
								{fragmentConfig.parent_child?.child_segment_rule?.separator}
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.childSegmentMaxSize")}
							</div>
							<div className={styles.configValue}>
								{getConfigValue(
									fragmentConfig.parent_child?.child_segment_rule?.chunk_size,
								)}{" "}
								Tokens
							</div>
						</div>

						<div className={styles.configItem}>
							<div className={styles.configLabel}>
								{t("knowledgeDatabase.segmentPreprocessRules")}
							</div>
							<div className={styles.configValue}>
								{getTextPreprocessingRulesText(
									fragmentConfig.parent_child?.text_preprocess_rule,
								)}
							</div>
						</div>
					</>
				)}

				{/* Embedding model */}
				<div className={styles.configItem}>
					<div className={styles.configLabel}>
						{t("knowledgeDatabase.embeddingModel")}
					</div>
					<Flex align="center" gap={4} className={styles.configValue}>
						<img width={16} height={16} src={targetEmbeddingModel.icon} alt="" />
						{targetEmbeddingModel.name}
						<div className={styles.modelProvider}>{targetEmbeddingModel.provider}</div>
					</Flex>
				</div>

				{/* Retrieval settings */}
				<div className={styles.configItem}>
					<div className={styles.configLabel}>
						{t("knowledgeDatabase.searchSettings")}
					</div>
					<div className={styles.configValue}>
						{getRetrievalMethodText(retrieveConfig?.search_method)}
					</div>
				</div>

				{retrieveConfig?.score_threshold_enabled && (
					<div className={styles.configItem}>
						<div className={styles.configLabel}>
							{t("knowledgeDatabase.scoreThreshold")}
						</div>
						<div className={styles.configValue}>
							{getConfigValue(retrieveConfig?.score_threshold)}
						</div>
					</div>
				)}

				{retrieveConfig?.reranking_enable && (
					<div className={styles.configItem}>
						<div className={styles.configLabel}>
							{t("knowledgeDatabase.rerankModel")}
						</div>
						<div className={styles.configValue}>
							{getConfigValue(retrieveConfig?.reranking_model?.reranking_model_name)}
						</div>
					</div>
				)}
			</div>
		</div>
	)
}

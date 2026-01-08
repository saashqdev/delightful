import {
	TextPreprocessingRules,
	SegmentationMode,
	ParentBlockMode,
	RetrievalMethod,
} from "../../../constant"
import type { FragmentConfig, ConfigFormValues } from "@/opensource/pages/vectorKnowledge/types"
import { isEqual, cloneDeep } from "lodash-es"

/** Initialize form data */
export const DEFAULT_FORM_VALUES: ConfigFormValues = {
	fragment_config: {
		mode: SegmentationMode.General,
		normal: {
			replace_spaces: true,
			remove_urls: false,
			segment_rule: {
				separator: "\\n\\n",
				chunk_size: 500,
				chunk_overlap: 50,
			},
		},
		parent_child: {
			parent_mode: ParentBlockMode.Paragraph,
			separator: "\\n\\n",
			chunk_size: 500,
			parent_segment_rule: {
				separator: "\\n\\n",
				chunk_size: 500,
			},
			child_segment_rule: {
				separator: "\\n\\n",
				chunk_size: 500,
			},
			replace_spaces: true,
			remove_urls: false,
		},
	},
	embedding_config: {
		model_id: undefined,
	},
	retrieve_config: {
		search_method: RetrievalMethod.SemanticSearch,
		top_k: 3,
		score_threshold: 0.5,
		score_threshold_enabled: false,
		reranking_model: {
			model_id: undefined,
			reranking_model_name: "",
			reranking_provider_name: "",
		},
		reranking_enable: false,
	},
}

/** Convert form boolean values to array format required by API */
export function convertBooleanToPreprocessingRules(
	replaceSpaces: boolean,
	removeUrls: boolean,
): TextPreprocessingRules[] {
	const rules: TextPreprocessingRules[] = []

	if (replaceSpaces) {
		rules.push(TextPreprocessingRules.ReplaceSpaces)
	}

	if (removeUrls) {
		rules.push(TextPreprocessingRules.RemoveUrls)
	}

	return rules
}

/**
 * Compare whether new and old fragment_config have changes
 * @param oldConfig Old fragment_config configuration
 * @param newConfig New fragment_config configuration
 * @returns Whether changes occurred
 */
export function isFragmentConfigChanged(
	oldConfig: FragmentConfig,
	newConfig: FragmentConfig,
): boolean {
	if (!oldConfig || !newConfig) {
		return true // If either config is empty, default to having changes
	}

	// Check if mode is the same
	if (oldConfig.mode !== newConfig.mode) {
		return true
	}

	// Determine which part of config to compare based on mode
	if (newConfig.mode === SegmentationMode.General) {
		// Compare normal part in general mode
		if (!oldConfig.normal || !newConfig.normal) {
			return true
		}

		// Deep copy to avoid modifying original object
		const oldNormal = cloneDeep(oldConfig.normal)
		const newNormal = cloneDeep(newConfig.normal)

		// Sort text_preprocess_rule to ignore order differences
		if (oldNormal.text_preprocess_rule && newNormal.text_preprocess_rule) {
			oldNormal.text_preprocess_rule.sort()
			newNormal.text_preprocess_rule.sort()
		}

		return !isEqual(oldNormal, newNormal)
	} else if (newConfig.mode === SegmentationMode.ParentChild) {
		// Compare parent_child part in parent-child mode
		if (!oldConfig.parent_child || !newConfig.parent_child) {
			return true
		}

		// Deep copy to avoid modifying original object
		const oldParentChild = cloneDeep(oldConfig.parent_child)
		const newParentChild = cloneDeep(newConfig.parent_child)

		// Sort text_preprocess_rule to ignore order differences
		if (oldParentChild.text_preprocess_rule && newParentChild.text_preprocess_rule) {
			oldParentChild.text_preprocess_rule.sort()
			newParentChild.text_preprocess_rule.sort()
		}

		return !isEqual(oldParentChild, newParentChild)
	}

	// Default to having changes
	return true
}

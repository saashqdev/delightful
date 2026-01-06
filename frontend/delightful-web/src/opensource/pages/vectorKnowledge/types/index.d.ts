import {
	SegmentationMode,
	TextPreprocessingRules,
	ParentBlockMode,
	RetrievalMethod,
} from "../constant"
import type { Knowledge } from "@/types/knowledge"

export interface FileData {
	id: string
	name: string
	file: File
	status: "init" | "uploading" | "done" | "error"
	progress: number
	result?: {
		key: string
		name: string
		size: number
	}
	error?: Error
	cancel?: () => void
}

/** Temporarily created knowledge base */
export interface TemporaryKnowledgeConfig {
	name: string
	icon: string
	description: string
	enabled: boolean
	document_files: {
		code: string
		name: string
		key: string
	}[]
	fragmentConfig?: FragmentConfig
	embeddingConfig?: EmbeddingModelConfig
	retrieveConfig?: RetrieveConfig
}

/** Segmentation configuration */
export interface FragmentConfig {
	mode: SegmentationMode
	normal: {
		text_preprocess_rule: TextPreprocessingRules[]
		segment_rule: {
			separator: string
			chunk_size: number
			chunk_overlap: number
		}
	}
	parent_child: {
		parent_mode: ParentBlockMode
		separator: string
		chunk_size: number
		parent_segment_rule: {
			separator: string
			chunk_size: number
		}
		child_segment_rule: {
			separator: string
			chunk_size: number
		}
		text_preprocess_rule: TextPreprocessingRules[]
	}
}

/** Embedding model configuration */
export interface EmbeddingModelConfig {
	model_id: string | undefined
}

/** Retrieval configuration */
export interface RetrieveConfig {
	search_method: RetrievalMethod
	top_k: number
	score_threshold: number
	score_threshold_enabled: boolean
	reranking_model: {
		model_id: string | undefined
		reranking_model_name: string
		reranking_provider_name: string
	}
	reranking_enable: boolean
}

/** Configuration form data type */
export interface ConfigFormValues {
	fragment_config: Omit<FragmentConfig, "normal" | "parent_child"> & {
		normal: Omit<FragmentConfig["normal"], "text_preprocess_rule"> & {
			// Use boolean values instead of text_preprocess_rule array
			replace_spaces: boolean
			remove_urls: boolean
		}
		parent_child: Omit<FragmentConfig["parent_child"], "text_preprocess_rule"> & {
			// Use boolean values instead of text_preprocess_rule array
			replace_spaces: boolean
			remove_urls: boolean
		}
	}
	embedding_config: EmbeddingModelConfig
	retrieve_config: RetrieveConfig
}

/** Segment preview result type */
export interface SegmentPreviewType {
	total: number
	list: Knowledge.FragmentItem[]
	page: number
}

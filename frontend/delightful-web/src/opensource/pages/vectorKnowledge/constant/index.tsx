import {
	IconTextFile,
	IconMarkdownFile,
	IconPDFFile,
	IconExcelFile,
	IconDocxFile,
	IconXMindFile,
	IconOtherFile,
} from "@/enhance/tabler/icons-react"

// Mapping between file types and extensions
export const fileExtensionMap = {
	TXT: "txt",
	MD: "md",
	PDF: "pdf",
	XLSX: "xlsx",
	XLS: "xls",
	DOCX: "docx",
	CSV: "csv",
	XML: "xml",
}

// Mapping between file types and enum values
export const fileTypeMap = {
	UNKNOWN: 0,
	TXT: 1,
	MD: 2,
	PDF: 3,
	XLSX: 5,
	XLS: 6,
	DOCX: 8,
	CSV: 9,
	XML: 10,
}

// File types supported by vector knowledge base embedding
export const supportedFileExtensions = Object.values(fileExtensionMap)

// Get file type icon by extension
export const getFileIconByExt = (extension: string, size = 24) => {
	const map = {
		[fileExtensionMap.TXT]: <IconTextFile size={size} />,
		[fileExtensionMap.MD]: <IconMarkdownFile size={size} />,
		[fileExtensionMap.PDF]: <IconPDFFile size={size} />,
		[fileExtensionMap.XLSX]: <IconExcelFile size={size} />,
		[fileExtensionMap.XLS]: <IconExcelFile size={size} />,
		[fileExtensionMap.DOCX]: <IconDocxFile size={size} />,
		[fileExtensionMap.CSV]: <IconExcelFile size={size} />,
		[fileExtensionMap.XML]: <IconXMindFile size={size} />,
	}
	return map[extension] || <IconOtherFile size={size} />
}

// Get file type icon by document type enum value
export const getFileIconByType = (type: number, size = 24) => {
	const map = {
		[fileTypeMap.UNKNOWN]: <IconOtherFile size={size} />,
		[fileTypeMap.TXT]: <IconTextFile size={size} />,
		[fileTypeMap.MD]: <IconMarkdownFile size={size} />,
		[fileTypeMap.PDF]: <IconPDFFile size={size} />,
		[fileTypeMap.XLSX]: <IconExcelFile size={size} />,
		[fileTypeMap.XLS]: <IconExcelFile size={size} />,
		[fileTypeMap.DOCX]: <IconDocxFile size={size} />,
		[fileTypeMap.CSV]: <IconExcelFile size={size} />,
		[fileTypeMap.XML]: <IconXMindFile size={size} />,
	}
	return map[type] || <IconOtherFile size={size} />
}

/** Document sync status mapping */
export enum documentSyncStatusMap {
	/** Not synced */
	Pending = 0,
	/** Synced */
	Success = 1,
	/** Sync failed */
	Failed = 2,
	/** Syncing */
	Processing = 3,
	/** Deleted successfully */
	Deleted = 4,
	/** Delete failed */
	DeleteFailed = 5,
	/** Rebuilding */
	Rebuilding = 6,
}

/** File types supported by knowledge base embedding */
export const SUPPORTED_EMBED_FILE_TYPES =
	"text/plain,text/markdown,.md,.markdown,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,text/xml"

/** Knowledge base type */
export enum knowledgeType {
	/** User-created knowledge base */
	UserKnowledgeDatabase = 1,
	/** Teamshare knowledge base */
	TeamshareKnowledgeDatabase = 2,
}

/** Document operation type enum */
export enum DocumentOperationType {
	ENABLE = "enable",
	DISABLE = "disable",
	DELETE = "delete",
}

/** Segmentation mode */
export enum SegmentationMode {
	/** General mode */
	General = 1,
	/** Parent-child segmentation */
	ParentChild = 2,
}

/** Parent block mode */
export enum ParentBlockMode {
	/** Paragraph */
	Paragraph = 1,
	/** Full text */
	FullText = 2,
}

/** Text preprocessing rules */
export enum TextPreprocessingRules {
	/** Replace consecutive spaces, newlines, and tabs */
	ReplaceSpaces = 1,
	/** Remove all URLs and email addresses */
	RemoveUrls = 2,
}

/** Retrieval method */
export enum RetrievalMethod {
	/** Semantic search */
	SemanticSearch = "semantic_search",
	/** Full text search */
	FullTextSearch = "full_text_search",
	/** Hybrid search */
	HybridSearch = "hybrid_search",
	/** Graph search */
	GraphSearch = "graph_search",
}

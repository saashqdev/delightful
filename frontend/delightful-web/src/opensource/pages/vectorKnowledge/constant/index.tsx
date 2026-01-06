import {
	IconTextFile,
	IconMarkdownFile,
	IconPDFFile,
	IconExcelFile,
	IconDocxFile,
	IconXMindFile,
	IconOtherFile,
} from "@/enhance/tabler/icons-react"

// 文件类型与拓展名的映射
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

// 文件类型与枚举值的映射
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

// 支持向量知识库嵌入的文件类型
export const supportedFileExtensions = Object.values(fileExtensionMap)

// 根据文件扩展名获取文件类型图标
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

// 根据文档类型枚举值获取文件类型图标
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

/** 文档同步状态映射 */
export enum documentSyncStatusMap {
	/** 未同步 */
	Pending = 0,
	/** 已同步 */
	Success = 1,
	/** 同步失败 */
	Failed = 2,
	/** 同步中 */
	Processing = 3,
	/** 删除成功 */
	Deleted = 4,
	/** 删除失败 */
	DeleteFailed = 5,
	/** 重建中 */
	Rebuilding = 6,
}

/** 知识库支持嵌入的文件类型 */
export const SUPPORTED_EMBED_FILE_TYPES =
	"text/plain,text/markdown,.md,.markdown,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,text/xml"

/** 知识库类型 */
export enum knowledgeType {
	/** 用户自建知识库 */
	UserKnowledgeDatabase = 1,
	/** 天书知识库 */
	TeamshareKnowledgeDatabase = 2,
}

/** 文档操作类型枚举 */
export enum DocumentOperationType {
	ENABLE = "enable",
	DISABLE = "disable",
	DELETE = "delete",
}

/** 分段模式 */
export enum SegmentationMode {
	/** 通用模式 */
	General = 1,
	/** 父子分段 */
	ParentChild = 2,
}

/** 父块模式 */
export enum ParentBlockMode {
	/** 段落 */
	Paragraph = 1,
	/** 全文 */
	FullText = 2,
}

/** 文本预处理规则 */
export enum TextPreprocessingRules {
	/** 替换掉连续的空格、换行符和制表符 */
	ReplaceSpaces = 1,
	/** 删除所有 URL 和电子邮件地址 */
	RemoveUrls = 2,
}

/** 检索方法 */
export enum RetrievalMethod {
	/** 语义检索 */
	SemanticSearch = "semantic_search",
	/** 全文检索 */
	FullTextSearch = "full_text_search",
	/** 混合检索 */
	HybridSearch = "hybrid_search",
	/** 图检索 */
	GraphSearch = "graph_search",
}

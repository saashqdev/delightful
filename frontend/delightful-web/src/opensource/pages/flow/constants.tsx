/* eslint-disable @typescript-eslint/naming-convention */

export enum customNodeType {
	// Start
	Start = "1",
	// Large Language Model
	LLM = "2",
	// Reply Message
	ReplyMessage = "3",
	// Selector
	If = "4",
	// Code
	Code = "5",
	// Data Loader
	Loader = "8",
	// HTTP Request
	HTTP = "10",
	// Sub Process
	Sub = "11",
	// End
	End = "12",
	// Historical Message Query
	MessageSearch = "13",
	// Text Split
	TextSplit = "14",
	// Intention Recognition
	IntentionRecognition = "24",
	// Vector Storage (Knowledge Base Fragment Storage)
	VectorStorage = "16",
	// Vector Search (Similarity Matching)
	VectorSearch = "17",
	// Vector Delete (Fragment Deletion)
	VectorDelete = "27",
	// Data Setter
	CacheSetter = "18",
	// Data Getter
	CacheGetter = "19",
	// Message Memory Storage
	MessageMemory = "20",
	// Variable - Save Variable
	VariableSave = "21",
	// Variable - Pop First Value from Array Variable
	// VariableArrayPop = "22",
	// Variable - Push Value into Array Variable
	// VariableArrayPush = "23",
	// Loop Node
	Loop = "30",
	// Loop Body Node
	LoopBody = "31",
	// Loop End Node
	LoopEnd = "32",
	// Search Users
	SearchUsers = "28",
	// Wait For Reply Node
	WaitForReply = "29",
	// Agent Node
	Agent = "50",
	// Excel Parsing Node
	Excel = "51",
	// Tools Node
	Tools = "26",
	// Vector Knowledge Base / Match Database
	VectorDatabaseMatch = "52",
	// Text to Image Node
	Text2Image = "53",
	// Group Chat Node
	GroupChat = "54",
	// Special Node: Quick Instructions Node
	Instructions = "1001",
}

export const DELIGHTFUL_FLOW_ID_PREFIX = "DELIGHTFUL-FLOW-"

// Nodes that need dynamically generated output
export const DynamicOutputNodeTypes = [
	customNodeType.Code,
	customNodeType.VariableSave,
	customNodeType.Sub,
]

// Current Topic
export const KnowledgeCurrentTopic = "knowledge_user_current_topic"
// Current Conversation
export const KnowledgeCurrentConversation = "knowledge_user_current_conversation"

export const OmitNodeKeys = [
	"data",
	"expandParent",
	"extent",
	"parentId",
	"deletable",
	"position",
	"step",
	"zIndex",
	"type",
]






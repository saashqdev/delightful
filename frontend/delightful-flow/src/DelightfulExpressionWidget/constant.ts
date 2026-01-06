import { EXPRESSION_VALUE, VALUE_TYPE } from "./types"

export const JSONPATH_JOIN_CHAR = "."

export const STRING_FORMATS = [
	{ name: "date-time" },
	{ name: "date" },
	{ name: "email" },
	{ name: "hostname" },
	{ name: "ipv4" },
	{ name: "ipv6" },
	{ name: "uri" },
]

export const MOCK_SOURCE = [
	{ name: "String", mock: "@string" },
	{ name: "Natural number", mock: "@natural" },
	{ name: "Float", mock: "@float" },
	{ name: "Character", mock: "@character" },
	{ name: "Boolean", mock: "@boolean" },
	{ name: "url", mock: "@url" },
	{ name: "Domain", mock: "@domain" },
	{ name: "IP address", mock: "@ip" },
	{ name: "id", mock: "@id" },
	{ name: "guid", mock: "@guid" },
	{ name: "Current time", mock: "@now" },
	{ name: "Timestamp", mock: "@timestamp" },
	{ name: "Date", mock: "@date" },
	{ name: "Time", mock: "@time" },
	{ name: "Datetime", mock: "@datetime" },
	{ name: "Image url", mock: "@image" },
	{ name: "Image data", mock: "@imageData" },
	{ name: "Color", mock: "@color" },
	{ name: "Color hex", mock: "@hex" },
	{ name: "Color rgba", mock: "@rgba" },
	{ name: "Color rgb", mock: "@rgb" },
	{ name: "Color hsl", mock: "@hsl" },
	{ name: "Integer", mock: "@integer" },
	{ name: "email", mock: "@email" },
	{ name: "Paragraph", mock: "@paragraph" },
	{ name: "Sentence", mock: "@sentence" },
	{ name: "Word", mock: "@word" },
	{ name: "Chinese paragraph", mock: "@cparagraph" },
	{ name: "Chinese title", mock: "@ctitle" },
	{ name: "Title", mock: "@title" },
	{ name: "Name", mock: "@name" },
	{ name: "Chinese name", mock: "@cname" },
	{ name: "Chinese surname", mock: "@cfirst" },
	{ name: "Chinese given name", mock: "@clast" },
	{ name: "English first name", mock: "@first" },
	{ name: "English last name", mock: "@last" },
	{ name: "Chinese sentence", mock: "@csentence" },
	{ name: "Chinese word", mock: "@cword" },
	{ name: "Region", mock: "@region" },
	{ name: "Province", mock: "@province" },
	{ name: "City", mock: "@city" },
	{ name: "County", mock: "@county" },
	{ name: "To uppercase", mock: "@upper" },
	{ name: "To lowercase", mock: "@lower" },
	{ name: "Pick (enum)", mock: "@pick" },
	{ name: "Shuffle array", mock: "@shuffle" },
	{ name: "Protocol", mock: "@protocol" },
]

export const SCHEMA_TYPE = [
	"object",
	"array",
	"string",
	"number",
	"boolean",
	//   'integer', temporarily disabled
]

export const ONLY_JSON_ROOT = ["object", "array"]

// KeyCode lookup table
export const KeyCodeMap = {
	ENTER: 13,
	TAB: 9,
	C: 67,
	V: 86,
	D: 68,
	F: 70,
	X: 88,
	A: 65,
	S: 83,
	UP: 38,
	Z: 90,
	Y: 89,
	RIGHT: 39,
	DOWN: 40,
	LEFT: 37,
	COMMAND: 91,
	DELETE: 46,
	BACKSPACE: 8,
	ESC: 27,
}

// Parameter value types
export const enum ValueType {
	CONST = "const",
	EXPRESSION = "expression",
}

// Field names for each parameter value type
export const FieldsName = {
	[ValueType.CONST]: "const_value",
	[ValueType.EXPRESSION]: "ExpressionValue",
}

/** Expression mode */
export enum ExpressionMode {
	/** Standard usage */
	Common = "common",
	/** Textarea usage */
	TextArea = "textarea",
}

export const TextAreaModeTrigger = "@"
export const TextAreaModePanelHeight = 120

export const defaultExpressionValue = {
	type: VALUE_TYPE.CONST,
	const_value: [] as EXPRESSION_VALUE[],
	expression_value: [] as EXPRESSION_VALUE[]
}


export const SystemNodeSuffix = "_system"

import { PreprocessRule } from "./types"
import { parseTable } from "./utils"

// Match LaTeX block formula regex - will move to conditional handling
export const BLOCK_MATH_REGEX = /\$\$([\s\S]*?)\$\$/g
// Improved LaTeX inline formula regex - more precisely match mathematical formulas
// Match content containing math symbols, LaTeX commands, or math operators
export const INLINE_MATH_REGEX = /\$([^$\n]*(?:[\\+\-*/=^_{}<>]|\\[a-zA-Z]+|[α-ωΑ-Ω])[^$\n]*)\$/g
// Citation format regex

export const CITATION_REGEX_1 = /\[\[citation:(\d+)\]\]/g
export const CITATION_REGEX_2 = /\[\[citation(\d+)\]\]/g
export const CITATION_REGEX_3 = /\[citation:(\d+)\]/g
export const CITATION_REGEX_4 = /\[citation(\d+)\]/g
// GFM related regex

export const STRIKETHROUGH_REGEX = /~~(.+?)~~/g
export const TASK_LIST_REGEX = /^(\s*)-\s+\[(x| )\]\s+(.+)$/gm
// GFM table regex - fixed version that correctly matches markdown tables
// First group: header row, second group: separator row (mainly contains dashes), third group: data rows
export const TABLE_REGEX =
	/^\s*(\|[^\n]*\|)\s*\n\s*(\|[\s\-:|\s]*\|)\s*\n((?:\s*\|[^\n]*\|\s*(?:\n|$))*)/gm
// Linked image regex - match [![alt](img_url)](link_url) format
export const LINKED_IMAGE_REGEX = /\[!\[([^\]]*)\]\(([^)]+)\)\]\(([^)]+)\)/g
// GFM horizontal rule regex - match ---, ***, ___ format
export const HORIZONTAL_RULE_REGEX = /^(?:---+|\*\*\*+|___+)$/gm
// Footnote reference regex - match [^1] format
export const FOOTNOTE_REF_REGEX = /\[\^([^\]]+)\]/g
// Footnote definition regex - match [^1]: content format
export const FOOTNOTE_DEF_REGEX = /^\[\^([^\]]+)\]:\s*(.+?)(?=\n\n|\n$|$)/gm
// Abbreviation definition regex - match *[HTML]: HyperText Markup Language format
export const ABBREVIATION_DEF_REGEX = /^\*\[([^\]]+)\]:\s*(.+)$/gm
// Multi-level task list regex - supports nested task lists
export const NESTED_TASK_LIST_REGEX = /^(\s*)-\s+\[(x| )\]\s+(.+)$/gm

// Superscript and subscript regex
export const SUPERSCRIPT_REGEX = /\^([^^\s]+)\^/g
export const SUBSCRIPT_REGEX = /~([^~\s]+)~/g

// Reference links support
// Match reference link definition: [1]: https://example.com "title"
export const REFERENCE_LINK_DEF_REGEX = /^\s*\[([^\]]+)\]:\s*([^\s]+)(?:\s+"([^"]*)")?\s*$/gm
// Match reference link usage: [text][1] or [text]
export const REFERENCE_LINK_USE_REGEX = /\[([^\]]+)\](?:\[([^\]]*)\])?/g

export const defaultPreprocessRules: PreprocessRule[] = [
	// Remove block formula handling - will add in conditional handling
	{
		regex: CITATION_REGEX_1,
		replace: (_, index) => `<DelightfulCitation index="${index}" />`,
	},
	{
		regex: CITATION_REGEX_2,
		replace: (_, index) => `<DelightfulCitation index="${index}" />`,
	},
	{
		regex: CITATION_REGEX_3,
		replace: (_, index) => `<DelightfulCitation index="${index}" />`,
	},
	{
		regex: CITATION_REGEX_4,
		replace: (_, index) => `<DelightfulCitation index="${index}" />`,
	},
	{
		regex: LINKED_IMAGE_REGEX,
		replace: (_, altText, imgUrl, linkUrl) =>
			`<a href="${linkUrl}"><img src="${imgUrl}" alt="${altText}" /></a>`,
	},
	{
		regex: HORIZONTAL_RULE_REGEX,
		replace: () => `<hr />`,
	},
	{
		regex: STRIKETHROUGH_REGEX,
		replace: (_, content) => `<span class="strikethrough">${content}</span>`,
	},
	{
		regex: TASK_LIST_REGEX,
		replace: (_, indent, checked, content) => {
			const level = Math.floor(indent.length / 2) // Every 2 spaces is one level
			const marginLeft = level * 20 // 20px indentation per level
			return `<li class="task-list-item" data-checked="${checked}" style="margin-left: ${marginLeft}px;">${content}</li>`
		},
	},
	{
		regex: FOOTNOTE_DEF_REGEX,
		replace: (_, id, content) =>
			`<div class="footnote" id="fn-${id}"><p>${content} <a href="#fnref-${id}" class="footnote-backref">↩</a></p></div>`,
	},
	{
		regex: FOOTNOTE_REF_REGEX,
		replace: (_, id) =>
			`<sup class="footnote-ref"><a href="#fn-${id}" id="fnref-${id}">${id}</a></sup>`,
	},
	{
		regex: ABBREVIATION_DEF_REGEX,
		replace: () => "", // Abbreviation definitions are not displayed, only used for subsequent replacement
	},
	{
		regex: TABLE_REGEX,
		replace: (_, header, separator, rows) => parseTable(header, separator, rows),
	},
	{
		regex: SUPERSCRIPT_REGEX,
		replace: (_, content) => `<sup>${content}</sup>`,
	},
	{
		regex: SUBSCRIPT_REGEX,
		replace: (_, content) => `<sub>${content}</sub>`,
	},
]

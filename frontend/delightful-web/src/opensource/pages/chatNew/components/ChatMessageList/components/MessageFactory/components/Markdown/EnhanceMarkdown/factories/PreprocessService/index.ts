import {
	defaultPreprocessRules,
	INLINE_MATH_REGEX,
	BLOCK_MATH_REGEX,
	ABBREVIATION_DEF_REGEX,
	TASK_LIST_REGEX,
} from "./defaultPreprocessRules"
import { PreprocessRule } from "./types"

class PreprocessService {
	rules: Map<string, PreprocessRule> = new Map()

	constructor(initialRules?: PreprocessRule[]) {
		defaultPreprocessRules.forEach((rule) => this.registerRule(rule.regex, rule))

		if (initialRules && initialRules.length > 0) {
			initialRules.forEach((rule) => this.registerRule(rule.regex, rule))
		}
	}

	/**
	 * Get all registered rules
	 */
	getAllRules() {
		return Array.from(this.rules.values())
	}

	/**
	 * Register a rule
	 */
	registerRule(key: RegExp, rule: PreprocessRule) {
		this.rules.set(key.toString(), rule)
	}

	/**
	 * Unregister a rule
	 */
	unregisterRule(key: RegExp) {
		this.rules.delete(key.toString())
	}

	/**
	 * Get the inline LaTeX rule
	 */
	getInlineLatexRule(): PreprocessRule {
		return {
			regex: INLINE_MATH_REGEX,
			replace: (_, formulaContent) =>
				`<DelightfulLatexInline math="${this.encodeForAttribute(formulaContent)}" />`,
		}
	}

	/**
	 * Get the block-level LaTeX rule
	 */
	getBlockLatexRule(): PreprocessRule {
		return {
			regex: BLOCK_MATH_REGEX,
			replace: (_, formulaContent) =>
				`<DelightfulLatexBlock math="${this.encodeForAttribute(formulaContent.trim())}" />`,
		}
	}

	/**
	 * Encode LaTeX content so it is safe for HTML attributes
	 */
	private encodeForAttribute(content: string): string {
		return content
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#39;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
	}

	/**
	 * Determine whether a range is inside a blockquote
	 */
	private isInsideBlockquote(markdown: string, start: number, end: number): boolean {
		// Text from the beginning to the current position
		const beforeText = markdown.substring(0, start)
		const lines = beforeText.split("\n")

		// Walk backwards to see if we are inside a quote block
		let inBlockquote = false
		for (let i = lines.length - 1; i >= 0; i--) {
			const line = lines[i]
			// If we meet a line that starts the quote
			if (line.trim().startsWith(">")) {
				inBlockquote = true
				break
			}
			// If a non-empty, non-quote line is found, we are outside the quote
			if (line.trim() && !line.trim().startsWith(">")) {
				break
			}
		}

		if (!inBlockquote) return false

		// Check whether the entire range stays within the quote
		const textToCheck = markdown.substring(start, end)
		const allLines = markdown.substring(0, end).split("\n")
		const startLineIndex = beforeText.split("\n").length - 1

		// Ensure every involved line is still inside the quote
		for (let i = startLineIndex; i < allLines.length; i++) {
			const line = allLines[i]
			if (line.trim() && !line.trim().startsWith(">") && !line.includes("```")) {
				// A non-quote line outside code fence means we left the quote
				return false
			}
		}

		return true
	}

	/**
	 * Split Markdown into block code sections and images
	 */
	splitBlockCode(markdown: string): string[] {
		const blocks: string[] = []
		if (!markdown || markdown.trim() === "") {
			return blocks
		}

		// Match ```lang\n...``` style code fences (language may include hyphens)
		const codeBlockRegex = /```([a-zA-Z0-9_-]*)\s*\n([\s\S]*?)```/g

		// Match images in the form ![...](...)
		const imgRegex = /!\[.*?\]\((.*?)\)/g

		// Collect all special blocks to split (code blocks and images)
		const specialBlocks: Array<{
			start: number
			end: number
			content: string
			type: "code" | "image"
		}> = []

		// Collect all code blocks
		let match
		while ((match = codeBlockRegex.exec(markdown)) !== null) {
			const language = match[1]
			const code = match[2]
			const blockStart = match.index
			const blockEnd = match.index + match[0].length

			// Skip if the code block is inside a quote
			if (!this.isInsideBlockquote(markdown, blockStart, blockEnd)) {
				specialBlocks.push({
					start: blockStart,
					end: blockEnd,
					content: `\`\`\`${language}\n${code}\`\`\``,
					type: "code",
				})
			}
		}

		// Reset lastIndex for reuse
		codeBlockRegex.lastIndex = 0

		// Collect all images, excluding those inside code blocks
		while ((match = imgRegex.exec(markdown)) !== null) {
			const imageStart = match.index
			const imageEnd = match.index + match[0].length

			// Skip images inside code blocks
			const isInsideCodeBlock = specialBlocks.some(
				(block) =>
					block.type === "code" && imageStart >= block.start && imageEnd <= block.end,
			)

			// Skip images inside quotes
			const isInsideQuote = this.isInsideBlockquote(markdown, imageStart, imageEnd)

			// Only add images that are outside code blocks and quotes
			if (!isInsideCodeBlock && !isInsideQuote) {
				specialBlocks.push({
					start: imageStart,
					end: imageEnd,
					content: match[0],
					type: "image",
				})
			}
		}

		// Reset lastIndex for reuse
		imgRegex.lastIndex = 0

		// Sort by position
		specialBlocks.sort((a, b) => a.start - b.start)

		// Split the text using special blocks as separators
		let lastIndex = 0
		for (const block of specialBlocks) {
			// Add content before the block
			const beforeContent = markdown.substring(lastIndex, block.start)
			if (beforeContent.trim()) {
				blocks.push(beforeContent.trim())
			}

			// Add the special block (code or image)
			blocks.push(block.content)

			lastIndex = block.end
		}

		// Add trailing content
		const remainingText = markdown.substring(lastIndex)
		if (remainingText.trim()) {
			blocks.push(remainingText.trim())
		}

		// If no special blocks were found, return the original text
		if (blocks.length === 0 && markdown.trim()) {
			blocks.push(markdown.trim())
		}

		return blocks
	}

	/**
	 * Handle abbreviation definitions and replacements
	 */
	processAbbreviations(markdown: string): string {
		// Phase 1: collect all abbreviation definitions
		const abbreviations = new Map<string, string>()
		const matches = markdown.matchAll(ABBREVIATION_DEF_REGEX)

		for (const match of matches) {
			const [, abbr, definition] = match
			abbreviations.set(abbr, definition)
		}

		// Remove definition lines and compress blank lines
		let processedMarkdown = markdown.replace(ABBREVIATION_DEF_REGEX, "")

		// Collapse long sequences of blank lines
		processedMarkdown = processedMarkdown.replace(/\n{3,}/g, "\n\n")

		// Phase 2: replace abbreviations in text
		for (const [abbr, definition] of abbreviations) {
			// Match full-word abbreviations to avoid partial matches
			const abbrRegex = new RegExp(
				`\\b${abbr.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}\\b`,
				"g",
			)
			processedMarkdown = processedMarkdown.replace(
				abbrRegex,
				`<abbr title="${definition}">${abbr}</abbr>`,
			)
		}

		return processedMarkdown
	}

	/**
	 * Handle reference link definitions and replacements
	 */
	processReferenceLinks(markdown: string): string {
		// Phase 1: collect reference link definitions
		const referenceLinks = new Map<string, { url: string; title?: string }>()
		const REFERENCE_LINK_DEF_REGEX = /^\s*\[([^\]]+)\]:\s*([^\s]+)(?:\s+"([^"]*)")?\s*$/gm
		const matches = markdown.matchAll(REFERENCE_LINK_DEF_REGEX)

		for (const match of matches) {
			const [, id, url, title] = match
			referenceLinks.set(id.toLowerCase(), { url, title })
		}

		// Remove definition lines
		let processedMarkdown = markdown.replace(REFERENCE_LINK_DEF_REGEX, "")

		// Phase 2: replace reference link usages `[text][id]` or `[text]`
		const REFERENCE_LINK_USE_REGEX = /\[([^\]]+)\](?:\[([^\]]*)\])?/g

		processedMarkdown = processedMarkdown.replace(
			REFERENCE_LINK_USE_REGEX,
			(match, text, id) => {
				// Use text as id if none provided
				const linkId = (id !== undefined ? id : text).toLowerCase()
				const linkInfo = referenceLinks.get(linkId)

				if (linkInfo) {
					const titleAttr = linkInfo.title ? ` title="${linkInfo.title}"` : ""
					return `<a href="${linkInfo.url}"${titleAttr} target="_blank" rel="noopener noreferrer">${text}</a>`
				}

				// Leave untouched if no definition is found
				return match
			},
		)

		return processedMarkdown
	}

	/**
	 * Fix inline HTML tags at paragraph starts by inserting a zero-width space
	 * Note: does not handle block-level elements like DelightfulLatexBlock, div, hr, etc.
	 */
	private fixParagraphInlineHtmlTags(markdown: string): string {
		// Covers common inline HTML tags and Delightful inline components (excluding block ones)
		return markdown.replace(
			/(\n|^)(\s*)(<(?:abbr|sup|span|a|kbd|cite|q|s|u|mark|small|strong|em|sub|DelightfulLatexInline|DelightfulCitation)\b[^>]*>)/g,
			"$1$2\u200B$3",
		)
	}

	/**
	 * Protect code blocks from being altered by preprocessing rules
	 */
	private protectCodeBlocks(markdown: string): {
		processedMarkdown: string
		codeBlockMap: Map<string, string>
	} {
		const codeBlockMap = new Map<string, string>()
		let counter = 0

		// Match all code fences (with optional language)
		const codeBlockRegex = /```[\s\S]*?```/g

		const processedMarkdown = markdown.replace(codeBlockRegex, (match) => {
			const placeholder = `__CODE_BLOCK_PLACEHOLDER_${counter}__`
			codeBlockMap.set(placeholder, match)
			counter++
			return placeholder
		})

		return { processedMarkdown, codeBlockMap }
	}

	/**
	 * Restore protected code blocks
	 */
	private restoreCodeBlocks(markdown: string, codeBlockMap: Map<string, string>): string {
		let result = markdown

		for (const [placeholder, originalContent] of codeBlockMap) {
			result = result.replace(placeholder, originalContent)
		}

		return result
	}

	/**
	 * Preprocess markdown text
	 */
	preprocess(markdown: string, options?: { enableLatex?: boolean }): string[] {
		// 首先保护代码块内容
		const { processedMarkdown: protectedMarkdown, codeBlockMap } =
			this.protectCodeBlocks(markdown)

		// handle缩写
		let processedMarkdown = this.processAbbreviations(protectedMarkdown)

		// handle参考链接
		processedMarkdown = this.processReferenceLinks(processedMarkdown)

		// handle多级tasklist（在其他规则之前）
		processedMarkdown = this.processNestedTaskLists(processedMarkdown)

		const rules = this.getAllRules()

		// 移除原有的单一tasklisthandle规则，因为我们已经用新methodhandle了
		const filteredRules = rules.filter(
			(rule) => rule.regex.toString() !== TASK_LIST_REGEX.toString(),
		)

		// Protect dollar signs inside tables before LaTeX handling (if enabled)
		let tableProtectionMap = new Map<string, string>()
		if (options?.enableLatex) {
			const { markdown: protectedTableMarkdown, protectionMap } =
				this.protectTableDollarSigns(processedMarkdown)
			processedMarkdown = protectedTableMarkdown
			tableProtectionMap = protectionMap
		}

		if (options?.enableLatex) {
			// Block math must be processed before inline math
			filteredRules.unshift(this.getBlockLatexRule())
			filteredRules.push(this.getInlineLatexRule())
		}

		for (const rule of filteredRules) {
			processedMarkdown = processedMarkdown.replace(rule.regex, rule.replace)
		}

		// Restore table dollar signs if they were protected
		if (tableProtectionMap.size > 0) {
			processedMarkdown = this.restoreTableDollarSigns(processedMarkdown, tableProtectionMap)
		}

		// Finally fix inline HTML at paragraph starts
		processedMarkdown = this.fixParagraphInlineHtmlTags(processedMarkdown)

		// Restore code blocks
		const finalMarkdown = this.restoreCodeBlocks(processedMarkdown, codeBlockMap)

		// Split markdown into block code segments
		return this.splitBlockCode(finalMarkdown)
	}

	/**
	 * Handle nested task lists and generate proper HTML structure
	 */
	processNestedTaskLists(markdown: string): string {
		const lines = markdown.split("\n")
		const taskLines: Array<{
			line: string
			originalIndex: number
			indent: number
			checked: string
			content: string
			level: number
		}> = []

		// Collect all task list lines
		lines.forEach((line, index) => {
			const match = line.match(/^(\s*)-\s+\[(x| )\]\s+(.+)$/)
			if (match) {
				const [, indent, checked, content] = match
				const level = Math.floor(indent.length / 2)
				taskLines.push({
					line,
					originalIndex: index,
					indent: indent.length,
					checked,
					content,
					level,
				})
			}
		})

		if (taskLines.length === 0) {
			return markdown
		}

		// Build nested HTML structure
		const buildNestedHTML = (tasks: typeof taskLines, startLevel: number = 0): string => {
			const result: string[] = []
			let i = 0

			while (i < tasks.length) {
				const task = tasks[i]

				if (task.level < startLevel) {
					break
				}

				if (task.level > startLevel) {
					// Higher-level tasks are handled in recursion
					i++
					continue
				}

				// Current level task
				const checkbox = `<input type="checkbox" ${
					task.checked === "x" ? "checked" : ""
				} readonly />`

				// Find subtasks
				const childTasks: typeof taskLines = []
				let j = i + 1
				while (j < tasks.length && tasks[j].level > task.level) {
					childTasks.push(tasks[j])
					j++
				}

				// Build HTML for the task
				let taskHTML = `<li class="task-list-item">${checkbox}`

				// If subtasks exist, wrap text in a span and append nested list
				if (childTasks.length > 0) {
					taskHTML += `<span>${task.content}</span>`
					const childHTML = buildNestedHTML(childTasks, task.level + 1)
					if (childHTML) {
						taskHTML += `<ul class="task-list-nested">${childHTML}</ul>`
					}
				} else {
					// No subtasks; add content directly
					taskHTML += task.content
				}

				taskHTML += "</li>"
				result.push(taskHTML)

				// Skip processed subtasks
				i = j
			}

			return result.join("")
		}

		// Generate final HTML
		const nestedHTML = `<ul class="task-list-container">${buildNestedHTML(taskLines)}</ul>`

		// Replace original task lists
		const processedMarkdown = markdown
		const taskLineIndices = taskLines.map((t) => t.originalIndex).sort((a, b) => b - a)

		// Identify consecutive task list blocks and replace them
		const taskBlocks: Array<{ start: number; end: number }> = []
		let currentBlock: { start: number; end: number } | null = null

		taskLines.forEach((task) => {
			if (!currentBlock) {
				currentBlock = { start: task.originalIndex, end: task.originalIndex }
			} else if (
				task.originalIndex === currentBlock.end + 1 ||
				(task.originalIndex > currentBlock.end &&
					lines
						.slice(currentBlock.end + 1, task.originalIndex)
						.every((line) => line.trim() === ""))
			) {
				currentBlock.end = task.originalIndex
			} else {
				taskBlocks.push(currentBlock)
				currentBlock = { start: task.originalIndex, end: task.originalIndex }
			}
		})

		if (currentBlock) {
			taskBlocks.push(currentBlock)
		}

		// Replace in reverse order to avoid index issues
		taskBlocks.reverse().forEach((block) => {
			const blockTasks = taskLines.filter(
				(t) => t.originalIndex >= block.start && t.originalIndex <= block.end,
			)
			const blockHTML = `<ul class="task-list-container">${buildNestedHTML(blockTasks)}</ul>`

			const beforeLines = lines.slice(0, block.start)
			const afterLines = lines.slice(block.end + 1)

			lines.splice(0, lines.length, ...beforeLines, blockHTML, ...afterLines)
		})

		return lines.join("\n")
	}

	/**
	 * Protect dollar signs inside tables to avoid LaTeX mis-parsing
	 */
	private protectTableDollarSigns(markdown: string): {
		markdown: string
		protectionMap: Map<string, string>
	} {
		const protectionMap = new Map<string, string>()
		let protectedMarkdown = markdown

		// Find all table blocks
		const tableMatches = markdown.matchAll(
			/^\s*(\|[^\n]*\|)\s*\n\s*(\|[\s\-:|\s]*\|)\s*\n((?:\s*\|[^\n]*\|\s*(?:\n|$))*)/gm,
		)

		for (const match of tableMatches) {
			const fullTable = match[0]
			let protectedTable = fullTable

			// Replace all dollar signs with placeholders
			const dollarMatches = fullTable.matchAll(/\$/g)
			let offset = 0

			for (const dollarMatch of dollarMatches) {
				const placeholder = `__TABLE_DOLLAR_${protectionMap.size}__`
				protectionMap.set(placeholder, "$")

				const index = dollarMatch.index! + offset
				protectedTable =
					protectedTable.slice(0, index) + placeholder + protectedTable.slice(index + 1)
				offset += placeholder.length - 1
			}

			protectedMarkdown = protectedMarkdown.replace(fullTable, protectedTable)
		}

		return { markdown: protectedMarkdown, protectionMap }
	}

	/**
	 * Restore protected dollar signs inside tables
	 */
	private restoreTableDollarSigns(markdown: string, protectionMap: Map<string, string>): string {
		let restoredMarkdown = markdown

		for (const [placeholder, original] of protectionMap) {
			restoredMarkdown = restoredMarkdown.replace(new RegExp(placeholder, "g"), original)
		}

		return restoredMarkdown
	}
}

export default new PreprocessService()

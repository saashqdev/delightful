import { initMermaidDb } from "@/opensource/database/mermaid"
import type { MermaidDb } from "@/opensource/database/mermaid/types"
import { RenderResult } from "mermaid"

const MERMAID_ERROR_SVG = "<g></g></svg>"

class MermaidRenderService {
	cacheSvg: Map<string, { data: string; svg: string; png: string }> = new Map()
	db: MermaidDb

	constructor() {
		this.db = initMermaidDb()
		this.db.mermaid.toArray().then((res) => {
			this.cacheSvg = res.reduce((acc, cur) => {
				acc.set(cur.data, cur)
				return acc
			}, new Map<string, { data: string; svg: string; png: string }>())
		})
	}

	/**
	 * Check if svg generation failed
	 * @param svg svg
	 * @returns Whether generation failed
	 */
	isErrorSvg(svg: string) {
		return svg.includes(MERMAID_ERROR_SVG)
	}

	/**
	 * Cache svg
	 * @param chart Chart
	 * @param svg svg
	 */
	cache(chart: string, svg: RenderResult) {
		// If cache exists and svg is the same, don't cache
		if (this.cacheSvg.get(chart) && this.cacheSvg.get(chart)?.svg === svg.svg) {
			return this.cacheSvg.get(chart)
		}

		// If matches <g></g> tag, consider rendering unsuccessful
		if (this.isErrorSvg(svg.svg)) {
			return undefined
		}

		const result = {
			data: chart,
			svg: svg.svg,
			diagramType: svg.diagramType,
			png: "",
		}

		this.cacheSvg.set(chart, result)

		this.db.mermaid.put(result).catch((err) => {
			console.error("Cache failed", err)
		})

		return result
	}

	/**
	 * Get cache
	 * @param chart Chart
	 * @returns Cache
	 */
	async getCache(chart: string): Promise<{ data: string; svg: string; png: string } | undefined> {
		if (this.cacheSvg.get(chart)) {
			return this.cacheSvg.get(chart)
		}
		const res = await this.db.mermaid.where("data").equals(chart).first()
		if (res) {
			this.cacheSvg.set(chart, res)
			return res
		}
		return undefined
	}

	/**
	 * Fix mermaid data
	 * @description Fix Chinese punctuation in mermaid data, convert Chinese punctuation to English, but skip content inside square brackets
	 * @param data Data
	 * @returns Fixed data
	 */
	fix(data: string | undefined): string {
		if (!data) return ""

		// Define Chinese to English punctuation mapping
		const punctuationMap: Record<string, string> = {
			"，": ", ",
			"。": ".",
			"：": ": ",
			"；": "; ",
			"！": "! ",
			"？": "? ",
			"、": " ",
			"（": " (",
			"）": ") ",
			"\u201c": '"',
			"\u201d": '"',
			"【": " [",
			"】": "] ",
			"°": "degrees",
			"¥": "yuan",
			"~": "~",
			"-": "-",
		}

		// Split text by brackets, preserving the brackets and their content
		const parts: string[] = []
		let currentPos = 0
		let bracketStart = data.indexOf("[", currentPos)

		while (bracketStart !== -1) {
			// Add text before bracket
			if (bracketStart > currentPos) {
				parts.push(data.substring(currentPos, bracketStart))
			}

			// Find matching closing bracket
			const bracketEnd = data.indexOf("]", bracketStart)
			if (bracketEnd !== -1) {
				// Add bracket content (including brackets) without modification
				parts.push(data.substring(bracketStart, bracketEnd + 1))
				currentPos = bracketEnd + 1
			} else {
				// No matching closing bracket found, treat as regular text
				parts.push(data.substring(bracketStart))
				break
			}

			bracketStart = data.indexOf("[", currentPos)
		}

		// Add remaining text after last bracket
		if (currentPos < data.length) {
			parts.push(data.substring(currentPos))
		}

		// Process each part - only modify parts that are not inside brackets
		const processedParts = parts.map((part) => {
			// Check if this part is inside brackets (starts with '[' and ends with ']')
			if (part.startsWith("[") && part.endsWith("]")) {
				return part // Keep bracket content unchanged
			}

			// Apply punctuation replacement to non-bracket content
			let result = part
			for (const [chinese, english] of Object.entries(punctuationMap)) {
				result = result.replace(new RegExp(chinese, "g"), english)
			}
			return result
		})

		return processedParts.join("")
	}
}

export default new MermaidRenderService()

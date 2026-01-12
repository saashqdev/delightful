import { LazyExoticComponent, ComponentType, lazy } from "react"
import { RenderComponent } from "../components/Code/types"
import { BlockRenderProps, Block } from "../components/Block/types"
import BaseRenderFactory from "./BaseRenderFactory"

const Fallback = lazy(() => import("../components/Block/components/Fallback"))

// Content block rendering factory
class BlockRenderFactory extends BaseRenderFactory<BlockRenderProps> {
	/**
	 * Splitting rules (regex)
	 */
	protected splitters = new Map<string, RegExp>()

	/**
	 * Component cache
	 */
	protected componentCache = new Map<
		string,
		LazyExoticComponent<ComponentType<BlockRenderProps>>
	>()

	/**
	 * Components
	 */
	protected components = new Map<string, RenderComponent<BlockRenderProps>>()

	/**
	 * Constructor function
	 */
	constructor() {
		super()
	}

	/**
	 * Get default component
	 * @returns Default component
	 */
	public getFallbackComponent(): LazyExoticComponent<ComponentType<BlockRenderProps>> {
		return Fallback
	}

	/**
	 * Register block recognizer
	 */
	registerSplitters(lang: string, blockRecognizer: RegExp) {
		this.splitters.set(lang, blockRecognizer)
	}

	/**
	 * Extract blocks matching rules
	 */
	private extractBlocks(content: string) {
		const extractedBlocks = []

		// Iterate through each defined matcher
		for (const [lang, regex] of this.splitters) {
			let match
			// Reset regex lastIndex
			regex.lastIndex = 0

			// Match current type
			while ((match = regex.exec(content)) !== null) {
				const fullMatch = match[0] // Full match (including ``` markers)
				const content = match[1]?.trim() // Content with markers removed
				const position = match.index // Code block position in original text

				// Save extraction result
				extractedBlocks[position] = {
					lang,
					content,
					position: position,
					length: fullMatch.length,
				}
			}
		}

		return extractedBlocks
	}

	/**
	 * Generate split blocks
	 */
	public getBlocks(content: string | undefined) {
		if (!content) return []

		if (this.splitters.size === 0) {
			return [content]
		}

		const extractedBlocks = this.extractBlocks(content)
		// Partition into blocks
		const blocks: Block[] = []
		let lastIndex = 0

		extractedBlocks.forEach((value) => {
			// At the very front of text
			if (value.position === 0) {
				blocks.push({
					component: this.getComponent(value.lang),
					props: {
						language: value.lang,
						data: value.content,
					},
				})
			} else {
				blocks.push(content.substring(lastIndex, value.position))
				blocks.push({
					component: this.getComponent(value.lang),
					props: {
						language: value.lang,
						data: value.content,
					},
				})
				lastIndex = value.position + value.length
			}
		})

		// Last block
		if (lastIndex < content.length) {
			blocks.push(content.substring(lastIndex))
		}

		return blocks
	}
}
export default new BlockRenderFactory()

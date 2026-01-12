import { LazyExoticComponent, ComponentType, lazy } from "react"
import { RenderComponent } from "../components/Code/types"
import { BlockRenderProps, Block } from "../components/Block/types"
import BaseRenderFactory from "./BaseRenderFactory"

const Fallback = lazy(() => import("../components/Block/components/Fallback"))

// Content block rendering factory
class BlockRenderFactory extends BaseRenderFactory<BlockRenderProps> {
	/**
	 * 切割规则（正则）
	 */
	protected splitters = new Map<string, RegExp>()

	/**
	 * component缓存
	 */
	protected componentCache = new Map<
		string,
		LazyExoticComponent<ComponentType<BlockRenderProps>>
	>()

	/**
	 * component
	 */
	protected components = new Map<string, RenderComponent<BlockRenderProps>>()

	/**
	 * 构造function
	 */
	constructor() {
		super()
	}

	/**
	 * get默认component
	 * @returns 默认component
	 */
	public getFallbackComponent(): LazyExoticComponent<ComponentType<BlockRenderProps>> {
		return Fallback
	}

	/**
	 * 注册block识别器
	 */
	registerSplitters(lang: string, blockRecognizer: RegExp) {
		this.splitters.set(lang, blockRecognizer)
	}

	/**
	 * 提取符合规则的block
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
	 * 生成切割后的block
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

import { useMemo } from "react"
import { omit } from "lodash-es"
import { A as a } from "../components/A"
import Code from "../components/Code"
import KaTeX from "../components/KaTeX"
import TableCell from "../components/Table/TableCell"
import TableWrapper from "../components/Table/TableWrapper"
import Video from "../components/Video"
import Blockquote from "../components/Blockquote"
import type { MarkdownProps } from "../types"
import { RuleType } from "markdown-to-jsx"
import type { MarkdownToJSX } from "markdown-to-jsx"
import { useMemoizedFn } from "ahooks"
import DelightfulCitation from "../components/DelightfulCitation"
import InlineCodeRenderFactory from "../factories/InlineCodeRenderFactory"
import PreprocessService from "../factories/PreprocessService"
import CustomComponentService from "../factories/CustomComponentService"
import ImageWrapper from "@/opensource/pages/chatNew/components/AiImageStartPage/components/ImageWrapper"

/**
 * Build configuration required for markdown-to-jsx component
 */
export const useMarkdownConfig = (props: MarkdownProps) => {
	// Destructure needed props
	const { allowHtml = true, enableLatex = true, components: componentsInProps } = props

	// Base component configuration
	const baseOverrides = useMemo(() => {
		return {
			a: {
				component: a,
			},
			blockquote: {
				component: Blockquote,
			},
			table: {
				component: TableWrapper,
			},
			td: {
				component: (tdProps: any) => <TableCell {...tdProps} />,
			},
			th: {
				component: (thProps: any) => <TableCell isHeader {...thProps} />,
			},
			code: {
				component: (codeProps: any) => {
					return <Code markdownProps={omit(props, "content")} {...codeProps} />
				},
			},
			img: ImageWrapper,
			video: {
				component: Video,
			},
			// Add LaTeX support
			math: enableLatex
				? {
						component: (mathProps: { children: string }) => (
							<KaTeX math={mathProps.children} inline={false} />
						),
				  }
				: undefined,
			// Add LaTeX component support
			DelightfulLatexInline: {
				component: (props: any) => {
					if (!enableLatex) return <span>{`$${props.math}$`}</span>

					// Decode HTML entities
					const decodedMath = props.math
						.replace(/&amp;/g, "&")
						.replace(/&quot;/g, '"')
						.replace(/&#39;/g, "'")
						.replace(/&lt;/g, "<")
						.replace(/&gt;/g, ">")

					return <KaTeX math={decodedMath} inline={true} />
				},
			},
			DelightfulLatexBlock: {
				component: (props: any) => {
					if (!enableLatex) return <div>{`$$${props.math}$$`}</div>

					// Decode HTML entities
					const decodedMath = props.math
						.replace(/&amp;/g, "&")
						.replace(/&quot;/g, '"')
						.replace(/&#39;/g, "'")
						.replace(/&lt;/g, "<")
						.replace(/&gt;/g, ">")

					return <KaTeX math={decodedMath} inline={false} />
				},
			},
			// Add handling for strikethrough tags
			span: {
				component: (props: any) => {
					// Handle strikethrough (GFM)
					if (props.className === "strikethrough") {
						return <del>{props.children}</del>
					}
					return <span {...props} />
				},
			},
			// Handle task list (GFM)
			li: {
				component: (props: any) => {
					// New multi-level task list already generated correct HTML structure in preprocessing phase
					// Here we just maintain original behavior, let HTML render directly
					if (props.className === "task-list-item") {
						// For new task list, return li directly without adding extra checkbox
						// Because checkbox already generated in HTML during preprocessing phase
						return <li {...props} />
					}
					return <li {...props} />
				},
			},
			DelightfulCitation: {
				component: DelightfulCitation,
			},
			// Add superscript and subscript support
			sup: {
				component: (props: any) => <sup {...props} />,
			},
			sub: {
				component: (props: any) => <sub {...props} />,
			},
		}
	}, [props, enableLatex])

	// Merge custom component configuration
	const customOverrides = useMemo(() => {
		if (!componentsInProps) return {}

		// Convert react-markdown format components to markdown-to-jsx format overrides
		const converted: Record<string, { component: any; props: Record<string, never> }> = {}
		Object.entries(componentsInProps).forEach(([tag, Component]) => {
			if (Component) {
				converted[tag] = {
					component: Component,
					props: {} as Record<string, never>,
				}
			}
		})

		return converted
	}, [componentsInProps])

	// Merge all overrides
	const overrides = useMemo(() => {
		return {
			...baseOverrides,
			...CustomComponentService.getAllComponents(),
			...customOverrides,
		} as MarkdownToJSX.Overrides
	}, [baseOverrides, customOverrides])

	// Custom markdown-to-jsx preprocessing function
	const preprocess = useMemoizedFn((markdown: string) => {
		if (!markdown) return []

		return PreprocessService.preprocess(markdown, {
			enableLatex,
		})
	})

	// Configure markdown-to-jsx options
	const options = useMemo<MarkdownToJSX.Options>(() => {
		return {
			overrides,
			forceWrapper: true,
			disableParsingRawHTML: !allowHtml,
			// Use updated API to configure preprocessing function
			renderRule: (next: () => any, node: any) => {
				if (node.type === RuleType.codeInline) {
					const InlineCodeComponent = InlineCodeRenderFactory.getComponent(node.className)
					return <InlineCodeComponent className={node.className} data={node.text} />
				}

				return next()
			},
		}
	}, [overrides, allowHtml])

	return {
		options,
		preprocess,
	}
}

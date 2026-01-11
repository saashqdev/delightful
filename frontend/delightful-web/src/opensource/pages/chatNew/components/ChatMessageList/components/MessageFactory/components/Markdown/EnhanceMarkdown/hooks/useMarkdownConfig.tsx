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
 * 构建 markdown-to-jsx component所需的configuration
 */
export const useMarkdownConfig = (props: MarkdownProps) => {
	// 解构需要的props
	const { allowHtml = true, enableLatex = true, components: componentsInProps } = props

	// 基础componentconfiguration
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
			// 添加 LaTeX 支持
			math: enableLatex
				? {
						component: (mathProps: { children: string }) => (
							<KaTeX math={mathProps.children} inline={false} />
						),
				  }
				: undefined,
			// 添加LaTeXcomponent支持
			DelightfulLatexInline: {
				component: (props: any) => {
					if (!enableLatex) return <span>{`$${props.math}$`}</span>

					// 解码HTML实体
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

					// 解码HTML实体
					const decodedMath = props.math
						.replace(/&amp;/g, "&")
						.replace(/&quot;/g, '"')
						.replace(/&#39;/g, "'")
						.replace(/&lt;/g, "<")
						.replace(/&gt;/g, ">")

					return <KaTeX math={decodedMath} inline={false} />
				},
			},
			// 添加handledelete线的label
			span: {
				component: (props: any) => {
					// handledelete线 (GFM)
					if (props.className === "strikethrough") {
						return <del>{props.children}</del>
					}
					return <span {...props} />
				},
			},
			// handletasklist (GFM)
			li: {
				component: (props: any) => {
					// 新的多级tasklist已经在预handle阶段生成了正确的HTML结构
					// 这里只需要保持原有的行为，让HTML直接渲染
					if (props.className === "task-list-item") {
						// 对于新的tasklist，直接returnli，不再添加额外的复选框
						// 因为复选框已经在预handle阶段的HTML中生成了
						return <li {...props} />
					}
					return <li {...props} />
				},
			},
			DelightfulCitation: {
				component: DelightfulCitation,
			},
			// 添加上标和下标支持
			sup: {
				component: (props: any) => <sup {...props} />,
			},
			sub: {
				component: (props: any) => <sub {...props} />,
			},
		}
	}, [props, enableLatex])

	// 合并自定义componentconfiguration
	const customOverrides = useMemo(() => {
		if (!componentsInProps) return {}

		// 将 react-markdown format的 components 转换为 markdown-to-jsx format的 overrides
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

	// 合并所有 overrides
	const overrides = useMemo(() => {
		return {
			...baseOverrides,
			...CustomComponentService.getAllComponents(),
			...customOverrides,
		} as MarkdownToJSX.Overrides
	}, [baseOverrides, customOverrides])

	// 自定义的 markdown-to-jsx 预handlefunction
	const preprocess = useMemoizedFn((markdown: string) => {
		if (!markdown) return []

		return PreprocessService.preprocess(markdown, {
			enableLatex,
		})
	})

	// configuration markdown-to-jsx options
	const options = useMemo<MarkdownToJSX.Options>(() => {
		return {
			overrides,
			forceWrapper: true,
			disableParsingRawHTML: !allowHtml,
			// 使用update的API方式configuration预handlefunction
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

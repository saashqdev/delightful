import React from "react"

/**
 * Props for code rendering component
 */
export interface CodeRenderProps {
	/**
	 * Code type
	 */
	language?: string
	/**
	 * Code data
	 */
	data?: string
	/**
	 * Whether streaming is in progress
	 */
	isStreaming?: boolean
	/**
	 * Code className
	 */
	className?: string
}

/**
 * Props for inline code rendering component
 */
export interface InlineCodeRenderProps<D extends object = object> {
	/**
	 * Code type
	 */
	language?: string
	/**
	 * Code data
	 */
	data?: string
	/**
	 * Parsed data
	 */
	parsedData?: D
	/**
	 * Inline code className
	 */
	className?: string
	/**
	 * Whether streaming is in progress
	 */
	isStreaming?: boolean
}

/**
 * Code rendering component
 */
export interface RenderComponent<Props> {
	componentType: string
	propsParser?: (props: Props) => unknown
	matchFn?: (props: Props) => boolean
	loader: () => Promise<{
		default: React.ComponentType<Props> | React.MemoExoticComponent<React.ComponentType<Props>>
	}>
}

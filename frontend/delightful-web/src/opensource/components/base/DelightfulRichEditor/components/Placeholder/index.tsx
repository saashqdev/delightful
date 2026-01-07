import { memo } from "react"
import { useStyles } from "./styles"

export interface PlaceholderProps {
	/**
	 * Placeholder text
	 */
	placeholder: string
	/**
	 * Whether to show the placeholder
	 */
	show: boolean
}

/**
	 * Rich text editor placeholder component
 *
	 * Displays hint text when the editor is empty
	 * Shows different colors based on editor focus state
 */
const Placeholder = memo(({ placeholder, show }: PlaceholderProps) => {
	const { styles } = useStyles()

	// If the placeholder should not be shown, return null
	if (!show) return null

	return (
		<div
			className={styles.placeholder}
			data-testid="rich-editor-placeholder"
			style={{
				position: "absolute",
				top: -2 /* Fine-tuned vertical position */,
				left: "0.1em" /* Fine-tuned horizontal position */,
				padding: "0.15em" /* Add padding */,
				pointerEvents: "none",
				zIndex: 0,
				color: "#bfbfbf",
			}}
		>
			{placeholder}
		</div>
	)
})

export default Placeholder

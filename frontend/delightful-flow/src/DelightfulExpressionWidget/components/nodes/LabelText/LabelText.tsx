import { EXPRESSION_ITEM } from "@/DelightfulExpressionWidget/types"
import _ from "lodash"
import React, { useMemo } from "react"
import { useGlobalContext } from "../../../context/GlobalContext/useGlobalContext"

interface LabelTextProps {
	config: EXPRESSION_ITEM
}

// Escape HTML special characters
function escapeHtml(text: string) {
	return text.replace(/</g, "&lt;").replace(/>/g, "&gt;")
}

export function LabelTextBlock({ config }: LabelTextProps) {
	const { showMultipleLine } = useGlobalContext()

	// Escape HTML tags to avoid injection
	const escapedValue = useMemo(() => {
		let result = _.cloneDeep(config.value)
		if (!showMultipleLine) {
			result = result.replace(/\n/g, "")
		}
		result = escapeHtml(result)
		return result
	}, [config.value, showMultipleLine])

	return (
		<span
			key={config.uniqueId}
			id={config.uniqueId}
			data-id={config.uniqueId}
			data-type={config.type}
			dangerouslySetInnerHTML={{ __html: escapedValue }}
		/>
	)
}


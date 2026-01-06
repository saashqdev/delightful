/**
 * Shared helper for special render components (single select, multi select, member, etc.)
 * to handle removing referenced values
 */

import { EXPRESSION_ITEM, WithReference } from "@/DelightfulExpressionWidget/types"
import { useMemoizedFn } from "ahooks"

type UseDeleteReferenceNodeProps = {
    values: WithReference<any>[]
    setValues: React.Dispatch<React.SetStateAction<WithReference<any>[]>>
    config: EXPRESSION_ITEM
    updateFn: (val: EXPRESSION_ITEM) => void
    // Actual prop key to store values
    valueName: string
}

export default function useDeleteReferenceNode({
    values,
    setValues,
    config,
    updateFn,
    valueName
}:UseDeleteReferenceNodeProps) {
  
    // Shared helper to remove referenced values for special render blocks
	const onDeleteReferenceNode = useMemoizedFn((item: WithReference<EXPRESSION_ITEM>) => {
		const index = values.findIndex(
			(multipleItem) =>
				(multipleItem as EXPRESSION_ITEM)?.uniqueId === (item as EXPRESSION_ITEM)?.uniqueId,
		)
		if (index === -1) return
		values.splice(index, 1)
		setValues([...values])
		updateFn({
			...config,
			[valueName]: values,
		})
	})

    return {
        onDeleteReferenceNode
    }
}


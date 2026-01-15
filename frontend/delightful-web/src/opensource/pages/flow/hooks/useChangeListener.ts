/**
 * Node change listener
 */

import { useEventEmitter, useMemoizedFn } from "ahooks"
import { debounce } from "lodash-es"

type UseChangeListener = {
	saveDraft: () => Promise<void>
}

export default function useChangeListener({ saveDraft }: UseChangeListener) {
	const nodeChangeEventListener = useEventEmitter<string>()

	const handleNodeConfigChange = useMemoizedFn(
		debounce(() => {
			// console.log("Configuration changed, triggering save")
			saveDraft()
		}, 2500),
	)

	nodeChangeEventListener?.useSubscription(handleNodeConfigChange)

	return {
		nodeChangeEventListener,
	}
}

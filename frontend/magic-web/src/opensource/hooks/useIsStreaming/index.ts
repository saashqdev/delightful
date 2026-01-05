import { useDebounceEffect, useUpdate } from "ahooks"
import { useEffect, useRef } from "react"

/**
 * Check whether output is still streaming
 * @param value Current streaming value
 * @param delay Debounce delay for streaming status
 * @returns Whether streaming is ongoing
 */
export const useIsStreaming = (value: string, delay = 500) => {
	const isStreaming = useRef(true)
	const firstRender = useRef(true)
	const update = useUpdate()

	useEffect(() => {
		if (firstRender.current) {
			firstRender.current = false
			return
		}

		isStreaming.current = true
		update()
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [value])

	// Delay before marking stream as finished
	useDebounceEffect(
		() => {
			if (isStreaming.current) isStreaming.current = false
			update()
		},
		[value],
		{
			wait: delay,
		},
	)

	return { isStreaming: isStreaming.current }
}

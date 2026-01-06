import {
	AggregateAISearchCardContentV2,
	AggregateAISearchCardV2Status,
} from "@/types/chat/conversation_message"

/**
 * Append a value at a specific path in an object
 * @param object Target object
 * @param keyPath Key path array
 * @param appendValue Value to append
 * @returns Updated object
 */
export function appendObject(
	object: Record<string, any> | null | undefined,
	keyPath: string[],
	appendValue: any,
) {
	if (!object || !keyPath || keyPath.length === 0) {
		return object
	}

	let current = object

	// Traverse the path up to the penultimate key
	for (let i = 0; i < keyPath.length - 1; i++) {
		const key = keyPath[i]
		// If current key is missing/null, initialize as object or array
		if (current[key] === undefined || current[key] === null) {
			// If the next key is numeric, create array; otherwise create object
			const nextKey = keyPath[i + 1]
			current[key] = !isNaN(Number(nextKey)) ? [] : {}
		}
		current = current[key]
	}

	// Get the final key
	const finalKey = keyPath[keyPath.length - 1]

	// Handle final key value
	if (current[finalKey] === undefined || current[finalKey] === null) {
		current[finalKey] = appendValue
	} else if (Array.isArray(current[finalKey]) && Array.isArray(appendValue)) {
		// If both arrays, push elements
		current[finalKey].push(...appendValue)
	} else if (
		typeof appendValue !== "string" &&
		appendValue !== null &&
		appendValue !== undefined
	) {
		// For non-string non-empty values, assign directly
		current[finalKey] = appendValue
	} else {
		// Otherwise, concatenate (strings)
		current[finalKey] = current[finalKey] + appendValue
	}

	return object
}

/**
 * Upgrade status for Aggregate AI Search Card V2
 * @param currentStatus Current status
 * @param targetStatus Target status
 * @returns Upgraded status
 */
function upgradeAggregateAISearchCardV2Status(
	currentStatus: AggregateAISearchCardV2Status | undefined,
	targetStatus: AggregateAISearchCardV2Status,
) {
	if (!currentStatus) {
		return targetStatus
	}

	if (currentStatus > targetStatus) {
		return currentStatus
	}

	return targetStatus
}

/**
 * Update status for Aggregate AI Search Card V2
 * @param object Target object
 * @param keyPath Key path
 */
export function updateAggregateAISearchCardV2Status(
	object: AggregateAISearchCardContentV2 | undefined,
	keyPath: string,
	value: any,
) {
	if (!object || !keyPath) {
		return object
	}

	if (keyPath.startsWith("stream_options.steps_finished")) {
		switch (true) {
			// Root question search results received → mark as reading
			case keyPath.endsWith("associate_questions.question_0") && value?.finished_reason === 0:
				object.status = upgradeAggregateAISearchCardV2Status(
					object.status,
					AggregateAISearchCardV2Status.isReading,
				)
				break
			// Child question results received → mark as searching
			case keyPath.startsWith(
				"stream_options.steps_finished.associate_questions.question_",
			) && value?.finished_reason === 0:
				object.status = upgradeAggregateAISearchCardV2Status(
					object.status,
					AggregateAISearchCardV2Status.isSearching,
				)
				break
			// Reasoning content received → mark as summarizing
			case keyPath.endsWith("summary.reasoning_content") && value?.finished_reason === 0:
				object.status = upgradeAggregateAISearchCardV2Status(
					object.status,
					AggregateAISearchCardV2Status.isSummarizing,
				)
				break
			// Summary content received → mark as end
			case keyPath.endsWith("summary.content") && value?.finished_reason === 0:
				object.status = upgradeAggregateAISearchCardV2Status(
					object.status,
					AggregateAISearchCardV2Status.isEnd,
				)
				break
			default:
				break
		}
	}
	// Reasoning content streaming → mark as reasoning
	else if (
		keyPath.startsWith("summary.reasoning_content") &&
		(!object.status || object.status < AggregateAISearchCardV2Status.isReasoning)
	) {
		object.status = upgradeAggregateAISearchCardV2Status(
			object.status,
			AggregateAISearchCardV2Status.isReasoning,
		)
	}
	// Summary content streaming → mark as summarizing
	else if (
		keyPath.startsWith("summary.content") &&
		(!object.status || object.status < AggregateAISearchCardV2Status.isSummarizing)
	) {
		object.status = upgradeAggregateAISearchCardV2Status(
			object.status,
			AggregateAISearchCardV2Status.isSummarizing,
		)
	}

	return object
}

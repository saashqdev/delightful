/**
 * Checks whether changeValues only includes knowledge_config.knowledge_list[index].knowledge_type
 * Used to detect if only the knowledge base type changed
 * @param changeValues Form change values
 * @returns [meetsCondition, index (optional)]
 */
export function isOnlyKnowledgeTypeChange(changeValues: any): [boolean, number?] {
	// 1. Verify it only contains the knowledge_config property
	const keys = Object.keys(changeValues)
	if (keys.length !== 1 || keys[0] !== "knowledge_config") {
		return [false]
	}

	const { knowledge_config } = changeValues

	// 2. Verify knowledge_config only contains the knowledge_list property
	const configKeys = Object.keys(knowledge_config)
	if (configKeys.length !== 1 || configKeys[0] !== "knowledge_list") {
		return [false]
	}

	const { knowledge_list } = knowledge_config

	// 3. Ensure knowledge_list is an array
	if (!Array.isArray(knowledge_list)) {
		return [false]
	}

	// 4. Find the object containing only knowledge_type and its index
	let foundIndex = -1
	let foundCount = 0

	knowledge_list.forEach((item, index) => {
		// Skip empty items
		if (!item) return

		// Check the item is an object
		if (typeof item !== "object") return

		const itemKeys = Object.keys(item)
		// Verify the item has only one property and it's knowledge_type
		if (itemKeys.length === 1 && itemKeys[0] === "knowledge_type") {
			foundIndex = index
			foundCount++
		}
	})

	// 5. Ensure exactly one item meets the condition
	if (foundCount === 1 && foundIndex !== -1) {
		return [true, foundIndex]
	}

	return [false]
}

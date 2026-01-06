// Check whether the condition matches the default structure
export const isEqualToDefaultCondition = (condition: Record<string, any>) => {
	if (!Reflect.has(condition, "ops") || !Reflect.has(condition, "children")) return false
	if (condition.children.length !== 1) return false
	const firstCondition = condition.children[0]
	if (firstCondition.type) return true
	return false
}


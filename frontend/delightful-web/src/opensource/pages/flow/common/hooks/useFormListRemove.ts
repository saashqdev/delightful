import { get, set } from "lodash-es"
import type { FormInstance } from "antd"
import { useNodeConfigActions } from "@delightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@delightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useMemoizedFn } from "ahooks"

/**
 * Hook for handling Form.List item removal
 * Bypasses Form.List's remove method, directly manipulates array and updates form values
 */
export default function useFormListRemove() {
	const { currentNode } = useCurrentNode()
	const { updateNodeConfig } = useNodeConfigActions()

	/**
	 * Remove item from Form.List
	 * @param form Form instance
	 * @param fieldName Field name array, e.g. ['filters'] or ['knowledge_config', 'knowledge_list']
	 * @param index Index to remove
	 */
	const removeFormListItem = useMemoizedFn(
		(form: FormInstance, fieldName: string[], index: number) => {
			// First get current field value (array)
			const currentList = [...(get(form.getFieldsValue(), fieldName) || [])]

			// Remove item at specified index
			currentList.splice(index, 1)

			// Create form value object to be set
			const fieldValueObj: any = {}
			set(fieldValueObj, fieldName, currentList)

			// Update form values
			form.setFieldsValue(fieldValueObj)

			// If current node exists, update node configuration
			if (currentNode) {
				// Build path array, add params prefix
				const paramsPath = ["params", ...fieldName]

				// Update current node parameters
				set(currentNode, paramsPath, currentList)

				// Update node configuration
				updateNodeConfig({ ...currentNode })
			}
		},
	)

	return {
		removeFormListItem,
	}
}





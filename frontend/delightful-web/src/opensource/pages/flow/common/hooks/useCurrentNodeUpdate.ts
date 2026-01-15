import type { FormInstance } from "antd"
import { useFlowData } from "@bedelightful/delightful-flow/dist/DelightfulFlow/context/FlowContext/useFlow"
import { useCurrentNode } from "@bedelightful/delightful-flow/dist/DelightfulFlow/nodes/common/context/CurrentNode/useCurrentNode"
import { useUpdateEffect } from "ahooks"

type UseCurrentNodeUpdateProps = {
	form: FormInstance<any>
	initialValues?: any
}

export default function useCurrentNodeUpdate({ form }: UseCurrentNodeUpdateProps) {
	const { flow } = useFlowData()

	const { currentNode } = useCurrentNode()

	useUpdateEffect(() => {
		form.setFieldsValue({
			...currentNode?.params,
		})
	}, [flow, currentNode])
}

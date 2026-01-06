import { FormItemType } from "@/DelightfulExpressionWidget/types"
import { nodeManager } from "@/DelightfulFlow"
import i18next from "i18next"


/** Get the default constant references for boolean values */
export const getDefaultBooleanConstantSource = () => {

	const variableNodeType = nodeManager.variableNodeTypes?.[0]

	return [{
		"title": i18next.t("common.constants", { ns: "delightfulFlow" }),
		"key": "",
		"nodeId": "Wrapper",
		"nodeType": variableNodeType,
		"type": "",
		"isRoot": true,
		"children": [
			{
				"title": i18next.t("common.real", { ns: "delightfulFlow" }),
				"key": "true",
				"nodeId": "",
				"nodeType": variableNodeType,
				"type": FormItemType.Boolean,
				"isRoot": false,
				"children": [],
				"isConstant": true
			},
			{
				"title": i18next.t("common.artifact", { ns: "delightfulFlow" }),
				"key": "false",
				"nodeId": "",
				"nodeType": variableNodeType,
				"type": FormItemType.Boolean,
				"isRoot": false,
				"children": [],
				"isConstant": true
			}
		]
	}]
}




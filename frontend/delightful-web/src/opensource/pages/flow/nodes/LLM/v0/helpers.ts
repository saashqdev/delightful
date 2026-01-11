import { TFunction } from "i18next";

/** Knowledge data type */
export enum KnowledgeType {
	/** Knowledge Base */
	KnowledgeDatabase = 2,
	/** Cloud Document */
	Document = 3,
}


export const getDefaultKnowledge = (isCommercial: boolean) => {

	return {
		knowledge_code: "",
		knowledge_type: isCommercial ? KnowledgeType.KnowledgeDatabase : "",
		business_id: "",
		name: "",
		description: "",
	}
}



export const getKnowledgeTypeOptions = (t: TFunction, isCommercial: boolean) => [
	...(isCommercial ? [{
		label: t("common.knowledgeDatabase", { ns: "flow" }),
		value: KnowledgeType.KnowledgeDatabase,
	}] : [])
]



export const getLLMRoleConstantOptions = () => {
	return [
		{
			title: "Constant",
			key: "",
			nodeId: "Wrapper",
			nodeType: "21",
			type: "",
			isRoot: true,
			children: [
				{
					title: "User",
					key: "user",
					nodeId: "",
					nodeType: "21",
					type: "string",
					isRoot: false,
					children: [],
					isConstant: true,
				},
				{
					title: "System",
					key: "system",
					nodeId: "",
					nodeType: "21",
					type: "string",
					isRoot: false,
					children: [],
					isConstant: true,
				},
			],
		},
	]
}

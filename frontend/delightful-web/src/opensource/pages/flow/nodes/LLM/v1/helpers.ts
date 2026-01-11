import { TFunction } from "i18next"

/** Knowledge data types */
export enum KnowledgeType {
	/** User-created knowledge base */
	UserKnowledgeDatabase = 1,
	/** Teamshare knowledge base */
	TeamshareKnowledgeDatabase = 2,
	/** Cloud Document */
	Document = 3,
}

export const getDefaultKnowledge = (isCommercial: boolean) => {
	return {
		knowledge_code: "",
		knowledge_type: isCommercial ? KnowledgeType.TeamshareKnowledgeDatabase : "",
		business_id: "",
		name: "",
		description: "",
	}
}

export const getKnowledgeTypeOptions = (t: TFunction, isCommercial: boolean) => [
	...(isCommercial
		? [
				{
					label: t("common.teamshareKnowledgeDatabase", { ns: "flow" }),
					value: KnowledgeType.TeamshareKnowledgeDatabase,
				},
				{
					label: t("common.userKnowledgeDatabase", { ns: "flow" }),
					value: KnowledgeType.UserKnowledgeDatabase,
				},
		  ]
		: [
				{
					label: t("common.userKnowledgeDatabase", { ns: "flow" }),
					value: KnowledgeType.UserKnowledgeDatabase,
				},
		  ]),
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






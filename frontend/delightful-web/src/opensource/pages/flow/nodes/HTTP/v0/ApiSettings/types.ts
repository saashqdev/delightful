import type { HTTP } from "@/types/flow"
import type { ExpressionSource } from "@bedelightful/delightful-flow/dist/DelightfulExpressionWidget/types"

/** Parameter configuration tab bar type */
export enum ArgsTabType {
	Query = "1",
	Body = "2",
	Headers = "3",
}

export interface ApiSettingsProps {
	expressionSource: ExpressionSource
	apiSettings?: HTTP.Api
	setApiSettings?: (value: HTTP.Api) => void
	hasPath?: boolean
}

export interface ApiSettingsInstance {
	// Get the internal value of the API component
	getValue: () => HTTP.Api
	// Set the internal value of the API component
	setValue(changePath: string[], val: any, activeKey?: ArgsTabType): void
}

export default {}

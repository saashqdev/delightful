import { NodeTestConfig } from "@/DelightfulFlow/context/NodeTesingContext/Context";
import { TestingResultRow } from "./useTesting";
import _ from "lodash";


/** Transform testing result entries into a list */
export const transformToList = (testConfig: NodeTestConfig, key = 'output' as keyof NodeTestConfig): TestingResultRow[] => {
    if(key === 'input' && !testConfig?.success) {
        return []
    }
	// If the run failed, surface the error message
	if (!testConfig?.success) {
		return [
			{
				key: "error_message",
				value: testConfig?.error_message || "",
			},
		]
	}
	return (
		Object.entries(testConfig?.[key] || {}).map(([key, value]) => {
			return {
				key,
				value: `${_.isObject(value) ? JSON.stringify(value) : value}`,
			}
		}) || []
	)
}

/** Build the debug log list */
export const generateDebugLogList = (testConfig: NodeTestConfig): TestingResultRow[] => {
	return (
		Object.entries(testConfig?.debug_log || {}).map(([key, value]) => {
			return {
				key,
				value: `${_.isObject(value) ? JSON.stringify(value) : value}`,
			}
		}) || []
	)
}

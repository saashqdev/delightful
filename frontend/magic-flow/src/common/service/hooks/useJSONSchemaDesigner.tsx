import { Common } from "@/MagicConditionEdit/types/common"
import { useMemoizedFn } from "ahooks"
import type { AxiosPromise } from "axios"
import { RequestInstance } from ".."

export type JSONSchemaDesignerServiceType = {
	getDynamicObject: () => AxiosPromise<any>
	getDynamicOptions: () => AxiosPromise<any>
	getOptionsDynamicDefault: () => AxiosPromise<Common.API_SETTINGS>
	getObjectDynamicDefault: () => AxiosPromise<Common.API_SETTINGS>
}

export default ({ request }: { request: RequestInstance }) => {
	/**
	 * @description Fetch dynamic field data sources for object types
	 */
	const getDynamicObject = useMemoizedFn(() => {
		return request({
			url: "/v1/object-template",
			method: "POST",
		})
	})

	/**
	 * @description Fetch dynamic field data sources for string types
	 */
	const getDynamicOptions = useMemoizedFn(() => {
		return request({
			url: "/v1/options-template",
			method: "POST",
		})
	})

	// Fetch dynamic field creation template for options
	const getOptionsDynamicDefault = useMemoizedFn(() => {
		return request({
			url: "/v1/dynamic-fields-options-api-template",
			method: "POST",
		})
	})

	// Fetch dynamic field creation template for objects
	const getObjectDynamicDefault = useMemoizedFn(() => {
		return request({
			url: "/v1/dynamic-fields-objects-api-template",
			method: "POST",
		})
	})

	return {
		getDynamicObject,
		getDynamicOptions,
		getOptionsDynamicDefault,
		getObjectDynamicDefault,
	}
}

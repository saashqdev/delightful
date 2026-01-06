import { createContext, useContext } from "react"
import { JSONSchemaDesignerServiceType } from "./hooks/useJSONSchemaDesigner"

const ServiceStore = {
	/**
	 * @description JSON Schema designer
	 * @type {ReturnType<typeof import('./hooks/useJSONSchemaDesigner').default>}
	 */
	JSONSchemaDesignerService: {} as JSONSchemaDesignerServiceType,
}

export const ServiceContext = createContext(ServiceStore)

export const useService = () => {
	return useContext(ServiceContext)
}


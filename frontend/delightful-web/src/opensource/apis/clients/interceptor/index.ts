import type { Container } from "@/opensource/services/ServiceContainer"
import delightfulClient from "../delightful"
import {
	generateInvalidOrgResInterceptor,
	generateUnauthorizedResInterceptor,
	generateSuccessResInterceptor,
} from "./interceptor"

export function initialApi(service: Container) {
	delightfulClient.addResponseInterceptor(generateUnauthorizedResInterceptor(service))
	delightfulClient.addResponseInterceptor(generateInvalidOrgResInterceptor(service))
	delightfulClient.addResponseInterceptor(generateSuccessResInterceptor())
}

export {
	generateInvalidOrgResInterceptor,
	generateUnauthorizedResInterceptor,
	generateSuccessResInterceptor,
}

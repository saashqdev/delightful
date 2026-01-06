import { type Container, ServiceContainer } from "@/opensource/services/ServiceContainer"
import { UserService } from "@/opensource/services/user/UserService"
import * as apis from "@/apis"
import { LoginService } from "@/opensource/services/user/LoginService"
import { ConfigService } from "@/opensource/services/config/ConfigService"
import { initialApi } from "@/apis/clients/interceptor"
import { FlowService } from "./flow"

export function initializeApp() {
	const container = new ServiceContainer()

	// Delay API initialization until services are actually created
	container.registerFactory<UserService>(
		"userService",
		(c: Container) => new UserService(apis as any, c),
	)

	container.registerFactory<LoginService>(
		"loginService",
		(c: Container) => new LoginService(apis as any, c),
	)

	container.registerFactory<ConfigService>("configService", () => new ConfigService(apis as any))

	container.registerFactory<FlowService>("flowService", () => new FlowService(apis as any))

	// Get service instances - container handles async factory internally
	const userService = container.get<UserService>("userService")
	const loginService = container.get<LoginService>("loginService")
	const configService = container.get<ConfigService>("configService")
	const flowService = container.get<FlowService>("flowService")

	initialApi(container)

	// Return service instances for application use
	return {
		userService,
		loginService,
		configService,
		flowService,
	}
}

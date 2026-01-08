import { UserService } from "@/opensource/services/user/UserService"
import { LoginService } from "@/opensource/services/user/LoginService"
import * as globalApis from "@/apis"
import { type Container, ServiceContainer } from "@/opensource/services/ServiceContainer"
import { UserApi as openSourceUserApi, CommonApi as openSourceCommonApi } from "@/apis"
import { ConfigService } from "@/opensource/services/config/ConfigService"
/**
 * @description Create service instance (when under a completely new React root node instance, need to re-instantiate the business layer)
 */
function createService() {
	const UserApi = openSourceUserApi
	const CommonApi = openSourceCommonApi

	const apis = {
		...globalApis,
		UserApi,
		CommonApi,
	}

	const container = new ServiceContainer()

	// Defer API initialization to when services are actually created
	container.registerFactory<UserService>(
		"userService",
		(c: Container) => new UserService(apis, c),
	)

	container.registerFactory<LoginService>(
		"loginService",
		(c: Container) => new LoginService(apis, c),
	)

	container.registerFactory<ConfigService>("configService", () => new ConfigService(apis as any))

	// Get service instances - the container will handle async factory cases internally
	const loginService = container.get<LoginService>("loginService")
	const userService = container.get<UserService>("userService")
	const configService = container.get<ConfigService>("configService")

	return { loginService, userService, configService }
}

export const { loginService, userService, configService } = createService()

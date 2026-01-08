import { genRequestUrl } from "@/utils/http"
import type {
	AuthRequestParams,
	ResourceTypes,
} from "@/opensource/pages/flow/components/AuthControlButton/types"
import type { User } from "@/types/user"
import { RequestUrl } from "../constant"
import { isCommercial } from "@/utils/env"
import type { HttpClient } from "../core/HttpClient"

export const generateAuthApi = (fetch: HttpClient) => ({
	/** Update resource access */
	updateResourceAccess(params: AuthRequestParams) {
		return fetch.post<null>(genRequestUrl(RequestUrl.updateResourceAccess), params)
	},

	/** Query resource access */
	getResourceAccess(resource_type: ResourceTypes, resource_id: string) {
		return fetch.get<AuthRequestParams>(
			genRequestUrl(
				RequestUrl.getResourceAccess,
				{},
				{
					resource_type,
					resource_id,
				},
			),
		)
	},

	/**
	 * @description After login, need to bind authorization code + authorization in delightful service
	 * @param {string} authorization User token
	 * @param {string} authCode Login authorization code (useful for private deployment, pass empty string for non-private)
	 * @param {string} thirdPlatformOrganizationCode Third-party platform organization code returned when creating organization for the first time, backend synchronizes user account information
	 */
	bindDelightfulAuthorization(
		authorization: string,
		authCode: string,
		thirdPlatformOrganizationCode?: string,
	) {
		return fetch.get<Array<User.DelightfulOrganization>>(
			genRequestUrl(
				RequestUrl.bindDelightfulAuthorization,
				{},
				{
					login_code: authCode || "",
					authorization,
				},
			),
			{
				headers: {
					authorization,
					["Organization-Code"]: thirdPlatformOrganizationCode || "",
				},
				enableErrorMessagePrompt: false,
				enableRequestUnion: true,
			},
		)
	},

	/**
	 * @description Get the environment code to which the current account belongs
	 */
	getAccountDeployCode() {
		if (!isCommercial()) {
			return { login_code: "" }
		}
		return fetch.get<{ login_code: string }>(genRequestUrl(RequestUrl.getDeploymentCode), {
			enableRequestUnion: true,
		})
	},

	/**
	 * @description Exchange user token for one-time temporary authorization token
	 */
	getTempTokenFromUserToken() {
		return fetch.get<{ temp_token: string }>(genRequestUrl(RequestUrl.userTokenToTempToken))
	},

	/**
	 * @description Exchange temporary authorization token for user token
	 */
	getUserTokenFromTempToken(tempToken: string) {
		return fetch.get<{ teamshare_token: string }>(
			genRequestUrl(RequestUrl.tempTokenToUserToken, {
				tempToken,
			}),
			{
				enableRequestUnion: true,
			},
		)
	},

	/**
	 * @description Get admin console user information
	 * @returns {Promise<User.UserInfo>}
	 */
	getAdminPermission() {
		return fetch.get<{ is_admin: boolean }>(genRequestUrl(RequestUrl.getAdminPermission), {
			enableRequestUnion: true,
		})
	},
})

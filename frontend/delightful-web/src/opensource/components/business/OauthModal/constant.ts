export const enum OauthScope {
	OPENID = "openid",
	PROFILE = "profile",
	PHONE = "phone",
	EMAIL = "email",
	ADDRESS = "address",
}

// export const ScopeLabel = {
// 	[OauthScope.OPENID]: i18next.t("oauth.openid", { ns: "interface" }),
// 	[OauthScope.PROFILE]: i18next.t("oauth.profile", { ns: "interface" }),
// 	[OauthScope.PHONE]: i18next.t("oauth.phone", { ns: "interface" }),
// 	[OauthScope.EMAIL]: i18next.t("oauth.email", { ns: "interface" }),
// 	[OauthScope.ADDRESS]: i18next.t("oauth.address", { ns: "interface" }),
// }

export const ScopeLabel = {
	[OauthScope.OPENID]: "Get your account",
	[OauthScope.PROFILE]: "Get your basic information (name/phone/department/etc.)",
	[OauthScope.PHONE]: "Get your phone number",
	[OauthScope.EMAIL]: "Get your email",
	[OauthScope.ADDRESS]: "Get your address",
}

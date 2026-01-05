/**
 * Verification code types
 */
export const enum VerificationCode {
	/** Change password */
	ChangePassword = "change_password",
	/** Change phone */
	ChangePhone = "change_phone",
	/** Bind phone */
	BindPhone = "bind_phone",
	/** Account registration */
	RegisterAccount = "register_account",
	/** Account login activation */
	AccountLoginActive = "account_login_active",
	/** Account registration activation */
	AccountRegisterActive = "account_register_active",
	/** Account login */
	AccountLogin = "account_login",
	/** Account login binds third-party platform */
	AccountLoginBindThirdPlatform = "account_login_bind_third_platform",
	/** Device logout */
	DeviceLogout = "device_logout",
}

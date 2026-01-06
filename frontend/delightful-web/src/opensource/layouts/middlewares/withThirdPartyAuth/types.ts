export interface ThirdPartyLoginStrategy {
	getAuthCode(deployCode: string): Promise<string> // Returns user credentials or user ID
}

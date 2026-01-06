/** Language helpers */
export const languageHelper = {
	/**
	 * @description Internationalization locale transform: other styles => delightful style (zzZZ/zz-ZZ -> zz_ZZ; case length is not constrained)
	 * @param {string} lang
	 * @return zh_CN
	 */
	transform: (lang: string): string => {
		return lang.replace(/([a-z]{2})([-]?)([A-Z]{2})/g, "$1_$3")
	},
	/**
	 * @description Internationalization locale transform: delightful style => other styles (zz_ZZ/zz-ZZ -> zzZZ; case length is not constrained)
	 * @param {string} lang
	 * @return zhCN
	 */
	unTransform: (lang: string): string => {
		return lang.replace(/([a-z]{2})([_/-]?)([A-Z]{2})/g, "$1$3")
	},
}


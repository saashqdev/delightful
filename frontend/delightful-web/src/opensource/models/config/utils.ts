/** Language helper */
export const languageHelper = {
	/**
	 * @description I18n code conversion: other standards => delightful standard (zzZZ/zz-ZZ unified to zz_ZZ, case-insensitive length)
	 * @param {string} lang
	 * @return zh_CN
	 */
	transform: (lang: string): string => {
		return lang.replace(/([a-z]{2})([-]?)([A-Z]{2})/g, "$1_$3")
	},
	/**
	 * @description I18n code conversion: delightful standard => other standards (zz_ZZ/zz-ZZ unified to zzZZ, case-insensitive length)
	 * @param {string} lang
	 * @return zhCN
	 */
	unTransform: (lang: string): string => {
		return lang.replace(/([a-z]{2})([_/-]?)([A-Z]{2})/g, "$1$3")
	},
}

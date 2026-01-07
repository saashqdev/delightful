/**
 * @deprecated Application configuration (internationalization language, theme, area code)
 */
export namespace Config {
	/** Global internationalization language options */
	export interface LanguageOption {
		/** Current language identifier */
		locale: string
		/** Current language name */
		name: string
		/** Expressions of each language used for the current language */
		translations: Record<string, string>
	}

	/** Global area code options */
	export interface AreaCodeOption {
		/** Current language identifier */
		locale: string
		/** Current area code identifier */
		code: string
		/** Current language name */
		name: string
		/** Expressions of each language used for the current language */
		translations: Record<string, string>
	}

	/** Global language module */
	export interface Language {
		/** Current language */
		language: string
		/** Local language list */
		languages: Array<LanguageOption>
		/** Set current language */
		setLanguage: (language: string) => void
		/** Set language list */
		setLanguages: (languages: Array<LanguageOption>) => void
		/** Area code (telephone number area identifier) */
		areaCodes: Array<AreaCodeOption>
		setAreaCodes: (areaCodes: Array<AreaCodeOption>) => void
		/** Get global internationalization configuration */
		// useFetchOptions: () => void
		/** Internationalization language instance */
		// instance: i18n
	}
}

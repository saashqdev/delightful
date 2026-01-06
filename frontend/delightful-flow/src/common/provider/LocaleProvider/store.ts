import { create } from "zustand"
import { createJSONStorage, persist } from "zustand/middleware"
import { platformKey } from "@/common/utils/storage"

/** Global internationalization language option */
interface GlobalLanguageOption {
	/** Language code */
	locale: string
	/** Language display name */
	name: string
	/** Translations for this locale */
	translations: Record<string, string>
}

/** Global area code option */
interface AreaCodeOption {
	/** Language code */
	locale: string
	/** Area code identifier */
	code: string
	/** Language display name */
	name: string
	/** Translations for this locale */
	translations: Record<string, string>
}

/** Global language store */
export interface GlobalLanguage {
	/** Current language */
	language: string
	/** Local language list */
	languages: Array<GlobalLanguageOption>
	/** Set current language */
	setLanguage: (language: string) => void
	/** Set language list */
	setLanguages: (languages: Array<GlobalLanguageOption>) => void
	/** Area codes (phone dialing codes) */
	areaCodes: Array<AreaCodeOption>
	setAreaCodes: (areaCodes: Array<AreaCodeOption>) => void
	/** Get global internationalization config */
	// useFetchOptions: () => void
	/** Internationalization instance */
	// instance: i18n
}

// Explicitly declare store type
export type GlobalLanguageStoreType = ReturnType<typeof createStore>

// Create store function
const createStore = () => {
	return create<GlobalLanguage>()(
		persist(
			(set) => ({
				language: "en_US",
				languages: [],
				areaCodes: [],
				setLanguage: (language) => {
					set({ language })
				},
				setLanguages: (languages) => {
					set({
						languages: languages.map((lang) => ({
							name: lang.name,
							locale: lang.locale,
							translations: lang?.translations,
						})),
					})
				},
				setAreaCodes: (areaCodes) => {
					set({
						areaCodes: areaCodes.map((item) => ({
							name: item.name,
							code: item.code,
							locale: item.locale,
							translations: item?.translations,
						})),
					})
				},
			}),
			{
				name: platformKey("store:internationalized"),
				partialize: (state) => ({
					language: state.language,
					languages: state.languages,
					areaCodes: state.areaCodes,
				}),
				storage: createJSONStorage(() => localStorage),
			}
		)
	)
}

// Create and export store instance
export const store = createStore()


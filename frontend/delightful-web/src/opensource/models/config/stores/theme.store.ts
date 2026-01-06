import { makeAutoObservable } from "mobx"
import type { ThemeMode } from "antd-style"

/**
 * @description Theme configuration store, manages in-memory state
 */
export class ThemeStore {
	
	// Set default value
	theme: ThemeMode = "auto"
	
	constructor() {
		makeAutoObservable(this)
	}
	
	/**
	 * @description Set theme
	 */
	setTheme(theme: ThemeMode) {
		this.theme = theme
	}
	
}

export const themeStore = new ThemeStore()

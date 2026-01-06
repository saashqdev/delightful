// types/electron.d.ts
import type { DesktopCapturerSource, Display, IpcRenderer } from "electron"
import type { FileTypeResult } from "file-type"
import type { ThemeMode } from "antd-style"

/** Media window module */
export namespace DelightfulMediaElectron {
	/** Media payload */
	export interface MediaInfo {
		/** Media data */
		media: Array<string>
		/** Current preview index */
		index?: number
	}

	/** Electron media window API */
	export interface DelightfulMediaElectronAPI {
		media: {
			getMedia: () => Promise<MediaInfo>
		}
	}
}

export namespace DelightfulScreenshotElectron {
	/** Electron global screenshot window API result */
	interface GetScreenResult {
		/** Windows */
		sources: Array<DesktopCapturerSource>
		/** Displays */
		displays: Array<Display>
	}

	/** Electron global screenshot window API */
	export interface DelightfulScreenshotElectronAPI {
		getScreen: () => GetScreenResult
	}
}

/** Global search module */
export namespace DelightfulSearchElectron {
	/** Electron global search window API */
	export interface DelightfulSearchElectronAPI {}
}

/** Global main application module */
export namespace DelightfulElectron {
	export interface ShortcutConfig {
		/** Global search shortcut */
		globalSearch?: null | Array<number>
	}

	/** Application info */
	export interface ApplicationInfo {
		name: string
		archKind: string
		lastModified: string
		obtainedFrom: string
		path: string
		signedBy: Array<string>
		version: string
		icon: string
	}

	interface MacInfo {
		/** Application name */
		appName: string
		/** Application version */
		appVersion: string
		/** Host username */
		hostname: string
		/** Device unique ID */
		mac: string
		/** Device info */
		systemVersion: string
	}

	/** Electron main application window */
	export interface DelightfulElectronAPI {
		/** Environment module */
		env: {
			/** Running on Linux */
			isLinux: () => boolean
			/** Running on macOS */
			isMacOS: () => boolean
			/** Running on Windows */
			isWindows: () => boolean
			/** Running inside Electron */
			isElectron: () => boolean
		}
		/** System module */
		os: {
			getSystemInfo: () => Promise<MacInfo>
			getScreenInfo: () => Promise<{
				source: Array<DesktopCapturerSource>
				displays: Array<Display>
			}>
		}
		/** Application module */
		app: {
			queryApplications: () => Promise<Array<ApplicationInfo>>
			openApplication: (appInfo: ApplicationInfo) => void
		}
		/** Global configuration module */
		config: {
			/** Shortcut management */
			globalShortcut: {
				/** Get all registered shortcut configs */
				getRegisterAll: () => Promise<Array<ConfigModal>>
				/** Register all shortcut bindings */
				register: (config: DelightfulElectron.ShortcutConfig) => void
				/** Unregister a single shortcut binding */
				unregister: (name: null | Array<number>) => void
				/** Remove all shortcut bindings */
				unregisterAll: () => void
			}
		}
		/** Media module */
		media: {
			/** Media preview */
			previewMedia: (params: {
				messageId: string | undefined
				conversationId: string | undefined
				fileId?: string
				fileName?: string
				fileSize?: number
				index?: number
				url?: string
				ext?:
					| Partial<FileTypeResult>
					| { ext?: "svg"; mime?: "image/svg+xml" }
					| { ext?: string; mime?: string }
				/** Display in standalone window */
				standalone?: boolean
			}) => void
		}
		/** Internationalization module */
		language: {
			/** Subscribe to language changes */
			subscribe: (callback: (lang: string) => void) => () => void
			getLanguage: () => Promise<string>
			setLanguage: (lang: string) => void
		}
		/** Theme module */
		theme: {
			/** Subscribe to theme changes */
			subscribe: (callback: (theme: ThemeMode) => void) => () => void
			getTheme: () => Promise<string>
			setTheme: (theme: string) => void
		}
		/** Global extension capabilities module */
		extend: {}
		/** Logging module */
		log: {
			report: (log: string | Array<any> | Record<any, any> | Error) => Promise<void>
			query: (query?: DelightfulCore.LogsQuery) => Promise<Array<DelightfulCore.Log>>
		}
		/** Window view module */
		view: {
			/** On show callback */
			onShow: (callback: () => void) => () => void
			/** On hide callback */
			onHide: (callback: () => void) => () => void
			/** Minimize window */
			minimize: () => void
			/** Maximize window */
			maximize: () => void
			/** Close window */
			close: () => void
			/** Exit full screen */
			endFullScreen: () => void
			/** Show window */
			show: () => void
			/** Hide window */
			hide: () => void
			/** Set window size */
			setViewSize: (size: { height: number; width: number }) => void
			/** Set window position */
			setViewPosition: (position: { x: number; y: number }) => void
		}
	}
}

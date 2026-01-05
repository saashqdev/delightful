import "./env"
import "./theme"
import "./assets"
import "./react-router"
import type {
	MagicElectron,
	MagicMediaElectron,
	MagicScreenshotElectron,
	MagicSearchElectron,
} from "./electron"
import type { DingTalk } from "./dingTalk"
import type { Lark } from "./lark"

declare global {
	interface Window {
		/** Main application window */
		magic: MagicElectron.MagicElectronAPI
		/** Media window */
		magicMedia: MagicMediaElectron.MagicMediaElectronAPI
		/** Global screenshot window */
		magicScreenshot: MagicScreenshotElectron.MagicScreenshotElectronAPI
		/** Global search window */
		magicSearch: MagicSearchElectron.MagicSearchElectronAPI

		// Temporarily set developer mode
		setDebug?: (debug: boolean) => void

		/**
		 * Third-party SDK API
		 */
		/** DingTalk QR code login API */
		DTFrameLogin: DingTalk.DTFrameLoginAPI
		/** Lark QR code login API */
		QRLogin: Lark.LarkQRLogin
		/** Lark SSO SDK */
		tt: Lark.LarkSDK
	}
}

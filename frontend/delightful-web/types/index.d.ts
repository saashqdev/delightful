import "./env"
import "./theme"
import "./assets"
import "./react-router"
import type {
	DelightfulElectron,
	DelightfulMediaElectron,
	DelightfulScreenshotElectron,
	DelightfulSearchElectron,
} from "./electron"
import type { DingTalk } from "./dingTalk"
import type { Lark } from "./lark"

declare global {
	interface Window {
		/** Main application window */
		magic: DelightfulElectron.DelightfulElectronAPI
		/** Media window */
		magicMedia: DelightfulMediaElectron.DelightfulMediaElectronAPI
		/** Global screenshot window */
		magicScreenshot: DelightfulScreenshotElectron.DelightfulScreenshotElectronAPI
		/** Global search window */
		magicSearch: DelightfulSearchElectron.DelightfulSearchElectronAPI

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

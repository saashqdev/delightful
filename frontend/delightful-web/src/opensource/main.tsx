import "./index.css"
// Initialize emoji cache
import "@/opensource/components/base/DelightfulEmojiPanel/cache"
import { enableMapSet } from "immer"
// import ReactDom from "react-dom"
import { createRoot } from "react-dom/client"
import { StrictMode } from "react"
import App from "./App"
import "@/utils/polyfill"

enableMapSet()

const root = createRoot(document.getElementById("root")!)
root.render(
	<StrictMode>
		<App />
	</StrictMode>,
)

postMessage({ payload: "removeLoading" }, "*")

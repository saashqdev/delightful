/**
 * Code editor related helper methods
 */
import { loader } from "@monaco-editor/react"
import * as monaco from "monaco-editor"

/**
 * Register code editor related functionality
 */
export default function registerEditor() {
	loader.config({
        monaco
	})

	loader.init()
}

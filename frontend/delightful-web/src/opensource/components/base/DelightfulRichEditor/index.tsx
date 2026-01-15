import type { Content, Editor, UseEditorOptions, EditorEvents } from "@tiptap/react"
import { useEditor, EditorContent } from "@tiptap/react"
import StarterKit from "@tiptap/starter-kit"
import Highlight from "@tiptap/extension-highlight"
import TextAlign from "@tiptap/extension-text-align"
import TextStyle from "@tiptap/extension-text-style"
import FontSize from "tiptap-extension-font-size"
import type { HTMLAttributes } from "react"
import {
	forwardRef,
	memo,
	useImperativeHandle,
	useMemo,
	useRef,
	useCallback,
	useEffect,
	useState,
} from "react"
import { useTranslation } from "react-i18next"
import { omit } from "lodash-es"
import { nodePasteRule, Extension } from "@tiptap/core"
import HardBlock from "@tiptap/extension-hard-break"
import type { EditorView } from "@tiptap/pm/view"
import type { Slice } from "@tiptap/pm/model"
import { history, undo, redo } from "@tiptap/pm/history"
import { Image } from "./extensions/image"
import ToolBar from "./components/ToolBar"
import useStyles from "./styles"
import DelightfulEmojiNodeExtension from "./extensions/delightfulEmoji"
import { fileToBase64 } from "./utils"
import { FileHandler } from "./extensions/file-handler"
import type { FileError } from "./utils"
import Placeholder from "./components/Placeholder"
import { message } from "antd"

// Custom history management extension to ensure proper undo/redo handling
const CustomHistory = Extension.create({
	name: "customHistory",

	addProseMirrorPlugins() {
		return [
			history({
				// Increase history depth
				depth: 100,
				// Increase delay for new transaction groups to ensure smoother history
				// Increased from 300ms to 500ms to better merge consecutive operations
				newGroupDelay: 500,
			}),
		]
	},

	// Add keyboard shortcuts for undo/redo
	addKeyboardShortcuts() {
		return {
			// Undo - Ctrl+Z/Cmd+Z
			"Mod-z": ({ editor }) => {
				if (editor.can().undo()) {
					// Use enhanced undo command
					editor.commands.first(({ commands }) => [
						// First try normal undo
						() => commands.undo(),
						// Check editor state after undo
						() => {
							// Get current document state
							const { isEmpty } = editor
							const content = editor.getJSON()

							// If editor is empty or has a single node after undo, it may be an intermediate state
							if (isEmpty || (content.content && content.content.length <= 1)) {
								// Try undoing again (if possible) to skip intermediate states
								setTimeout(() => {
									if (editor.can().undo()) {
										commands.undo()
									}
								}, 0)
							}
							return true
						},
					])
					return true
				}
				return false
			},
			// Redo - Ctrl+Y/Cmd+Shift+Z
			"Mod-y": ({ editor }) => {
				if (editor.can().redo()) {
					// Use enhanced redo command
					editor.commands.first(({ commands }) => [
						// First try normal redo
						() => commands.redo(),
						// Check editor state after redo
						() => {
							// Get current document state
							const { isEmpty } = editor
							const content = editor.getJSON()

							// If editor is empty or has a single node after redo, it may be an intermediate state
							if (isEmpty || (content.content && content.content.length <= 1)) {
								// Try redoing again (if possible) to skip intermediate states
								setTimeout(() => {
									if (editor.can().redo()) {
										commands.redo()
									}
								}, 0)
							}
							return true
						},
					])
					return true
				}
				return false
			},
			// Redo - Ctrl+Shift+Z/Cmd+Shift+Z (macOS style)
			"Mod-Shift-z": ({ editor }) => {
				if (editor.can().redo()) {
					// Use enhanced redo command
					editor.commands.first(({ commands }) => [
						// First try normal redo
						() => commands.redo(),
						// Check editor state after redo
						() => {
							// Get current document state
							const { isEmpty } = editor
							const content = editor.getJSON()

							// If editor is empty or has a single node after redo, it may be an intermediate state
							if (isEmpty || (content.content && content.content.length <= 1)) {
								// Try redoing again (if possible) to skip intermediate states
								setTimeout(() => {
									if (editor.can().redo()) {
										commands.redo()
									}
								}, 0)
							}
							return true
						},
					])
					return true
				}
				return false
			},
		}
	},

	// Add undo and redo commands
	addCommands() {
		return {
			undo:
				() =>
				({ state, dispatch }) => {
					// Use top-level imported undo command
					return undo(state, dispatch)
				},
			redo:
				() =>
				({ state, dispatch }) => {
					// Use top-level imported redo command
					return redo(state, dispatch)
				},
		}
	},

	// Handle text input transactions, ensuring they are properly added to the history stack
	addOptions() {
		return {
			newGroupDelay: 500, // Increase delay to match the configuration above
		}
	},
})

const HardBlockExtension = HardBlock.extend({
	addPasteRules() {
		return [
			nodePasteRule({
				find: /\n/g,
				type: this.type,
			}),
		]
	},
})

interface DelightfulRichEditorProps extends Omit<HTMLAttributes<HTMLDivElement>, "content"> {
	/** Whether to show the toolbar */
	showToolBar?: boolean
	/** Placeholder */
	placeholder?: string
	/** Content */
	content?: Content
	/** Editor configuration */
	editorProps?: UseEditorOptions
	/** Enter key callback */
	onEnter?: (editor: Editor) => void
	/** Paste failure callback */
	onPasteFileFail?: (error: FileError[]) => void
	/** Whether to remove Enter key default behavior */
	enterBreak?: boolean
	contentProps?: HTMLAttributes<HTMLDivElement>
}

export interface DelightfulRichEditorRef {
	editor: Editor | null
}

const DelightfulRichEditor = memo(
	forwardRef<DelightfulRichEditorRef, DelightfulRichEditorProps>((props, ref) => {
		const {
			content,
			placeholder,
			showToolBar = true,
			editorProps,
			enterBreak = false,
			contentProps,
			onPasteFileFail,
			...otherProps
		} = props
		const { styles } = useStyles()
		const { t } = useTranslation("interface")

		const parentDom = useRef<HTMLDivElement>(null)

		// Create an editorRef to resolve circular reference issues
		const editorRef = useRef<Editor | null>(null)

		// Used to handle custom placeholder
		const [showPlaceholder, setShowPlaceholder] = useState(!content)

		// Error state management
		const [error, setError] = useState<string | null>(null)

		// Optimize onPaste callback with useCallback
		const handlePaste = useCallback(async (editor: Editor, files: File[]) => {
			try {
				console.log("FileHandler onPaste", files)
				// Ensure processing only once
				if (!files.length) return

				const currentPos = editor.state.selection.$from.pos
				// Only handle the first file to avoid duplicate insertions
				const file = files[0]
				const src = await fileToBase64(file)
				editor.commands.insertContent({
					type: Image.name,
					attrs: { src, file_name: file.name, file_size: file.size },
				})

				editor.commands.focus(currentPos + 1)
			} catch (err) {
				console.error("Error handling paste:", err)
				setError("Image paste failed, please try again")
			}
		}, [])

		// Optimize onImageRemoved callback with useCallback
		const handleImageRemoved = useCallback((attrs: Record<string, any>) => {
			console.log("Image removed", attrs)
			// If the src is a blob URL, revoke it
			if (attrs.src?.startsWith("blob:")) {
				try {
					URL.revokeObjectURL(attrs.src)
				} catch (err) {
					console.error("Error revoking blob URL:", err)
				}
			}
		}, [])

		const extensions = useMemo(() => {
			const list = [
				// Custom history management, replaces StarterKit's built-in history
				CustomHistory,
				// Skip calling extend in test environment
				StarterKit.configure({
					blockquote: false,
					codeBlock: false,
					orderedList: false,
					bulletList: false,
					code: false,
					hardBreak: false,
					// Disable StarterKit built-in history, use our custom history
					history: false,
				}).extend({
					addKeyboardShortcuts() {
						return {
							// Remove Enter key default behavior
							Enter: () => enterBreak,
						}
					},
				}),
				FontSize,
				Highlight,
				TextAlign,
				TextStyle,
				Image.configure({
					inline: true,
					allowedMimeTypes: ["image/*"],
					maxFileSize: 15 * 1024 * 1024,
					onImageRemoved: handleImageRemoved,
					onValidationError: onPasteFileFail,
				}),
				FileHandler.configure({
					allowedMimeTypes: ["image/*"],
					maxFileSize: 15 * 1024 * 1024,
					onPaste: handlePaste,
					onValidationError: onPasteFileFail,
				}),
				// MentionExtension.configure({
				// 	HTMLAttributes: {
				// 		class: styles.mention,
				// 	},
				// 	suggestion: suggestion(getParentDom),
				// 	deleteTriggerWithBackspace: true,
				// }),
				DelightfulEmojiNodeExtension.configure({
					HTMLAttributes: {
						className: styles.emoji,
					},
					basePath: "/emojis",
				}),
				HardBlockExtension,
			]
			return list
		}, [enterBreak, handleImageRemoved, handlePaste, onPasteFileFail, styles.emoji])

		const pasteSize = useRef<{
			originalPosition: number
			size: number
		} | null>(null)

		// Optimize handlePasteText with useCallback
		const handlePasteText = useCallback((view: EditorView, event: ClipboardEvent) => {
			// If files exist, let FileHandler handle them; return false to do nothing here
			if (event.clipboardData?.files.length) {
				return false
			}

			// Get plain text content
			const text = event.clipboardData?.getData("text/plain")

			if (text) {
				// Prevent default paste behavior
				event.preventDefault()

				// Create a sequence of paragraphs, each newline as a new paragraph
				const parts = text.split("\n")
				const transaction = view.state.tr
				const { selection } = view.state

				// If there is a selection, delete it first
				if (!selection.empty) {
					transaction.delete(selection.from, selection.to)
					// Note: We do not set setMeta("addToHistory", true) here,
					// so the deletion becomes part of the current transaction, not a separate history entry
				}

				let pos = selection.from

				// Insert these paragraphs one by one into the document
				for (let i = 0; i < parts.length; i += 1) {
					// Insert text part
					if (parts[i].length > 0) {
						transaction.insertText(parts[i], pos)
						pos += parts[i].length
					}

					// Insert a hard break after each part (except the last)
					if (i < parts.length - 1) {
						const hardBreakNode = view.state.schema.nodes.hardBreak.create()
						transaction.insert(pos, hardBreakNode)
						pos += 1
					}
				}

				// Ensure the paste operation is added to the history
				transaction.setMeta("addToHistory", true)

				// Apply the transaction
				view.dispatch(transaction)

				return true
			}

			return false
		}, [])

		// Optimize handleEditorUpdate with useCallback
		const handleEditorUpdate = useCallback(
			(updateProps: EditorEvents["update"]) => {
				const { editor: e } = updateProps
				if (pasteSize.current) {
					e.commands.focus(pasteSize.current.originalPosition + pasteSize.current.size)
					pasteSize.current = null
				}

				// Update placeholder state
				setShowPlaceholder(e.isEmpty)

				editorProps?.onUpdate?.(updateProps)
			},
			[editorProps],
		)

		// Optimize onPaste with useCallback
		const handleOnPaste = useCallback((_: ClipboardEvent, slice: Slice) => {
			// Record position only when handling plain-text paste
			if (!slice.content.firstChild?.type.name.includes("image")) {
				pasteSize.current = {
					originalPosition: editorRef.current?.state.selection.$from.pos ?? 0,
					size: slice.content.size,
				}
			}
		}, [])

		// Optimize editor configuration with useMemo
		const editorConfig = useMemo(
			() => ({
				onPaste: handleOnPaste,
				editorProps: {
					handlePaste: handlePasteText,
				},
				enablePasteRules: true,
				extensions: [...extensions, ...(editorProps?.extensions ?? [])],
				content,
				autofocus: true,
				immediatelyRender: true,
				shouldRerenderOnTransaction: true,
				onUpdate: handleEditorUpdate,
				...omit(editorProps, ["extensions", "onUpdate"]),
			}),
			[content, editorProps, extensions, handleEditorUpdate, handleOnPaste, handlePasteText],
		)

		const editor = useEditor(editorConfig)

		// Update editorRef
		useEffect(() => {
			editorRef.current = editor
			// After initialization, update placeholder state
			if (editor) {
				setShowPlaceholder(editor.isEmpty)
			}
		}, [editor])

		useImperativeHandle(ref, () => ({
			editor,
		}))

		// Clear error message after a delay
		useEffect(() => {
			if (error) {
				const timer = setTimeout(() => {
					setError(null)
				}, 3000)
				return () => clearTimeout(timer)
			}
			return undefined
		}, [error])

		// Clean up resources when component unmounts
		useEffect(() => {
			return () => {
				// Destroy editor
				if (editorRef.current) {
					editorRef.current.destroy()
				}
			}
		}, [])

		// Add global style overrides to ensure no outlines/borders
		useEffect(() => {
			// Add global styles
			const styleElement = document.createElement("style")
			styleElement.textContent = `
				.ProseMirror, .ProseMirror:focus, .ProseMirror:focus-visible,
				div[contenteditable="true"], div[contenteditable="true"]:focus, div[contenteditable="true"]:focus-visible {
					outline: none !important;
					box-shadow: none !important;
					border: none !important;
				}
				
				/* Fix height issues */
				.ProseMirror {
					height: auto !important;
					min-height: inherit !important;
					overflow: hidden !important;
					max-height: none !important;
					caret-color: inherit !important; /* Use inherited caret color */
				}
				
				/* Ensure paragraphs have no extra margins */
				.ProseMirror p {
					margin: 0 !important;
					padding: 0 !important;
					line-height: 1.5em !important; /* Set fixed line height */
					min-height: 1em !important;
					max-height: none !important;
					position: relative !important;
				}
				
				/* Cursor style to avoid layout impact */
				.ProseMirror .ProseMirror-cursor {
					margin: 0 !important;
					padding: 0 !important;
					position: absolute !important;
					z-index: 1 !important;
				}
				
				/* Fix editor container height */
				.tiptap {
					height: auto !important;
					min-height: inherit !important;
					overflow: hidden !important;
					display: flex !important;
					flex-direction: column !important;
				}
				
				/* Add placeholder style in focus state */
				.ProseMirror:focus p.is-editor-empty:first-child::before,
				.ProseMirror:focus p.is-empty::before {
					color: #d9d9d9;
					top: 0 !important;
					left: 0 !important;
					transform: translateY(0) !important;
					line-height: 1.5em !important;
				}

				p[data-suggestion] {
					position: relative;
					overflow: visible;
				}

				/* Restore autocomplete suggestion style */
				p[data-suggestion]::after {
					color: #bfbfbf;
					content: attr(data-suggestion);
					pointer-events: none;
					height: 0;
				}
			`
			document.head.appendChild(styleElement)

			// Cleanup function
			return () => {
				document.head.removeChild(styleElement)
			}
		}, [])

		return (
			<div ref={parentDom} {...otherProps} id="delightful-rich-editor">
				{showToolBar && <ToolBar className={styles.toolbar} editor={editor} />}
				<div style={{ position: "relative" }}>
					<Placeholder
						placeholder={placeholder ?? t("richEditor.placeholder")}
						show={showPlaceholder}
					/>
					<EditorContent
						className={styles.content}
						editor={editor}
						style={{
							height: "auto",
							overflow: "hidden",
							display: "flex",
							flexDirection: "column",
						}}
						{...contentProps}
					/>
				</div>
				{error && <div className={styles.error}>{error}</div>}
			</div>
		)
	}),
)

export default DelightfulRichEditor

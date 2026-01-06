import { DelightfulRichEditorRef } from "@/opensource/components/base/DelightfulRichEditor"
import { debounce } from "lodash-es"
import { Extension } from "@tiptap/core"
import { Plugin } from "@tiptap/pm/state"
import type { Transaction, EditorState } from "@tiptap/pm/state"
import EditorService from "./EditorService"
import AiCompletionTip from "@/opensource/stores/chatNew/editor/AiCompletionTip"
import { platformKey } from "@/utils/storage"
import { userStore } from "@/opensource/models/user"

/**
 * AI auto-completion extension options interface.
 */
export interface AIAutoCompletionExtensionOptions {
	/**
	 * Function to fetch suggestion text based on current editor content.
	 */
	fetchSuggestion?: (value: string) => Promise<string>
}

/**
 * AI auto-completion service.
 * Manages AI suggestions in the editor: show/hide suggestions and handle interactions.
 *
 * Flow:
 * 1. User types -> onUpdate triggers -> triggerFetchSuggestion fetches suggestion
 * 2. On success -> updateSuggestion updates UI -> user can accept with Tab
 * 3. User presses Tab -> editor.commands.insertContent inserts suggestion -> clearSuggestion clears state
 */
class AiCompletionService {
	/** Editor instance reference */
	instance: DelightfulRichEditorRef | null = null

	/** Whether IME composition is active (e.g., Chinese input) */
	composition: boolean = false

	/** Cache of current text content for change comparison */
	valueCache: string = ""

	/** Current suggestion text */
	currentSuggestion: string = ""

	/** Flag for undo/redo (history) operations */
	isHistoryOperation: boolean = false

	/** AI auto-completion config options */
	aiAutoCompletionOptions: AIAutoCompletionExtensionOptions | undefined

	/** Flag for ongoing clear to avoid triggering other operations during cleanup */
	isClear: boolean = false

	/**
	 * Constructor
	 * @param aiAutoCompletionOptions AI auto-completion options
	 */
	constructor(aiAutoCompletionOptions?: AIAutoCompletionExtensionOptions) {
		this.instance = null
		this.aiAutoCompletionOptions = aiAutoCompletionOptions
	}

	/**
	 * Get localStorage key for tracking usage.
	 */
	get localStorageKey() {
		const userId = userStore.user.userInfo?.user_id
		return platformKey(`ai-completion-tab-count/${userId}`)
	}

	/**
	 * Get Tab key pressed count.
	 */
	getTabCount() {
		const tabCount = localStorage.getItem(this.localStorageKey)
		return tabCount ? parseInt(tabCount) : 0
	}

	/**
	 * Increment Tab key pressed count.
	 */
	addTabCount() {
		const tabCount = this.getTabCount()
		localStorage.setItem(this.localStorageKey, (tabCount + 1).toString())
	}

	/**
	 * Set the editor instance. Call on component mount.
	 * @param instance Rich text editor instance
	 */
	setInstance = (instance: DelightfulRichEditorRef) => {
		this.instance = instance
	}

	/**
	 * Determine whether suggestion text is effectively empty.
	 * Used by updateSuggestion to decide visibility.
	 * @param suggestion Text to check.
	 * @returns True if empty (null/undefined/empty/only whitespace or invisible chars).
	 */
	isEmptyText = (suggestion?: string) => {
		// Handle null/undefined/empty string
		if (!suggestion) return true

		// Whitespace-only
		const trimmed = suggestion.trim()
		if (trimmed.length === 0) return true

		// Remove invisible/special characters to check for visible content
		let visible = trimmed

		// Remove spaces and tabs
		visible = visible.replace(/\s+/g, "")

		// Remove common zero-width characters (individually)
		visible = visible.replace(/\u200B/g, "") // zero-width space
		visible = visible.replace(/\u200C/g, "") // zero-width non-joiner
		visible = visible.replace(/\u200D/g, "") // zero-width joiner
		visible = visible.replace(/\uFEFF/g, "") // zero-width no-break space
		visible = visible.replace(/\u200E/g, "") // left-to-right mark

		visible = visible.replace(/\uFE0F/g, "")

		// Final emptiness check
		return visible.length === 0
	}

	/**
	 * Update suggestion text and show hint.
	 * Called by triggerFetchSuggestion after fetching or by clearSuggestion to reset.
	 * @param suggestion Suggestion text.
	 */
	updateSuggestion = (suggestion?: string) => {
		const editor = this.instance?.editor
		if (!editor) return

		const isEmpty = this.isEmptyText(suggestion)
		// If empty, hide hint and clear attributes
		if (isEmpty) {
			// Clear suggestion on paragraph node
			editor.commands.updateAttributes("paragraph", { suggestion: "" })
			AiCompletionTip.hide()
			return
		}

		// Save current suggestion for potential restore
		this.currentSuggestion = suggestion || ""

		// Create a specially flagged transaction that does not affect history
		const { tr } = editor.state
		tr.setMeta("addToHistory", false)
		tr.setMeta("suggestionUpdate", true)
		editor.view.dispatch(tr)

		// Update paragraph attribute with suggestion
		editor.commands.updateAttributes("paragraph", { suggestion: suggestion || "" })

		// Get document end position to place the hint popup
		const endPosition = this.getDocumentEndPosition()

		// If position exists and suggestion is not empty, show hint
		if (endPosition && !isEmpty) {
			// Only show hint for the first two times the user uses Tab
			if (this.getTabCount() < 2) {
				AiCompletionTip.show({
					left: endPosition.left,
					top: endPosition.top + endPosition.height,
				})
			}
		} else if (isEmpty) {
			AiCompletionTip.hide()
		}
	}

	/**
	 * Get view coordinates for the end of the document to locate hint popup.
	 * @returns DOMRect of the end position with coordinates and size.
	 */
	getDocumentEndPosition = () => {
		const editor = this.instance?.editor
		if (!editor) return

		// Save current cursor position
		const currentCursorPosition = editor.state.selection.head
		// Get end position
		const lastPosition = editor.state.doc.content.size - 1

		// Temporarily move cursor to end to read its position
		editor.commands.focus(lastPosition, { scrollIntoView: false })
		const endPosition = this.getCurrentCursorPosition()

		// Restore original cursor position
		editor.commands.focus(currentCursorPosition, { scrollIntoView: false })

		return endPosition
	}

	/**
	 * Get view coordinates for the current cursor position.
	 * Used by getDocumentEndPosition.
	 * @returns DOMRect for current selection.
	 */
	getCurrentCursorPosition = () => {
		const selection = window.getSelection()
		if (!selection || selection.rangeCount === 0) {
			return
		}

		const range = selection.getRangeAt(0)
		return range.getBoundingClientRect()
	}

	/**
	 * Trigger fetching AI suggestion (debounced).
	 * Fired by editor onUpdate when content changes; debounced to avoid floods.
	 */
	triggerFetchSuggestion = debounce(
		() => {
			const text = this.getText()

			// Conditions: has text, fetcher configured, not in IME composition
			if (!text || !this.aiAutoCompletionOptions || this.composition) return

			// Fetch suggestion
			this.aiAutoCompletionOptions
				?.fetchSuggestion?.(text)
				.then((suggestion) => {
					// Only update suggestion when:
					// 1. Editor is not empty
					// 2. Not in IME composition
					// 3. Current text has not changed (user didn’t keep typing)
					if (
						!this.instance?.editor?.isEmpty &&
						!this.composition &&
						this.getText() === text
					) {
						this.updateSuggestion(suggestion)
						this.valueCache = text
					}
				})
				.catch((e) => {
					console.error("获取AI建议词失败:", e)
				})
		},
		200,
		{
			trailing: true,
		},
	)

	/**
	 * Get the TipTap extension for AI auto-completion features.
	 * Call during editor initialization to wire functionality.
	 * @returns TipTap extension.
	 */
	getExtension = () => {
		// eslint-disable-next-line @typescript-eslint/no-this-alias
		const self = this

		return Extension.create<
			AIAutoCompletionExtensionOptions | undefined,
			{ valueCache: string }
		>({
			name: "ai-auto-completion",

			// High priority so it runs before other extensions
			priority: 1000,

			// Add global node attributes to store suggestion text
			addGlobalAttributes() {
				return [
					{
						types: ["paragraph"],
						attributes: {
							suggestion: {
								default: "",
								parseHTML: (element) => {
									return element.getAttribute("data-suggestion") ?? ""
								},
								renderHTML: (attrs) => {
									return { "data-suggestion": attrs.suggestion }
								},
							},
						},
					},
				]
			},

			// Add ProseMirror plugin for transaction/state handling
			addProseMirrorPlugins() {
				return [
					new Plugin({
						// Handle transactions and maintain history flags
						appendTransaction: (
							transactions: readonly Transaction[],
							_: EditorState,
							newState: EditorState,
						) => {
							// Check for history ops (undo/redo)
							const hasHistoryOp = transactions.some(
								(tr) => tr.getMeta("isUndoing") || tr.getMeta("isRedoing"),
							)

							if (hasHistoryOp) {
								// Mark as history op; restore suggestion later
								self.isHistoryOperation = true

								// After undo/redo, restore suggestion asynchronously
								setTimeout(() => {
									// Restore suggestion without affecting history
									if (self.isHistoryOperation && self.currentSuggestion) {
										const editor = self.instance?.editor
										if (editor) {
											// Dispatch transaction that doesn't enter history
											const { tr } = editor.state
											tr.setMeta("addToHistory", false)
											tr.setMeta("suggestionUpdate", true)
											editor.view.dispatch(tr)

											editor.commands.updateAttributes("paragraph", {
												suggestion: self.currentSuggestion,
											})
										}
										self.isHistoryOperation = false
									}
								}, 0)
							}

							// Ensure suggestion-update transactions do not affect history
							if (transactions.some((tr) => tr.getMeta("suggestionUpdate"))) {
								return newState.tr.setMeta("addToHistory", false)
							}

							return null
						},
					}),
				]
			},

			// Watch transactions to mark history ops
			onTransaction({ transaction }) {
				if (transaction.getMeta("isUndoing") || transaction.getMeta("isRedoing")) {
					self.isHistoryOperation = true
				}
			},

			/**
			 * Observe editor updates to trigger suggestion fetch when user types.
			 */
			onUpdate() {
				// Skip while clearing
				if (self.isClear) {
					self.isClear = false
					return
				}

				const editor = self.instance?.editor
				if (!editor) return

				const text = self.getText()

				// Conditions to fetch:
				// 1) Has text
				// 2) Text changed
				// 3) Not composing
				if (text && text !== self.valueCache && !self.composition) {
					self.triggerFetchSuggestion()
				}
			},

			// Clear suggestion when editor blurs
			onBlur() {
				if (self.valueCache) {
					self.clearSuggestion()
				}
			},

			// Add keyboard shortcut: Tab to accept suggestion
			addKeyboardShortcuts() {
				return {
					Tab: ({ editor }) => {
						// Retrieve suggestion attribute
						const attr = editor.getAttributes("paragraph")
						const { suggestion } = attr

						// Robust check to ensure suggestion validity
						if (
							suggestion &&
							typeof suggestion === "string" &&
							suggestion.trim().length > 0
						) {
							editor.chain().focus().run()

							const currentPosition = editor.state.selection.head
							// Get end position
							const endPosition = editor.state.doc.content.size - 1
							editor.commands.focus(endPosition)

							// Insert suggestion at the document end
							editor.commands.insertContent(suggestion)

							// Increment counter
							self.addTabCount()

							// Restore cursor if needed
							const isNotLastPosition = currentPosition < endPosition
							if (isNotLastPosition) {
								editor.commands.focus(currentPosition)
							}

							// Clear after insertion
							self.clearSuggestion()
						}

						// Disable default Tab behavior
						return true
					},
				}
			},
		})
	}

	/**
	 * Get current editor text content.
	 * @returns Text content.
	 */
	getText = () => {
		return this.instance?.editor?.getText()
	}

	/**
	 * Clear suggestion and related state.
	 * Called when accepting, canceling suggestion, or on blur.
	 */
	clearSuggestion = () => {
		this.isClear = true
		this.valueCache = ""
		this.currentSuggestion = ""

		// Ensure editor exists
		const editor = this.instance?.editor
		if (editor) {
			// Clear paragraph attribute directly so Tab handler won’t react
			editor.commands.updateAttributes("paragraph", { suggestion: "" })
		}

		// Hide hint (do not route via updateSuggestion)
		AiCompletionTip.hide()
	}

	/**
	 * Handle IME composition start (e.g., Chinese input).
	 */
	onCompositionStart = () => {
		this.composition = true
		this.clearSuggestion()
	}

	/**
	 * Handle IME composition end; resume suggestion fetching.
	 */
	onCompositionEnd = () => {
		this.composition = false
		this.triggerFetchSuggestion()
	}
}

// Export singleton
export default new AiCompletionService({
	fetchSuggestion: EditorService.fetchAiAutoCompletion,
})

import { memo, useEffect, useRef, useMemo } from "react"
import { EditorView } from "prosemirror-view"
import { Schema, Node } from "prosemirror-model"
import { EditorState } from "prosemirror-state"
import ChatFileService from "@/opensource/services/chat/file/ChatFileService"
import useStyles from "./styles"
import schemaConfig from "./schemaConfig"
import { transformJSONContent } from "./utils"
import { useMemoizedFn } from "ahooks"

interface Props {
	content?: Record<string, any>
	messageId?: string
	className?: string
	hiddenDetail?: boolean
	emojiSize?: number
}

const RichText = memo(
	function RichText(props: Props) {
		const { content, messageId, className, hiddenDetail = false, emojiSize } = props
		const containerRef = useRef<HTMLDivElement>(null)
		const editorViewRef = useRef<EditorView | null>(null)
		const initializingRef = useRef(false)
		const { styles, cx } = useStyles({ emojiSize: emojiSize ?? (hiddenDetail ? 16 : 20) })

		const finalSchema = useMemo(() => new Schema(schemaConfig as any), [])

		const handleClickOn = useMemoizedFn((view, pos, node, nodePos, event, direct) => {
			if (node.type.name === "image" || node.type.name === "delightful-emoji") {
				event.preventDefault()
				// Commented out: fix image unable to trigger click event after clicking
				// event.stopPropagation()
				return false
			}
			return true
		})

		// Initialize renderer
		useEffect(() => {
			async function init() {
				initializingRef.current = true
				try {
					// Handle image data
					await transformJSONContent(
						content,
						(c) => c.type === "image",
						async (c) => {
							const src = c.attrs?.src
							if (src) return

							const fileUrl = await ChatFileService.fetchFileUrl([
								{ file_id: c.attrs?.file_id, message_id: messageId ?? "" },
							])
							c.attrs = {
								...c.attrs,
								src: fileUrl[c.attrs?.file_id]?.url ?? "",
								hidden_detail: hiddenDetail,
							}
						},
					)

					// Handle quick instruction
					transformJSONContent(
						content,
						(c) => c.type === "quick-instruction",
						(c) => {
							c.attrs = {
								...c.attrs,
								hidden_detail: hiddenDetail,
							}
						},
					)

					// Handle image
					editorViewRef.current = new EditorView(containerRef.current, {
						state: EditorState.create({
							doc: Node.fromJSON(finalSchema, content),
							schema: finalSchema,
						}),
						editable: () => false,
						handleClickOn,
						handleTripleClickOn: handleClickOn,
						handleDOMEvents: {
							mousedown: (_, event) => {
								if (event.target instanceof HTMLImageElement) {
									event.preventDefault()
									// Commented out: Fix for image click event not firing after click
									// event.stopPropagation()
									return true
								}
								return false
							},
							click: (_, event) => {
								if (event.target instanceof HTMLImageElement) {
									event.preventDefault()
									// Commented out: Fix for image click event not firing after click
									// event.stopPropagation()
									return true
								}
								return false
							},
						},
						plugins: [],
					})
				} catch (error) {
					console.error("RichText init error:", error, content)
				} finally {
					initializingRef.current = false
				}
			}

			if (containerRef.current && content && !initializingRef.current) {
				init()
			}

			return () => {
				if (editorViewRef.current) {
					editorViewRef.current.destroy()
				}
			}
		}, [content, finalSchema, handleClickOn, hiddenDetail, messageId])

		// // Content update handler
		// useEffect(() => {
		// 	if (!editorViewRef.current || !content) return
		// 	console.log("content update 2=====> ", content)
		// 	try {
		// 		// Create new EditorState instead of partial update to avoid type mismatch issues
		// 		const newState = EditorState.create({
		// 			doc: Node.fromJSON(finalSchema, content),
		// 			schema: finalSchema,
		// 		})
		// 		editorViewRef.current.updateState(newState)
		// 	} catch (error) {
		// 		console.error("RichText updateState error:", error, content)
		// 	}
		// }, [content, finalSchema])

		return (
			<div
				ref={containerRef}
				id={`message_copy_${messageId}`}
				className={cx(
					styles.container,
					styles.emoji,
					className,
					styles.quickInstruction,
					styles.image,
				)}
			/>
		)
	},
	(prev, next) => {
		return prev.content === next.content && prev.messageId === next.messageId
	},
)

export default RichText

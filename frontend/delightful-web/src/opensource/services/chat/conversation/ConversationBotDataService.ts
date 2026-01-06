import { computed, makeAutoObservable } from "mobx"
import type { JSONContent } from "@tiptap/core"
import { cloneDeep, isObject, omit } from "lodash-es"
import type {
	SelectorQuickInstruction,
	SwitchQuickInstruction,
	TextQuickInstruction,
	QuickInstruction,
	StatusQuickInstruction,
	QuickInstructionList,
	SystemInstruct,
	InstructionGroupType,
	Bot,
} from "@/types/bot"
import { InstructionMode, InstructionType } from "@/types/bot"
import type { DelightfulRichEditorRef } from "@/opensource/components/base/DelightfulRichEditor"
import { InsertLocationMap } from "@/opensource/pages/flow/components/QuickInstructionButton/const"
import type { ConversationMessageInstruct } from "@/types/chat/conversation_message"
import {
	replaceExistQuickInstruction,
	transformQuickInstruction,
} from "@/opensource/pages/chatNew/components/quick-instruction/utils"
import { ChatApi } from "@/apis"

export interface InstructConfigUpdateParams {
	instructs?: Record<string, unknown>
}

export interface InstructUpdateCallback {
	(params: InstructConfigUpdateParams): Promise<{ instructs?: Record<string, unknown> }>
}

export interface ConversationBotDataManagerProps {
	// 编辑器相关
	editorRef?: DelightfulRichEditorRef | null
	onSend?: (
		content: JSONContent | undefined,
		onlyTextContent: boolean,
	) => Promise<void> | undefined
}

/**
 * Conversation bot data manager
 */
class ConversationBotDataService {
	// Editor related
	editorRef?: DelightfulRichEditorRef | null

	onSend?: (
		content: JSONContent | undefined,
		onlyTextContent: boolean,
	) => Promise<void> | undefined

	// BotProvider related data
	conversationId?: string

	// User ID
	userId?: string

	// Bot ID
	agentId?: string

	// Loading: fetching bot info
	isfetchBotInfoLoading: boolean = false

	// Quick instruction list
	instructions?: QuickInstructionList[]

	// Quick instruction config
	innerInstructConfig?: Record<string, unknown>

	// Session quick instruction config (lost after refresh)
	sessionInstructConfig?: Record<string, unknown>

	// Whether to show the start page
	startPage?: boolean

	// Loading: updating quick instruction config
	isUpdateConversationInstructionConfigLoading: boolean = false

	// Loading: fetching quick instruction config
	isFetchConversationInstructionConfigLoading: boolean = false

	get instructConfig() {
		return {
			...this.innerInstructConfig,
			...this.sessionInstructConfig,
		}
	}

	/**
	 * Get quick instructions
	 * @param position Position
	 * @returns Quick instructions
	 */
	getQuickInstructionsByPosition(position: InstructionGroupType) {
		return this.instructions?.find((instruction) => instruction.position === position)
	}

	constructor(props?: ConversationBotDataManagerProps) {
		// Editor related
		this.editorRef = props?.editorRef
		this.onSend = props?.onSend
		makeAutoObservable(
			this,
			{
				residencyContent: computed,
			},
			{ autoBind: true },
		)
	}

	/**
	 * Update props
	 */
	updateProps(props: ConversationBotDataManagerProps) {
		this.editorRef = props?.editorRef
		this.onSend = props?.onSend
	}

	/**
	 * Get residency content
	 * @returns JSONContent list
	 */
	get residencyContent() {
		const result: JSONContent[] = []
		if (!this.instructConfig) return result
		const insList = this.instructions?.map((i) => i.items).flat()
		Object.entries(this.instructConfig).forEach(([key, value]) => {
			const ins = insList?.find((i) => i.id === key)
			if (!ins) return
			if (!ins.residency) return
			switch (ins.type) {
				case InstructionType.SINGLE_CHOICE:
					console.log("ins.content", ins)
					let item = transformQuickInstruction(JSON.parse(ins.content ?? "[]"), (c) => {
						c.attrs = {
							instruction: ins,
							value,
						}
					}) as JSONContent[]

					if (!Array.isArray(item)) {
						item = [item]
					}

					result.push(...item)
					break
				default:
					break
			}
		})

		return result
	}

	/**
	 * 初始化时，插入常驻快捷指令
	 */
	insertResidencyQuickInstructionWhenInit() {
		const jsonContent = this.editorRef?.editor?.getText()
		// If content is not empty, do not insert
		if (jsonContent) return
		this.editorRef?.editor?.chain().insertContent(this.residencyContent).run()
		this.editorRef?.editor?.chain().focus().run()
	}

	/**
	 * Update quick instruction config
	 * @param instructs Quick instruction config
	 */
	updateRemoteInstructionConfig(instructs: Record<string, unknown>, remove?: boolean) {
		if (!this.conversationId || !this.userId)
			return Promise.reject(new Error("会话ID或用户ID为空"))

		this.isUpdateConversationInstructionConfigLoading = true

		return ChatApi.updateAiConversationQuickInstructionConfig({
			conversation_id: this.conversationId,
			receive_id: this.userId,
			instructs,
		})
			.then(({ instructs: instructs_list }) => {
				if (remove) {
					this.innerInstructConfig = omit(
						this.innerInstructConfig,
						Object.keys(instructs),
					)
				} else {
					this.innerInstructConfig = {
						...this.innerInstructConfig,
						...instructs_list,
					}
				}
			})
			.finally(() => {
				this.isUpdateConversationInstructionConfigLoading = false
			})
	}

	/**
	 * Clear bot info
	 */
	clearBotInfo() {
		// Clear bot info
		this.instructions = []
		this.startPage = false
		this.innerInstructConfig = {}
		this.sessionInstructConfig = {}
		this.isFetchConversationInstructionConfigLoading = false
		this.isUpdateConversationInstructionConfigLoading = false
		this.isfetchBotInfoLoading = false
	}

	/**
	 * Update attributes
	 */
	switchConversation(
		conversationId: string,
		userId: string,
		botInfo: Bot.Detail["botEntity"],
		instructConfig?: Record<string, unknown>,
	) {
		// Clear previous bot info
		this.clearBotInfo()

		// Update new bot info
		this.conversationId = conversationId
		this.userId = userId
		this.innerInstructConfig = instructConfig
		this.initBotInfo(botInfo)
	}

	/**
	 * Initialize bot info
	 * @param data Bot info
	 */
	initBotInfo(data: Bot.Detail["botEntity"]) {
		this.instructions = data.instructs
		this.startPage = data.start_page
		this.agentId = data.root_id
		this.insertResidencyQuickInstructionWhenInit()
	}

	/**
	 * Update conversation quick instruction config
	 */
	updateConversationInstructConfig(instructConfig: Record<string, unknown>) {
		this.innerInstructConfig = instructConfig
	}

	/**
	 * Insert switch quick instruction into JSON content
	 */
	enhanceJsonContentBaseSwitchInstruction(jsonContent?: JSONContent): JSONContent | undefined {
		if (!jsonContent) return jsonContent
		if (isObject(jsonContent)) {
			const instructMap = this.instructions?.map((item) => item.items).flat()

			// No quick instructions configured, return directly
			if (!instructMap) return jsonContent

			let container

			if (
				jsonContent.content?.[0] &&
				jsonContent.content?.[0].content &&
				Array.isArray(jsonContent.content?.[0].content)
			) {
				container = jsonContent.content?.[0].content
			} else if (jsonContent.content && Array.isArray(jsonContent.content)) {
				container = jsonContent.content
			}

			if (container) {
				// Get default config
				const defaultConfig = instructMap.reduce((acc, cur) => {
					if (cur.type === InstructionType.SWITCH) {
						acc[cur.id] = cur.default_value
					}
					return acc
				}, {} as Record<string, unknown>)

				// Merge default config and user config
				Object.entries({ ...defaultConfig, ...this.instructConfig })
					.reverse()
					.forEach(([instructId, value]) => {
						const ins = instructMap?.find((i) => i.id === instructId) as
							| Exclude<QuickInstruction, SystemInstruct>
							| undefined

						// If instruction does not exist, skip
						if (!ins) return

						// If instruction is text or status, skip
						if (
							[
								InstructionType.TEXT,
								InstructionType.STATUS,
								InstructionType.SINGLE_CHOICE,
							].includes(ins.type)
						)
							return

						// If instruction is Flow mode, skip
						if (ins.instruction_type === InstructionMode.Flow) return

						// Switch instruction: insert only when value is 'on'
						if (ins.type === InstructionType.SWITCH && value !== "on") return

						// For switch, do not append content; backend will compose
						const content = [
							{
								type: "quick-instruction",
								attrs: {
									instruction: ins,
									value,
								},
							},
						]

						// Insert at head/tail based on setting
						switch (ins.insert_location) {
							case InsertLocationMap.Behind:
								container?.push(...content)
								break
							case InsertLocationMap.Before:
							default:
								container?.unshift(...content)
								break
						}
					})

				return cloneDeep(jsonContent)
			}
			return jsonContent
		}
		return jsonContent
	}

	/**
	 * Generate flow instruction data
	 * @returns Flow instructions
	 */
	genFlowInstructs() {
		return cloneDeep(
			Object.entries(this.instructConfig)
				.map(([k, v]) => {
					const ins = this.instructions
						?.map((i) => i.items)
						.flat()
						.find((i) => i.id === k)

					if (!ins) return undefined

					if (ins.instruction_type === InstructionMode.Chat) return undefined

					switch (ins.type) {
						case InstructionType.SWITCH:
							return {
								value: v,
								instruction: ins,
							}
						case InstructionType.SINGLE_CHOICE:
							return {
								value: v,
								instruction: ins,
							}
						case InstructionType.STATUS:
							return {
								value: v,
								instruction: ins,
							}
						default:
							return undefined
					}
				})
				.filter(Boolean) as ConversationMessageInstruct[],
		)
	}

	/**
	 * Insert content at a specified location
	 * @param insertContent Content to insert
	 * @param insertLocation Insert location
	 */
	insertContentByLocation(insertContent: JSONContent[], insertLocation?: InsertLocationMap) {
		if (!insertContent.length) return

		switch (insertLocation) {
			case InsertLocationMap.Before:
				// Insert at the first position; 0 causes a newline
				this.editorRef?.editor?.chain().focus().insertContentAt(1, insertContent).run()
				break
			case InsertLocationMap.Behind:
				// Get document end position rather than selection end
				const docSize = this.editorRef?.editor?.state.doc.content.size
				if (docSize === undefined) {
					// If doc size unknown, insert directly
					this.editorRef?.editor?.chain().focus().insertContent(insertContent).run()
				} else {
					// Compute end position by doc size to ensure validity
					const docEndPosition = Math.max(0, docSize - 1)
					this.editorRef?.editor
						?.chain()
						.focus()
						.insertContentAt(docEndPosition, insertContent)
						.run()
				}
				break
			case InsertLocationMap.Cursor:
				const currentPos = this.editorRef?.editor?.state.selection.$anchor
				if (!currentPos) {
					this.editorRef?.editor?.chain().focus().insertContent(insertContent).run()
				} else {
					this.editorRef?.editor
						?.chain()
						.focus()
						.insertContentAt(currentPos.pos, insertContent)
						.run()
				}
				break
			default:
				// Default: insert at current cursor position
				this.editorRef?.editor?.chain().focus().insertContent(insertContent).run()
				break
		}
	}

	/**
	 * 更新快捷指令配置
	 * @param instruction 快捷指令
	 * @param targetId 目标ID
	 */
	updateInstructionConfig(instruction: QuickInstruction, targetId: string) {
		if (instruction.residency) {
			return this.updateRemoteInstructionConfig?.({
				[instruction.id]: targetId,
			})
		}
		this.sessionInstructConfig = {
			...this.sessionInstructConfig,
			[instruction.id]: targetId,
		}
		return Promise.resolve()
	}

	/** Selector type */
	handleSelectorQuickInstructionClick(targetId: string, instruction: SelectorQuickInstruction) {
		// Update quick instruction config
		this.updateInstructionConfig(instruction, targetId)

		// If Flow instruction, just update value
		if (instruction.instruction_type === InstructionMode.Flow) {
			return
		}

		// If need send directly and not residency, send it
		if (instruction?.send_directly && !instruction.residency) {
			const contentWithWrapper = {
				type: "doc",
				content: transformQuickInstruction(JSON.parse(instruction.content ?? "[]"), (c) => {
					c.attrs = {
						instruction,
						value: targetId,
					}
				}) as JSONContent[],
			}

			this.onSend?.(this.enhanceJsonContentBaseSwitchInstruction(contentWithWrapper), false)
			return
		}

		// Replace existing instruction
		const jsonContent = this.editorRef?.editor?.getJSON() ?? {}
		const replaceStatus = replaceExistQuickInstruction(
			jsonContent,
			(attrs) => {
				if (!attrs) return false
				return (attrs?.instruction as QuickInstruction)?.id === instruction.id
			},
			targetId,
		)

		// On replace success, update content
		if (replaceStatus) {
			this.editorRef?.editor?.chain().clearContent().run()
			this.editorRef?.editor?.chain().focus().insertContent(jsonContent).run()
		} else {
			let insertContent = transformQuickInstruction(
				JSON.parse(instruction.content ?? "[]"),
				(c) => {
					c.attrs = {
						instruction,
						value: targetId,
					}
				},
			) as JSONContent[]

			if (!Array.isArray(insertContent)) {
				insertContent = [insertContent]
			}

			// Use unified insert method
			this.insertContentByLocation(insertContent, instruction.insert_location)
		}
	}

	/**
	 * Remove a quick instruction's value from config
	 * @param instruction Quick instruction
	 */
	removeSelectorValueFromConfig(instruction: SelectorQuickInstruction) {
		if (instruction.residency) {
			return this.updateRemoteInstructionConfig?.(
				{
					[instruction.id]: "",
				},
				true,
			)
		}

		this.sessionInstructConfig = {
			...this.sessionInstructConfig,
			[instruction.id]: "",
		}
		return Promise.resolve()
	}

	/**
	 * Clear session quick instruction config
	 */
	clearSessionInstructConfig() {
		this.sessionInstructConfig = {}
	}

	/**
	 * Switch type
	 * @param value Value
	 * @param instruction Quick instruction
	 */
	handleSwitchQuickInstructionClick(value: string, instruction: SwitchQuickInstruction) {
		return this.updateInstructionConfig(instruction, value)
	}

	/** Text type */
	handleTextQuickInstructionClick(value: string, instruction: TextQuickInstruction) {
		try {
			const content = JSON.parse(value)
			const insertContent = Array.isArray(content) ? content : [content]

			if (instruction?.send_directly) {
				const jsonWithWrapper = {
					type: "doc",
					content: insertContent,
				}
				this.onSend?.(this.enhanceJsonContentBaseSwitchInstruction(jsonWithWrapper), false)
			} else {
				// Use unified insert method
				this.insertContentByLocation(insertContent, instruction.insert_location)
			}
		} catch (err) {
			console.error("快捷指令内容解析失败", instruction)
		}
	}

	/** Status type */
	handleStatusQuickInstructionClick(
		value: string,
		instruction: StatusQuickInstruction,
		currentId: string,
	) {
		return this.updateRemoteInstructionConfig?.({
			[instruction.id]: currentId,
		}).then(() => {
			try {
				const jsonWithWrapper = {
					type: "doc",
					content: [{ type: "text", text: value }],
				}
				this.onSend?.(this.enhanceJsonContentBaseSwitchInstruction(jsonWithWrapper), false)
			} catch (err) {
				console.error("快捷指令内容解析失败", instruction)
			}
		})
	}
}

export default new ConversationBotDataService()

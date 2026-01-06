import { ChatApi } from "@/apis"
import { MessageReceiveListResponse } from "@/opensource/apis/modules/chat/types"
import { makeAutoObservable } from "mobx"

export const domClassName = "group-seen-panel"

/**
 * Group read receipts panel
 */
class GroupSeenPanelStore {
	/**
	 * Group read panel DOM class name
	 */
	static domClassName = "group-seen-panel"

	/**
	 * Message ID
	 */
	messageId: string | null = null

	/**
	 * Message recipient list
	 */
	messageReceiveList: MessageReceiveListResponse | null = null

	/**
	 * Margin size
	 */
	marginSize: number = 20

	/**
	 * Position
	 */
	position: { x: number; y: number } = { x: 0, y: 0 }

	/**
	 * Size
	 */
	size: { width: number; height: number } = { width: 360, height: 300 }

	/**
	 * Open state
	 */
	open: boolean = false

	/**
	 * Loading state
	 */
	loading: boolean = false

	/**
	 * Hover state
	 */
	isHover: boolean = false

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Open panel
	 * @param messageId Message ID
	 */
	openPanel(messageId: string, position: { x: number; y: number }) {
		this.open = true
		this.setPosition(position)

		this.adjustPosition()

		this.messageId = messageId
		this.fetchMessageReceiveList(messageId)
	}

	/**
	 * Fetch message recipients list
	 * @returns
	 */
	async fetchMessageReceiveList(messageId: string) {
		if (!messageId) {
			return
		}

		this.loading = true
		const messageReceiveList = await ChatApi.getMessageReceiveList(messageId)
		this.messageReceiveList = messageReceiveList
		this.loading = false
	}

	/**
	 * Close panel
	 */
	closePanel(force = false) {
		if (this.isHover && !force) return
		this.open = false
		this.messageId = null
		this.messageReceiveList = null
	}

	/**
	 * Set card position
	 * @param position Card position
	 */
	setPosition(position: { x: number; y: number }) {
		this.position.x = position.x - this.size.width - 20
		this.position.y = position.y
		this.adjustPosition()
	}

	/**
	 * Set card size
	 * @param size Card size
	 */
	setSize(size: { width: number; height: number }) {
		this.size = size
		// Adjust position
		this.adjustPosition()
	}

	/**
	 * Set hover state
	 * @param isHover Hover state
	 */
	setIsHover(isHover: boolean) {
		this.isHover = isHover
	}

	/**
	 * Adjust card position
	 */
	adjustPosition() {
		// Adjust position to prevent overflow
		if (typeof window !== "undefined") {
			const windowWidth = window.innerWidth - this.marginSize * 2
			const windowHeight = window.innerHeight - this.marginSize * 2

			// Ensure right edge stays within viewport
			if (this.position.x + this.size.width + this.marginSize > windowWidth) {
				this.position.x = windowWidth - this.size.width - this.marginSize
			}

			// Ensure left boundary not exceeded
			if (this.position.x < 0) {
				this.position.x = this.marginSize
			}

			// Ensure bottom edge stays within viewport
			if (this.position.y + this.size.height > windowHeight) {
				this.position.y = windowHeight - this.size.height - this.marginSize
			}

			// Ensure top boundary not exceeded
			if (this.position.y < 0) {
				this.position.y = this.marginSize
			}
		}
	}
}

export default new GroupSeenPanelStore()

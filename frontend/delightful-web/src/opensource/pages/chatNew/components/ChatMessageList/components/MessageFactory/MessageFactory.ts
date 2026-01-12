import React from "react"
import type {
	ConversationMessage,
	ConversationMessageAttachment,
	ConversationMessageSend,
} from "@/types/chat/conversation_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import MessageComponents from "./config/MessageComponents"

interface MessageProps {
	content?: any
	reasoningContent?: any
	isSelf?: boolean
	messageId: string
	files?: any[]
	isStreaming?: boolean
	isReasoningStreaming?: boolean
}

class MessageFactory {
	// Component cache, read from cache first
	private static componentCache = new Map<
		string,
		React.LazyExoticComponent<React.ComponentType<MessageProps>>
	>()

	private static getFallbackComponent(): React.LazyExoticComponent<
		React.ComponentType<MessageProps>
	> {
		return React.lazy(() => import("./components/Fallback"))
	}

	// getfilecomponent
	static getFileComponent(): React.LazyExoticComponent<React.ComponentType<MessageProps>> {
		return this.getComponent(ConversationMessageType.Files)
	}

	// Parse content
	static parseContent(
		type: string,
		message: ConversationMessage | ConversationMessageSend["message"],
	): any {
		const messageComponent = MessageComponents[type]
		if (!messageComponent?.contentParser) {
			return message
		}
		try {
			return messageComponent.contentParser(message)
		} catch (error) {
			return message
		}
	}

	// Parse reasoning content
	static parseReasoningContent(type: ConversationMessageType, message: ConversationMessage) {
		const messageComponent = MessageComponents[type]
		if (!messageComponent?.reasoningContentParser) {
			return undefined
		}
		try {
			return messageComponent.reasoningContentParser(message)
		} catch (error) {
			return undefined
		}
	}

	// Parse whether reasoning streaming
	static parseIsReasoningStreaming(type: ConversationMessageType, message: ConversationMessage) {
		const messageComponent = MessageComponents[type]
		if (!messageComponent?.isReasoningStreamingParser) {
			return false
		}
		try {
			return messageComponent.isReasoningStreamingParser(message)
		} catch (error) {
			return false
		}
	}

	// Parse whether streaming
	static parseIsStreaming(type: ConversationMessageType, message: ConversationMessage) {
		const messageComponent = MessageComponents[type]
		if (!messageComponent?.isStreamingParser) {
			return false
		}
		try {
			return messageComponent.isStreamingParser(message)
		} catch (error) {
			return false
		}
	}

	// Parse file information
	static parseFiles(
		type: string,
		message: ConversationMessage | ConversationMessageSend["message"],
		referFileId?: string,
	): ConversationMessageAttachment[] | undefined {
		const messageComponent = MessageComponents[type]

		if (!messageComponent?.showFileComponent) {
			return undefined
		}

		try {
			if (messageComponent.fileParser) {
				return messageComponent.fileParser(message, referFileId)
			}

			return undefined
		} catch (error) {
			return undefined
		}
	}

	// getcomponent
	static getComponent(
		type: string,
	): React.LazyExoticComponent<React.ComponentType<MessageProps>> {
		// Load and return component
		const messageComponent = MessageComponents[type]

		// Check cache
		if (messageComponent && this.componentCache.has(messageComponent.componentType)) {
			return this.componentCache.get(messageComponent.componentType)!
		}

		// No loader
		if (!messageComponent?.loader) {
			return this.getFallbackComponent()
		}

		// create lazy component
		const LazyComponent = React.lazy(() =>
			messageComponent.loader().then((module) => ({
				default: module.default as React.ComponentType<MessageProps>,
			})),
		)
		this.componentCache.set(messageComponent.componentType, LazyComponent)
		return LazyComponent
	}

	// Clean cache
	static cleanCache(usedTypes: string[]) {
		Array.from(this.componentCache.keys()).forEach((type) => {
			if (!usedTypes.includes(type)) {
				this.componentCache.delete(type)
			}
		})
	}
}

export default MessageFactory

import React from "react"
import ComponentConfig from "../config/ComponentConfig"
import type {
	GroupAddMemberMessage,
	GroupCreateMessage,
	GroupUsersRemoveMessage,
	GroupUpdateMessage,
	GroupDisbandMessage,
} from "@/types/chat/control_message"
import type { ConversationMessage } from "@/types/chat/conversation_message"

interface MessageProps {
	content?:
		| ConversationMessage
		| GroupAddMemberMessage
		| GroupCreateMessage
		| GroupUsersRemoveMessage
		| GroupUpdateMessage
		| GroupDisbandMessage
	reasoningContent?: any
	isSelf?: boolean
	messageId: string
	files?: any[]
	isStreaming?: boolean
	isReasoningStreaming?: boolean
}

class MessageRenderFactory {
	// Component cache, read from cache first
	private static componentCache = new Map<
		string,
		React.LazyExoticComponent<React.ComponentType<MessageProps>>
	>()

	private static getFallbackComponent(): React.LazyExoticComponent<
		React.ComponentType<MessageProps>
	> {
		return React.lazy(() => import("../../Fallback"))
	}

	// Generate props
	static generateProps(type: string, message: any) {
		const componentConfig = ComponentConfig[type]
		if (!componentConfig) {
			return {}
		}

		if (!componentConfig.getProps) {
			return { ...message }
		}

		return { ...componentConfig.getProps(message) }
	}

	// getcomponent
	static getComponent(
		type: string,
	): React.LazyExoticComponent<React.ComponentType<MessageProps>> {
		// Load and return component
		const messageComponent = ComponentConfig[type]

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

export default MessageRenderFactory

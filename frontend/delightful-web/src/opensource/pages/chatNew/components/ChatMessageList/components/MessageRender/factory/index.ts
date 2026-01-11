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
	// component缓存，优先读取缓存
	private static componentCache = new Map<
		string,
		React.LazyExoticComponent<React.ComponentType<MessageProps>>
	>()

	private static getFallbackComponent(): React.LazyExoticComponent<
		React.ComponentType<MessageProps>
	> {
		return React.lazy(() => import("../../Fallback"))
	}

	// 生成props
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
		// load并returncomponent
		const messageComponent = ComponentConfig[type]

		// check缓存
		if (messageComponent && this.componentCache.has(messageComponent.componentType)) {
			return this.componentCache.get(messageComponent.componentType)!
		}

		// 没有load器
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

	// 清除缓存
	static cleanCache(usedTypes: string[]) {
		Array.from(this.componentCache.keys()).forEach((type) => {
			if (!usedTypes.includes(type)) {
				this.componentCache.delete(type)
			}
		})
	}
}

export default MessageRenderFactory

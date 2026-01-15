import { ChatApi } from "@/apis"
import { generateSnowFlake } from "@/opensource/pages/flow/utils/helpers"
import { EventType } from "@/types/chat"
import type { ConversationMessageSend } from "@/types/chat/conversation_message"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import { useCallback, useEffect, useState } from "react"
import type { FileItem, Workspace } from "../pages/Workspace/types"
import { useDeepCompareEffect } from "ahooks"

export function useTopics(userInfo: any) {
	const [messages, setMessages] = useState<any[]>([])
	const [topicMessagesMap, setTopicMessagesMap] = useState<Record<string, any[]>>({})
	const [selectedThreadInfo, setSelectedThreadInfo] = useState<any | null>(null)
	const [fileList, setFileList] = useState<FileItem[]>([])
	const [attachments, setAttachments] = useState<FileItem[]>([])
	const handleSendMessage = useCallback(
		({ content, showLoading, selectedWorkspace, options }: any) => {
			if (!selectedThreadInfo?.id) {
				console.error("Send message - No topic selected")
				return
			}
			const message_id = generateSnowFlake()
			const Files = fileList.map((item) => {
				return {
					file_id: item.file_id,
					filename: item.file_name,
					fileize: item.file_size,
				}
			})
			const newMessage: any = {
				message_id,
				content,
				send_timestamp: new Date().toISOString(),
				type: "chat",
				attachments: Files,
				topic_id: selectedThreadInfo?.id,
			}
			const { chat_topic_id } = selectedThreadInfo
			const { conversation_id } = selectedWorkspace
			// Only update topic message map, messages will be auto-updated via useEffect. Self-sent messages have no seq_id and will be replaced by subsequently fetched messages
			if (selectedThreadInfo?.id && newMessage.content) {
				setTopicMessagesMap((prev) => ({
					...prev,
					[chat_topic_id]: [...(prev[chat_topic_id] || []), newMessage],
				}))
			}
			const date = new Date().getTime()
			console.log(
				"Send message",
				showLoading,
				chat_topic_id,
				"chat_topic_id",
				selectedThreadInfo,
				"selectedThreadInfo",
			)
			ChatApi.chat(
				EventType.Chat,
				{
					message: {
						type: ConversationMessageType.Text,
						text: {
							content,
							instructs: [{ value: showLoading ? "follow_up" : "normal" }],
							attachments: Files,
							...options,
						},
						send_timestamp: date,
						send_time: date,
						sender_id: userInfo?.user_id,
						app_message_id: message_id,
						message_id,
						topic_id: chat_topic_id,
					} as unknown as ConversationMessageSend["message"],
					conversation_id,
				},
				0,
			)
		},
		[fileList, selectedThreadInfo],
	)

	// Initialize topic messages map
	const initializeTopicMessages = useCallback((workspaces: Workspace[]) => {
		if (workspaces.length > 0) {
			// Save current message map to ensure existing messages are not lost
			setTopicMessagesMap((prevMap) => {
				const messagesMap: Record<string, any[]> = { ...prevMap }

				// Iterate all topics in all workspaces to ensure each topic has an entry
				workspaces.forEach((workspace) => {
					workspace.topics.forEach((topic) => {
						if (topic.id) {
							// If the topic already has messages in the existing map, retain them
							// Otherwise use the topic's history
							if (!messagesMap[topic.id]) {
								messagesMap[topic.id] = []
							}
						}
					})
				})
				return messagesMap
			})
		}
	}, [])

	// Update message list when selectedThreadInfo changes
	useDeepCompareEffect(() => {
		const { chat_topic_id } = selectedThreadInfo || {}
		if (chat_topic_id) {
			setMessages(topicMessagesMap[chat_topic_id] || [])
		} else {
			setMessages([])
		}
	}, [selectedThreadInfo, topicMessagesMap])

	return {
		messages,
		topicMessagesMap,
		fileList,
		setFileList,
		attachments,
		handleSendMessage,
		initializeTopicMessages,
		selectedThreadInfo,
		setTopicMessagesMap,
		setSelectedThreadInfo,
		setAttachments,
	}
}

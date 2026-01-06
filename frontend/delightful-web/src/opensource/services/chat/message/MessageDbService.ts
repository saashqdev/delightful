/* eslint-disable class-methods-use-this */
import type { Collection, Table, UpdateSpec } from "dexie"
import { Dexie } from "dexie"
import type { SeqResponse } from "@/types/request"
import {
	ConversationMessageStatus,
	type ConversationMessage,
	type ConversationMessageSend,
	type SendStatus,
} from "@/types/chat/conversation_message"
import type { CMessage } from "@/types/chat"
import type { SeenMessage } from "@/types/chat/seen_message"
import Logger from "@/utils/log/Logger"
import chatDb from "@/opensource/database/chat"

const console = new Logger("MessageDbService", "red")

class MessageDbService {
	async removeMessage(conversationId: string, messageId: string) {
		const table = await this.getMessageTable(conversationId)
		if (table) {
			table.update(messageId, {
				"message.is_local_deleted": true,
			})
		}
	}

	messageTableSchema: string = `&seq_id, message.topic_id, [message.topic_id+message.send_time], message.send_time, message.type, [message.type+message.topic_id]`

	/**
	 * Get the default schema for the message table
	 * @param conversationId Conversation ID
	 * @returns Message table schema
	 */
	// getMessageTableSchema() {
	// 	return `&seq_id, message.topic_id, [message.topic_id+message.send_time], message.send_time, message.type, [message.type+message.topic_id]`
	// }

	/**
	 * Generate message table name
	 * @param conversationId Conversation ID
	 * @returns Message table name
	 */
	private genMessageTableName(conversationId: string): string {
		return `conversation_message/${conversationId}`
	}

	/**
	 * Get pending messages table
	 * @returns Pending messages table
	 */
	private getPendingMessagesTable() {
		return chatDb.db.pending_messages
	}

	/**
	 * Create message table
	 * @param conversationId Conversation ID
	 * @returns Message table
	 */
	async createMessageTable(conversationId: string) {
		const tableName = this.genMessageTableName(conversationId)

		try {
			return chatDb.db.table(tableName)
		} catch (err) {
			// Table does not exist; create it
			const schema = {
				[tableName]: this.messageTableSchema,
			}

			await chatDb.changeSchema(schema)
			return chatDb.db.table(tableName)
		}
	}

	/**
	 * Create message tables in batch
	 * @param conversationIds Conversation IDs
	 */
	async createMessageTables(conversationIds: string[]) {
		await chatDb.changeSchema(
			conversationIds.reduce((acc, id) => {
				acc[this.genMessageTableName(id)] = this.messageTableSchema
				return acc
			}, {} as Record<string, string>),
		)
	}

	/**
	 * Get message table
	 * @param conversationId Conversation ID
	 * @returns Message table
	 */
	async getMessageTable(conversationId: string) {
		const tableName = this.genMessageTableName(conversationId)

		try {
			const table = chatDb.db.table(tableName)
			return table as Table<SeqResponse<ConversationMessage>>
		} catch (err) {
			// Try creating the table
			try {
				const t = await this.createMessageTable(conversationId)
				return t
			} catch (createErr) {
				// If creation fails, it may be an index issue; consider rebuilding indexes
				console.warn(`创建消息表失败，尝试重建索引: ${tableName}`, createErr)
				throw new Error("创建消息表失败")
				// await this.rebuildMessageTableIndex(conversationId)

				// // 重新获取表
				// return ChatBusiness.getChatDb().table(tableName) as Table<
				// 	SeqResponse<ConversationMessage>
				// >
			}
		}
	}

	/**
	 * Add a message
	 * @param conversationId Conversation ID
	 * @param message Message
	 */
	async addMessage(conversationId: string, message: SeqResponse<CMessage>) {
		const table = await this.getMessageTable(conversationId)
		if (table) {
			table
				.put(message)
				.then((res) => {
					console.log("addMessage success", res)
				})
				.catch((err) => {
					console.error("addMessage error", err)
					throw err
				})
		}
	}

	/**
	 * Add multiple messages
	 * @param conversationId Conversation ID
	 * @param messages Message list
	 */
	public async addMessages(conversationId: string, messages: SeqResponse<CMessage>[]) {
		try {
			const tableName = this.genMessageTableName(conversationId)
			await this.createMessageTable(tableName)

			const messageList = messages.map((message) => ({
				...message,
				conversation_id: conversationId,
			}))

			const table = await this.getMessageTable(conversationId)
			if (!table) {
				throw new Error("Failed to get message table")
			}

			await Promise.all(
				messageList.map((message) => table.put(message)),
			)
			return { success: true, count: messageList.length }
		} catch (error) {
			// Log error information
			const errorMessage = error instanceof Error ? error.message : "Unknown error"
			return { success: false, error: errorMessage }
		}
	}

	/**
	 * Get paginated messages for a conversation
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @param page Page number
	 * @param pageSize Page size
	 * @returns Paginated messages
	 */
	async getMessagesByPage(
		conversationId: string,
		topicId: string = "",
		page: number = 1,
		pageSize: number = 10,
	) {
		const table = await this.getMessageTable(conversationId)

		// Use compound index to optimize query performance
		const query = table
			.where("[message.topic_id+message.send_time]")
			.between([topicId, Dexie.minKey], [topicId, Dexie.maxKey])
			// Filter out locally deleted messages
			.filter((message) => !message.message.is_local_deleted)

		// Get total count
		const total = await query.count()

		// Calculate offset
		const offset = (page - 1) * pageSize

		// Get page data
		const messages = await query
			.reverse() // Reverse chronological order
			.offset(offset)
			.limit(pageSize)
			.toArray()

		return {
			messages,
			total,
			page,
			pageSize,
			totalPages: Math.ceil(total / pageSize),
		}
	}

	/**
	 * Get conversation messages (non-paginated)
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @returns Messages
	 */
	async getMessages(conversationId: string, topicId: string = "") {
		const rows = await this.getMessageList(conversationId, topicId)
		console.log("rows ====> ", rows)
		return rows.toArray()
	}

	/**
	 * Get conversation message list
	 * @param conversationId Conversation ID
	 * @param topicId Topic ID
	 * @returns Message list
	 */
	private async getMessageList(
		conversationId: string,
		topicId: string = "",
	): Promise<Collection<any>> {
		try {
			const table = await this.getMessageTable(conversationId)

			try {
				if (topicId) {
					// Use compound index to optimize query performance
					return table
						.where("[message.topic_id+message.send_time]")
						.between([topicId, Dexie.minKey], [topicId, Dexie.maxKey])
				}
				// Use compound index to optimize query performance
				return table
					.where("[message.topic_id+message.send_time]")
					.between(["", Dexie.minKey], ["", Dexie.maxKey])
			} catch (indexError) {
				// If compound index query fails, fall back to simple index
				console.error("使用复合索引查询失败，回退到普通索引", indexError)

				if (topicId) {
					return table.where("message.topic_id").equals(topicId)
				}
				return table.where("message.topic_id").equals("")
			}
		} catch (error) {
			// // Try to handle index error
			// const handled = await this.handleIndexError(error, conversationId)

			// if (handled) {
			// 	// If index error handled successfully, retry query
			// 	return this.getMessages(conversationId, topicId)
			// }

			// Use simple index when compound index fails
			console.error("数据库访问错误，无法获取消息", error)
			const table = await this.getMessageTable(conversationId)

			if (topicId) {
				return table.where("message.topic_id").equals(topicId)
			}
			return table.where("message.topic_id").equals("")
		}
	}

	/**
	 * Add a pending message
	 * @param message Pending message
	 * @returns Pending message ID
	 */
	async addPendingMessage(message: ConversationMessageSend) {
		const table = this.getPendingMessagesTable()
		return table.put(message)
	}

	/**
	 * Get a pending message
	 * @param messageId Message ID
	 * @returns Pending message
	 */
	async getPendingMessage(messageId: string) {
		const table = this.getPendingMessagesTable()
		return table.get(messageId)
	}

	/**
	 * Delete a pending message
	 * @param messageId Message ID
	 * @returns Deletion result
	 */
	async deletePendingMessage(messageId: string) {
		const table = this.getPendingMessagesTable()
		return table.delete(messageId)
	}

	/**
	 * Update status of a pending message
	 * @param messageId Message ID
	 * @param status Status
	 * @returns Update result
	 */
	async updatePendingMessageStatus(
		messageId: string,
		status: SendStatus,
	): Promise<string | null> {
		const table = this.getPendingMessagesTable()
		// Retrieve the existing message first, then update status
		const existingMessage = await table.get(messageId)
		if (existingMessage) {
			return table.put({
				...existingMessage,
				status,
			})
		}

		return null
	}

	/**
	 * Flag a message as revoked
	 * @param messageId Message ID
	 */
	async flagMessageRevoked(conversationId: string, messageId: string) {
		const table = await this.getMessageTable(conversationId)
		if (table) {
			const existingMessage = await table.get(messageId)
			if (existingMessage) {
				table.update(messageId, {
					"message.revoked": true,
				})
			}
		}
	}

	/**
	 * Replace a message
	 * @param conversationId Conversation ID
	 * @param message Message
	 */
	async replaceMessage(conversationId: string, message: SeqResponse<ConversationMessage>) {
		const table = await this.getMessageTable(conversationId)
		if (table) {
			table.update(message.message_id, message)
		}
	}

	/**
	 * Update message status
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 * @param message Message
	 */
	updateMessageStatus(
		conversationId: string,
		messageId: string,
		message: SeqResponse<SeenMessage>,
	) {
		this.getMessageTable(conversationId).then((table) => {
			if (table) {
				table
					.update(messageId, {
						"message.status":
							message.message.unread_count > 0
								? message.message.status
								: ConversationMessageStatus.Read,
						"message.unread_count": message.message.unread_count,
					})
					.then((res) => {
						console.log("updateMessageStatus success", res)
					})
					.catch((err) => {
						console.error("updateMessageStatus error", err)
					})
			}
		})
	}

	/**
	 * Update message unread count
	 * @param conversationId Conversation ID
	 * @param messageId Message ID
	 * @param unreadCount Unread count
	 */
	updateMessageUnreadCount(conversationId: string, messageId: string, unreadCount: number) {
		this.getMessageTable(conversationId).then((table) => {
			if (table) {
				table
					.update(messageId, {
						"message.unread_count": unreadCount,
					})
					.then((res) => {
						console.log("updateMessageUnreadCount success", res, messageId)
					})
					.catch((err) => {
						console.error("updateMessageUnreadCount error", err)
					})
			}
		})
	}

	/**
	 * Update message
	 * @param localMessageId Message ID
	 * @param conversation_id Conversation ID
	 * @param changes Message changes
	 */
	async updateMessage(
		localMessageId: string,
		conversation_id: string,
		changes: UpdateSpec<SeqResponse<ConversationMessage>>,
	) {
		const table = await this.getMessageTable(conversation_id)
		if (table) {
			table.update(localMessageId, changes).catch((err) => {
				console.error("updateMessage error", err)
			})
		}
	}

	/**
	 * Delete messages of a topic
	 * @param conversationId Conversation ID
	 * @param deleteTopicId Topic ID to delete
	 */
	async removeTopicMessages(conversationId: string, deleteTopicId: string) {
		const table = await this.getMessageTable(conversationId)
		if (table) {
			table.where("message.topic_id").equals(deleteTopicId).delete()
		}
	}
}

export default MessageDbService

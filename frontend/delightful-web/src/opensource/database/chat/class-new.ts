/* eslint-disable class-methods-use-this */
import { platformKey } from "@/utils/storage"
import Dexie from "dexie"
import { ChatDb } from "./types"

export const ChatDbSchemaStorageKey = (delightfulId: string) =>
	platformKey(`chat-db-schema/${delightfulId}`)

/**
 * Chat database
 */
class ChatDatabase {
	db: ChatDb

	delightfulId: string | undefined

	constructor() {
		this.db = new Dexie(this.getLocalSchemaKey("default")) as ChatDb
		const { version, schema } = this.getLocalDbSchema("default")
		this.db.version(version).stores(schema)
	}

	switchDb(delightfulId: string) {
		this.delightfulId = delightfulId
		this.db = new Dexie(this.getLocalSchemaKey(delightfulId)) as ChatDb
		const { version, schema } = this.getLocalDbSchema(delightfulId)
		this.db.version(version).stores(schema)
	}

	/**
	 * Update database schema
	 * @param schemaChanges Database schema changes
	 * @returns
	 */
	async changeSchema(schemaChanges: Record<string, string>) {
		if (!this.db || !this.delightfulId) return undefined

		try {
			const oldDb = this.db
			const newDb = new Dexie(oldDb.name)

			newDb.on("blocked", () => false)

			// Workaround: If DB is empty from tables, it needs to be recreated
			if (this.db.tables.length === 0) {
				await this.db.delete()
				newDb.version(1).stores(schemaChanges)
				return (await newDb.open()) as ChatDb
			}

			// Extract current schema in dexie format:
			const currentSchema = this.db.tables.reduce((result, { name, schema }) => {
				// @ts-ignore
				result[name] = [
					`&${schema.primKey.src}`,
					...schema.indexes.map((idx) => idx.src),
				].join(", ")
				return result
			}, {})

			// Save the old database reference for rollback
			const previousDb = this.db

			try {
				// Tell Dexie about current schema:
				newDb.version(previousDb.verno).stores(currentSchema)
				// Tell Dexie about next schema:
				newDb.version(previousDb.verno + 1).stores({
					...currentSchema,
					...schemaChanges,
				})

				// Try opening the new database first
				await newDb.open()

				// Close old database after successful opening
				oldDb.close()

				// set schema
				this.setLocalDbSchema(
					{
						version: previousDb.verno + 1,
						schema: {
							...currentSchema,
							...schemaChanges,
						},
					},
					this.delightfulId,
				)

				this.db = newDb as ChatDb
				return this.db
			} catch (error) {
				// If upgrade fails, close new database and keep using old database
				await newDb.close()
				throw error
			}
		} catch (error) {
			console.error("Schema change failed:", error)
			throw new Error("Failed to update database schema")
		}
	}

	/**
	 * Local database schema key
	 */
	getLocalSchemaKey(delightfulId: string) {
		return ChatDbSchemaStorageKey(delightfulId)
	}

	get defaultSchema() {
		return {
			conversation: "&id, user_organization_code",
			conversation_dots: "&conversation_id",
			organization_dots: "&organization_code",
			topic_dots: "&conversation_topic_id",
			pending_messages: "&message_id",
			disband_group_unconfirm: "&conversation_id",
			file_urls: "&file_id",
			current_conversation_id: "&organization_code",
			record_summary_message_queue: "&send_time",
			text_avatar_cache: "&text",
			topic_list: "&conversation_id",
			editor_draft: "&key, topic_id, conversation_id",
		}
	}

	/**
	 * Get the default schema for message table
	 * @param conversationId Conversation ID
	 * @returns Message table schema
	 */
	getMessageTableSchema() {
		return `&seq_id, message.topic_id, [message.topic_id+message.send_time], message.send_time, message.type, [message.type+message.topic_id]`
	}

	/**
	 * Get the schema of the local database
	 * @returns
	 */
	getLocalDbSchema(delightfulId: string) {
		return JSON.parse(
			localStorage.getItem(this.getLocalSchemaKey(delightfulId)) ||
				JSON.stringify({ version: 1, schema: this.defaultSchema }),
		)
	}

	/**
	 * Cache the local database schema for recovery when reopened
	 * @param schema
	 */
	setLocalDbSchema(
		schema: { version: number; schema: Record<string, string> },
		delightfulId: string,
	) {
		localStorage.setItem(this.getLocalSchemaKey(delightfulId), JSON.stringify(schema))
	}

	/**
	 * Get conversation table
	 * @returns Conversation table
	 */
	getConversationTable() {
		if (!this.db?.conversation) {
			this.changeSchema({
				conversation: this.defaultSchema.conversation,
			})
		}
		return this.db?.conversation
	}

	getTopicListTable() {
		if (!this.db?.topic_list) {
			this.changeSchema({
				topic_list: this.defaultSchema.topic_list,
			})
		}
		return this.db?.topic_list
	}

	getFileUrlsTable() {
		if (!this.db?.file_urls) {
			this.changeSchema({
				file_urls: this.defaultSchema.file_urls,
			})
		}
		return this.db?.file_urls
	}

	getTextAvatarTable() {
		if (!this.db?.text_avatar_cache) {
			this.changeSchema({
				text_avatar_cache: "&text",
			})
		}
		return this.db?.text_avatar_cache
	}

	getEditorDraftTable() {
		if (!this.db?.editor_draft) {
			this.changeSchema({
				editor_draft: this.defaultSchema.editor_draft,
			})
		}
		return this.db?.editor_draft
	}
}

export default ChatDatabase

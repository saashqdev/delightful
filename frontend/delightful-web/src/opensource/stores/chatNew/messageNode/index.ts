// import {
// 	ConversationMessage,
// 	SendStatus,
// 	ConversationMessageStatus,
// } from "@/types/chat/conversation_message"
// import { makeAutoObservable } from "mobx"

// interface BaseInfo {
// 	/** Local temporary id */
// 	temp_id?: string
// 	/** Unique user ID */
// 	delightful_id: string
// 	/** Message sequence ID */
// 	seq_id?: string
// 	/** Message ID */
// 	message_id: string
// }

// interface UserInfo {
// 	nickname: string
// 	avatar: string
// 	delightful_id: string
// }

// // Message node (contains detailed message content)
// export class MessageNode {
// 	// Basic message body information
//   baseInfo: BaseInfo = {
// 		/** Local temporary id */
// 		temp_id: "",
// 		/** Unique user ID */
// 		delightful_id: "",
// 		/** Message sequence ID */
// 		seq_id: "",
// 		/** Message ID */
// 		message_id: "",
// 	}

// 	user: UserInfo = {
// 		nickname: "",
// 		avatar: "",
// 		delightful_id: "",
// 	}

// 	/** Referenced message ID */
// 	refer_message_id?: string
// 	/** Sender message ID */
// 	sender_message_id?: string
// 	/** Conversation ID */
// 	conversation_id: string = ""
// 	/** Message type */
// 	type: string = ""
// 	/** Message content */
// 	message: ConversationMessage = {} as ConversationMessage
// 	/** Whether message has been recalled */
// 	revoked?: boolean = false
// 	/** Send time */
// 	send_time: string = ""
// 	/** Send status */
// 	send_status: SendStatus = SendStatus.Pending
// 	/** Whether message has been read */
// 	seen_status: ConversationMessageStatus = ConversationMessageStatus.Unread
// 	}

// 	constructor() {
// 		makeAutoObservable(this, {}, { autoBind: true })
// 	}
// }

// export default MessageNode

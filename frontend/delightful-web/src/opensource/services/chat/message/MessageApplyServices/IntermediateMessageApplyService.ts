import type { IntermediateResponse, SeqResponse } from "@/types/request"
import { IntermediateMessageType, IntermediateMessage } from "@/types/chat/intermediate_message"
import ConversationService from "@/opensource/services/chat/conversation/ConversationService"

class IntermediateMessageApplyService {
	/**
	 * Apply an intermediate message.
	 * @param message Intermediate message payload.
	 */
	apply(message: IntermediateResponse) {
		switch (message.seq.message.type) {
			case IntermediateMessageType.StartConversationInput:
				this.applyStartConversationInputMessage(message.seq)
				break
			case IntermediateMessageType.EndConversationInput:
				this.applyEndConversationInputMessage(message.seq)
				break
		}
	}

	/**
	 * Apply "start conversation input" message.
	 * @param message Message object.
	 */
	applyStartConversationInputMessage(message: SeqResponse<IntermediateMessage>) {
		ConversationService.startConversationInput(message.conversation_id)
	}

	/**
	 * Apply "end conversation input" message.
	 * @param message Message object.
	 */
	applyEndConversationInputMessage(message: SeqResponse<IntermediateMessage>) {
		ConversationService.endConversationInput(message.conversation_id)
	}
}

export default new IntermediateMessageApplyService()

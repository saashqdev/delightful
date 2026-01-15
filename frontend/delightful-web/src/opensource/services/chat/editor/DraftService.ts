import chatDb from "@/opensource/database/chat"
import EditorDraftStore, { EditorDraft } from "@/opensource/stores/chatNew/editorDraft"
import Logger from "@/utils/log/Logger"
import { cloneDeep, omit } from "lodash-es"

const console = new Logger("DraftService")

class DraftService {
	// Callback for persisting drafts
	persistDraftCallback: number | null = null

	initDrafts() {
		chatDb
			.getEditorDraftTable()
			?.toArray()
			.then((drafts) => {
				EditorDraftStore.initDrafts(drafts)
			})
	}

	// Write draft
	writeDraft(conversationId: string, topicId: string, draft: EditorDraft) {
		EditorDraftStore.setDraft(conversationId, topicId, draft)
		this.persistDraft(conversationId, topicId, draft)
	}

	// Delete draft
	deleteDraft(conversationId: string, topicId: string) {
		EditorDraftStore.deleteDraft(conversationId, topicId)
		this.persistDraft(conversationId, topicId, undefined)
	}

	// Persist draft
	persistDraft(conversationId: string, topicId: string, draft: EditorDraft | undefined) {
		if (this.persistDraftCallback) {
			clearTimeout(this.persistDraftCallback)
		}

		this.persistDraftCallback = requestIdleCallback(() => {
			const table = chatDb.getEditorDraftTable()

			const key = `${conversationId}-${topicId}`

			if (!draft) {
				table?.delete(key)
				return
			}

			const draftWithInfo = cloneDeep({
				key,
				topic_id: topicId,
				conversation_id: conversationId,
				content: draft.content,
				files: draft.files.map((item) => omit(item, ["error", "cancel"])),
			})

			table
				?.put(draftWithInfo)
				.then(() => {
					console.log("Persist draft success")
				})
				.catch((error) => {
					console.error("Persist draft failed", error, draftWithInfo)
				})

			this.persistDraftCallback = null
		})
	}
}

export default new DraftService()

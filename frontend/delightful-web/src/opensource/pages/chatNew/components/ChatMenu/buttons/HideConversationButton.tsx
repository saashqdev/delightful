import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconMessage2X } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { observer } from "mobx-react-lite"
import { useTranslation } from "react-i18next"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import conversationService from "@/opensource/services/chat/conversation/ConversationService"
import { ChatApi } from "@/opensource/apis"
import { userStore } from "@/opensource/models/user"

const HideConversationButton = observer(({ conversationId }: { conversationId: string }) => {
	const { t } = useTranslation("interface")
	const { organizationCode } = userStore.user
	const { conversations } = conversationStore
	const conversation = conversations[conversationId]

	const handleHideConversation = useMemoizedFn(() => {
		if (!organizationCode || !conversation) return

		conversationService.deleteConversation(conversation.id)

		ChatApi.hideConversation(conversation.id)
	})

	if (!conversation) return null

	return (
		<DelightfulButton
			justify="flex-start"
			icon={<DelightfulIcon color="currentColor" component={IconMessage2X} size={20} />}
			size="large"
			type="text"
			danger
			block
			onClick={handleHideConversation}
		>
			{t("chat.floatButton.removeGroup")}
		</DelightfulButton>
	)
})

export default HideConversationButton

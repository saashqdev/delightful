import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import conversationService from "@/opensource/services/chat/conversation/ConversationService"
import { IconMessage2Cancel } from "@tabler/icons-react"
import { useTranslation } from "react-i18next"
import { observer } from "mobx-react-lite"
import { useMemoizedFn } from "ahooks"

const MuteConversationButton = observer(({ conversationId }: { conversationId: string }) => {
	const { t } = useTranslation("interface")
	const { conversations } = conversationStore
	const conversation = conversations[conversationId]

	const handleClick = useMemoizedFn(() => {
		if (!conversation) return
		const isNotDisturb = conversation.is_not_disturb ? 0 : 1
		conversationService.setNotDisturbStatus(conversation.id, isNotDisturb)
	})

	if (!conversation) return null

	return (
		<DelightfulButton
			justify="flex-start"
			icon={<DelightfulIcon component={IconMessage2Cancel} size={20} />}
			size="large"
			type="text"
			block
			onClick={handleClick}
		>
			{conversation.is_not_disturb
				? t("chat.floatButton.disableNoDisturbing")
				: t("chat.floatButton.enableNoDisturbing")}
		</DelightfulButton>
	)
})

export default MuteConversationButton

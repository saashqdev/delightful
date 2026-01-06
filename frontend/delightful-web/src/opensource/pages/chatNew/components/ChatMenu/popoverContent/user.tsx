// import DelightfulButton from "@/opensource/components/base/DelightfulButton"
// import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
// import { IconMenuDeep } from "@tabler/icons-react"
// import { useTranslation } from "react-i18next"
import TopConversationButton from "../buttons/TopConversationButton"
import MuteConversationButton from "../buttons/MuteConversationButton"
import HideConversationButton from "../buttons/HideConversationButton"

export default function UserPopoverContent({ conversationId }: { conversationId: string }) {
	// const { t } = useTranslation("interface")

	return (
		<>
			<TopConversationButton conversationId={conversationId} />
			<MuteConversationButton conversationId={conversationId} />
			{/* <DelightfulButton
				justify="flex-start"
				icon={<DelightfulIcon component={IconMenuDeep} size={20} />}
				size="large"
				type="text"
				block
			>
				{t("chat.floatButton.moveToGroup")}
			</DelightfulButton> */}
			<HideConversationButton conversationId={conversationId} />
		</>
	)
}

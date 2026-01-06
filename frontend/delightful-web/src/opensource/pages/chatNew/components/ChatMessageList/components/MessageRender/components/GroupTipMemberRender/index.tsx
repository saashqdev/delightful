import DelightfulMemberAvatar from "@/opensource/components/business/DelightfulMemberAvatar"
import { getUserName } from "@/utils/modules/chat"
import { observer } from "mobx-react-lite"
import { useTipStyles } from "../../../../hooks/useTipStyles"
import { userStore } from "@/opensource/models/user"
import { useTranslation } from "react-i18next"

const GroupTipMemberRender = observer(({ uid }: { uid: string }) => {
	const { styles } = useTipStyles()
	const { t } = useTranslation("interface")

	const userId = userStore.user.userInfo?.user_id

	if (userId === uid) {
		return t("chat.you")
	}

	return (
		<DelightfulMemberAvatar uid={uid} classNames={{ name: styles.highlight }}>
			{(user) => <span className={styles.highlight}>{getUserName(user)}</span>}
		</DelightfulMemberAvatar>
	)
})

export default GroupTipMemberRender

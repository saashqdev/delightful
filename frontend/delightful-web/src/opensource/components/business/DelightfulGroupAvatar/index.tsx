import type { DelightfulAvatarProps } from "@/opensource/components/base/DelightfulAvatar"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import useGroupInfo from "@/opensource/hooks/chat/useGroupInfo"

interface DelightfulGroupAvatarProps extends DelightfulAvatarProps {
	gid: string
}

const DelightfulGroupAvatar = ({ gid, badgeProps, ...rest }: DelightfulGroupAvatarProps) => {
	const { groupInfo } = useGroupInfo(gid)

	return (
		<DelightfulAvatar src={groupInfo?.group_avatar} badgeProps={badgeProps} {...rest}>
			{groupInfo?.group_name}
		</DelightfulAvatar>
	)
}

export default DelightfulGroupAvatar

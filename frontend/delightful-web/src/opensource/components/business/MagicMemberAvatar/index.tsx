import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { getUserName } from "@/utils/modules/chat"
import { Flex } from "antd"
import { cx } from "antd-style"
import AutoTooltipText from "@/opensource/components/other/AutoTooltipText"
import { memo, useMemo } from "react"
import MemberCardStore from "@/opensource/stores/display/MemberCardStore"
import userInfoStore from "@/opensource/stores/userInfo"
import { computed } from "mobx"
import { observer } from "mobx-react-lite"
import { useStyles } from "./styles"
import type { DelightfulMemberAvatarProps } from "./types"

const DelightfulMemberAvatar = observer(
	({
		uid,
		showName = "none",
		showAvatar = true,
		classNames,
		className,
		showPopover = true,
		children,
		...rest
	}: DelightfulMemberAvatarProps) => {
		const userInfo = useMemo(() => {
			return computed(() => {
				if (uid) {
					return userInfoStore.get(uid)
				}
				return undefined
			})
		}, [uid]).get()

		const { styles } = useStyles({ nameVisible: showName })

		const name = getUserName(userInfo) ?? ""

		const Children: React.ReactNode =
			typeof children === "function" ? children(userInfo) : children

		return (
			<div
				className={cx(showPopover && MemberCardStore.domClassName)}
				data-user-id={userInfo?.user_id}
			>
				{Children ?? (
					<Flex
						vertical={showName === "vertical"}
						align={showName !== "none" ? "center" : undefined}
						gap={4}
						className={className}
					>
						{showAvatar ? (
							<DelightfulAvatar
								shape="square"
								src={userInfo?.avatar_url}
								className={cx(styles.avatar, classNames?.avatar)}
								{...rest}
							>
								{name}
							</DelightfulAvatar>
						) : null}
						{showName !== "none" ? (
							<AutoTooltipText
								maxWidth={showName === "vertical" ? 40 : undefined}
								className={cx(styles.name, classNames?.name)}
							>
								{name}
							</AutoTooltipText>
						) : null}
					</Flex>
				)}
			</div>
		)
	},
)

const memoizedDelightfulMemberAvatar = memo(DelightfulMemberAvatar)

export default memoizedDelightfulMemberAvatar

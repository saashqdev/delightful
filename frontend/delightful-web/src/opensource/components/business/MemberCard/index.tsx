import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { getUserName } from "@/utils/modules/chat"
import { IconBuildingSkyscraper, IconMessage } from "@tabler/icons-react"
import { Descriptions, Flex } from "antd"
import { useTranslation } from "react-i18next"
import { UserType } from "@/types/user"
import { useMemo, useRef } from "react"
import DelightfulSegmented from "@/opensource/components/base/DelightfulSegmented"
import { useMemoizedFn } from "ahooks"
import { useChatWithMember } from "@/opensource/hooks/chat/useChatWithMember"
import MemberCardStore from "@/opensource/stores/display/MemberCardStore"
import { observer } from "mobx-react-lite"
import { useOrganization } from "@/opensource/models/user/hooks"
import useStyles from "./styles"

const enum MemberCardTab {
	BaseInfo = "baseInfo",
	EmployeeProfile = "employeeProfile",
	EmploymentInfo = "employmentInfo",
}

const MemberCard = observer(() => {
	const { t } = useTranslation("interface")
	const { userInfo } = MemberCardStore

	const containerRef = useRef<HTMLDivElement>(null)

	const { styles, cx } = useStyles({
		open: MemberCardStore.open,
		animationDuration: MemberCardStore.animationDuration,
		hidden: !userInfo,
	})

	const { organizations } = useOrganization()
	const organization = useMemo(() => {
		return organizations.find((item) => item.organization_code === userInfo?.organization_code)
	}, [organizations, userInfo?.organization_code])

	const userType = userInfo?.user_type
	const isAi = userType === UserType.AI
	const isNormalPerson = userType === UserType.Normal

	const items = useMemo(() => {
		if (!userInfo) return []
		switch (userType) {
			case UserType.AI:
				return [
					{
						key: "assistantIntroduction",
						label: t("memberCard.assistantIntroduction"),
						children: userInfo.description,
					},
					// {
					// 	key: "origin",
					// 	label: t("memberCard.origin"),
					// 	children: userInfo.description,
					// },
					// {
					// 	key: "creator",
					// 	label: t("memberCard.creator"),
					// 	children: userInfo.description,
					// },
				]
			case UserType.Normal:
				return [
					{
						key: "enterprise/organization",
						label: t("memberCard.enterprise_organization"),
						children: organization?.organization_name,
					},
					{
						key: "title",
						label: t("memberCard.title"),
						children: userInfo.job_title,
					},
					{
						key: "email",
						label: t("memberCard.email"),
						children: userInfo.email,
					},
					...(userInfo.path_nodes?.map((item) => ({
						key: item.department_id,
						label: t("memberCard.department"),
						children: item.department_name,
					})) ?? []),
					{
						key: "phone",
						label: t("memberCard.phone"),
						children: userInfo.phone,
					},
				]
			default:
				return []
		}
	}, [organization?.organization_name, t, userInfo, userType])

	const options = useMemo(() => {
		return [
			{ label: t("memberCard.baseInfo"), value: MemberCardTab.BaseInfo },
			// { label: t("memberCard.employeeProfile"), value: MemberCardTab.EmployeeProfile },
			// { label: t("memberCard.employmentInfo"), value: MemberCardTab.EmploymentInfo },
		]
	}, [t])

	const chatWith = useChatWithMember()

	const handleChatWith = useMemoizedFn(() => {
		if (!userInfo) return
		chatWith(userInfo.user_id)
		MemberCardStore.closeCard(true)
	})

	return (
		<Flex
			className={cx(styles.container, styles.animation)}
			vertical
			gap={14}
			ref={containerRef}
			style={{ top: MemberCardStore.position.y, left: MemberCardStore.position.x }}
			onMouseEnter={() => MemberCardStore.setIsHover(true)}
			onMouseLeave={() => MemberCardStore.setIsHover(false)}
		>
			{/* Header card */}
			<Flex vertical className={styles.header} gap={10}>
				<Flex className={styles.headerTop} gap={14} align="center">
					<DelightfulAvatar className={styles.avatar} src={userInfo?.avatar_url} size={80}>
						{getUserName(userInfo)}
					</DelightfulAvatar>
					<span className={styles.username}>{getUserName(userInfo)}</span>
				</Flex>
				<Flex gap={2} align="center">
					<DelightfulIcon component={IconBuildingSkyscraper} />
					<span className={styles.organization}>{organization?.organization_name}</span>
				</Flex>
				{/* {isNormalPerson && (
					<Flex gap={10}>
						<DelightfulButton
							block
							className={styles.scheduleButton}
							icon={
								<DelightfulIcon
									color="currentColor"
									component={IconCalendarTime}
									size={18}
								/>
							}
						>
							{t("memberCard.viewSchedule")}
						</DelightfulButton>
						<DelightfulButton
							block
							className={styles.tasksAssociatedWithMeButton}
							icon={
								<DelightfulIcon color="currentColor" component={IconSubtask} size={18} />
							}
						>
							{t("memberCard.tasksAssociatedWithMe")}
						</DelightfulButton>
					</Flex>
				)} */}
			</Flex>
			{isNormalPerson && (
				<DelightfulSegmented block className={styles.segmented} options={options} />
			)}
			{/* Detail information */}
			<Descriptions colon={false} column={1} items={items} className={styles.descriptions} />
			{/* Bottom buttons */}
			<Flex vertical gap={10}>
				<DelightfulButton
					hidden={!isNormalPerson}
					block
					size="large"
					type="default"
					className={styles.button}
					icon={<DelightfulIcon color="currentColor" component={IconMessage} size={20} />}
					onClick={handleChatWith}
				>
					{t("memberCard.sendMessage")}
				</DelightfulButton>
				{/* <DelightfulButton
					hidden={!isNormalPerson}
					block
					size="large"
					type="default"
					className={styles.button}
					icon={<DelightfulIcon color="currentColor" component={IconPhoneCall} size={20} />}
				>
					{t("memberCard.call")}
				</DelightfulButton> */}
				<DelightfulButton
					hidden={!isAi}
					block
					size="large"
					type="default"
					className={styles.button}
					icon={<DelightfulIcon color="currentColor" component={IconMessage} size={20} />}
					onClick={handleChatWith}
				>
					{t("memberCard.sendMessage")}
				</DelightfulButton>
				{/* <DelightfulButton
					hidden={!isNormalPerson}
					block
					size="large"
					type="default"
					className={cx(styles.button, styles.shareBusinessCardButton)}
					icon={<DelightfulIcon color="currentColor" component={IconShare} size={20} />}
				>
					{t("memberCard.shareBusinessCard")}
				</DelightfulButton>
				<DelightfulButton
					hidden={!isAi}
					block
					size="large"
					type="default"
					className={cx(styles.button, styles.shareBusinessCardButton)}
					icon={<DelightfulIcon color="currentColor" component={IconShare} size={20} />}
				>
					{t("memberCard.shareBusinessCard")}
				</DelightfulButton> */}
			</Flex>
		</Flex>
	)
})

export default MemberCard

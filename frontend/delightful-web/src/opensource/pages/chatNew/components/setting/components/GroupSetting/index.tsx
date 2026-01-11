import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulMemberAvatar from "@/opensource/components/business/DelightfulMemberAvatar"
import { DelightfulList } from "@/opensource/components/DelightfulList"
import AutoTooltipText from "@/opensource/components/other/AutoTooltipText"
import { IconChevronRight, IconMinus, IconPlus } from "@tabler/icons-react"
import { Col, Flex, message, Row, Switch, Typography } from "antd"
import { useMemo, useRef } from "react"
import { useTranslation } from "react-i18next"
import { useBoolean, useMemoizedFn } from "ahooks"
import type { DelightfulListItemData } from "@/opensource/components/DelightfulList/types"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { ChatApi } from "@/apis"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { Virtuoso } from "react-virtuoso"

import DelightfulGroupAvatar from "@/opensource/components/business/DelightfulGroupAvatar"
import useGroupInfo from "@/opensource/hooks/chat/useGroupInfo"
import { observer } from "mobx-react-lite"
import ConversationService from "@/opensource/services/chat/conversation/ConversationService"
import type { GroupConversationMember } from "@/types/chat/conversation"
import groupInfoService from "@/opensource/services/groupInfo"
import SettingListItem from "../SettingListItem"
import useStyles from "./styles"
import AddGroupMemberModal from "../AddGroupMemberModal"
import useCommonStyles from "../../styles/common"
import UpdateGroupNameModal from "./components/UpdateGroupNameModal"
import RemoveGroupMember from "./components/RemoveGroupMember"
import useInfoService from "@/opensource/services/userInfo"
import { userStore } from "@/opensource/models/user"
import { computed } from "mobx"
import groupInfoStore from "@/opensource/stores/groupInfo"

const enum GroupSettingListItemId {
	UpdateGroupName = "updateGroupName",
	GroupNotice = "groupNotice",
	Setting = "setting",
	GroupAdmin = "groupAdmin",
	TopConversation = "topConversation",
	DisturbMessage = "disturbMessage",
	SpecialAttention = "specialAttention",
	AIRealTimeTranslate = "aiTranslateRealTime",
	GroupInWhich = "groupInWhich",
	GroupType = "groupType",
}

const GroupSetting = observer(() => {
	const { styles } = useStyles()
	const { styles: commonStyles } = useCommonStyles()
	const { t } = useTranslation("interface")
	const containerRef = useRef<HTMLDivElement>(null)

	const { currentConversation: conversation } = conversationStore

	const members = groupInfoStore.currentGroupMembers

	const { groupInfo } = useGroupInfo(conversation?.receive_id)

	const organization = userStore.user.getOrganizationByDelightful(
		conversation?.user_organization_code ?? "",
	)

	const isAdmin = useMemo(() => {
		return computed(() => groupInfo?.group_owner === userStore.user.userInfo?.user_id)
	}, [groupInfo?.group_owner]).get()

	const groupInfoListItem = useMemo(() => {
		return [
			{
				id: GroupSettingListItemId.UpdateGroupName,
				title: t("chat.groupSetting.groupName"),
				extra: (
					<Flex align="center" gap={4} className={styles.groupNameContent}>
						<div className={styles.groupNameContent}>{groupInfo?.group_name}</div>
						<DelightfulIcon component={IconChevronRight} />
					</Flex>
				),
			},
			// {
			// 	id: GroupSettingListItemId.GroupNotice,
			// 	title: t("chat.groupSetting.groupNotice"),
			// 	extra: <DelightfulIcon component={IconChevronRight} />,
			// },
		]
	}, [groupInfo?.group_name, t])

	const [
		updateGroupNameModalOpen,
		{ setTrue: openUpdateGroupNameModal, setFalse: closeUpdateGroupNameModal },
	] = useBoolean(false)

	/**
	 * update群组名称
	 * @param groupName 群组名称
	 */
	const onUpdateGroupName = useMemoizedFn((groupName: string) => {
		console.log("onUpdateGroupName", conversation)
		return ChatApi.updateGroupInfo({
			group_id: conversation?.receive_id ?? "",
			group_name: groupName,
		}).then(() => {
			groupInfoService.updateGroupInfo(conversation?.receive_id ?? "", {
				group_name: groupName,
			})
		})
	})

	/**
	 * 点击群组informationlist项
	 * @param {DelightfulListItemData} item list项数据
	 */
	const onGroupInfoListItemClick = useMemoizedFn(({ id }: DelightfulListItemData) => {
		if (!conversation) return
		switch (id) {
			case GroupSettingListItemId.UpdateGroupName:
				openUpdateGroupNameModal()
				break
			case GroupSettingListItemId.GroupNotice:
				// FIXME: 需要重新implement

				// extraSectionController?.pushSection(
				// 	{
				// 		key: ExtraSectionKey.GroupNotice,
				// 		title: t("chat.groupSetting.groupNotice"),
				// 		Component: GroupNoticeComponent,
				// 	},
				// 	true,
				// )
				break
			default:
				break
		}
	})

	const onTopConversationChange = useMemoizedFn((value: boolean) => {
		if (!conversation) return
		ConversationService.setTopStatus(conversation.id, value ? 1 : 0)
	})

	const onNotDisturbConversationChange = useMemoizedFn((value: boolean) => {
		if (!conversation) return
		ConversationService.setNotDisturbStatus(conversation.id, value ? 1 : 0)
	})

	const listItems = useMemo(() => {
		return [
			{
				id: GroupSettingListItemId.TopConversation,
				title: t("chat.userSetting.topConversation"),
				extra: (
					<Switch
						checked={Boolean(conversation?.is_top)}
						onChange={onTopConversationChange}
					/>
				),
			},
			{
				id: GroupSettingListItemId.DisturbMessage,
				title: t("chat.userSetting.disturbMessage"),
				extra: (
					<Switch
						checked={Boolean(conversation?.is_not_disturb)}
						onChange={onNotDisturbConversationChange}
					/>
				),
			},
			// {
			// 	id: GroupSettingListItemId.SpecialAttention,
			// 	title: t("chat.userSetting.specialAttention"),
			// 	extra: <Switch checked={Boolean(conversation?.is_mark)} />,
			// },
			// {
			// 	id: GroupSettingListItemId.AIRealTimeTranslate,
			// 	title: t("chat.userSetting.aiTranslateRealTime"),
			// },
			// {
			// 	id: GroupSettingListItemId.GroupInWhich,
			// 	title: t("chat.userSetting.GroupInWhich"),
			// },
		]
	}, [
		conversation?.is_not_disturb,
		conversation?.is_top,
		onNotDisturbConversationChange,
		onTopConversationChange,
		t,
	])

	// const groupTypes = useGroupTypes()

	// const groupAdminListItem = useMemo(() => {
	// 	return [
	// 		{
	// 			id: GroupSettingListItemId.GroupAdmin,
	// 			title: (
	// 				<Flex align="center" gap={4}>
	// 					<DelightfulMemberAvatar uid={groupInfo?.group_owner} />
	// 					<Flex vertical>
	// 						<span>{t("chat.groupSetting.groupAdmin")}</span>
	// 						<span className={styles.groupAdminTip}>
	// 							{t("chat.groupSetting.groupAdminTip")}
	// 						</span>
	// 					</Flex>
	// 				</Flex>
	// 			),
	// 			extra: <DelightfulIcon component={IconChevronRight} />,
	// 		},
	// 		{
	// 			id: GroupSettingListItemId.GroupType,
	// 			title: t("chat.groupSetting.groupType"),
	// 			extra: (
	// 				<Flex align="center" gap={4}>
	// 					{groupInfo?.group_type
	// 						? groupTypes[groupInfo?.group_type].label
	// 						: undefined}
	// 					<DelightfulIcon component={IconChevronRight} />
	// 				</Flex>
	// 			),
	// 		},
	// 	]
	// }, [groupInfo?.group_owner, groupInfo?.group_type, groupTypes, styles.groupAdminTip, t])

	const onSubmitAddMember = useMemoizedFn<(typeof ChatApi)["addGroupUsers"]>((data) => {
		return ChatApi.addGroupUsers(data)
			.then(() => groupInfoService.fetchGroupMembers(conversation?.receive_id ?? ""))
			.then((members = []) =>
				useInfoService.fetchUserInfos(members.map((item) => item.user_id) ?? [], 2),
			)
			.then(() => {
				message.success(t("chat.groupSetting.addMemberSuccess"))
			})
	})

	// 添加成员
	const [
		addGroupMemberModalOpen,
		{ setTrue: openAddGroupMemberModal, setFalse: closeAddGroupMemberModal },
	] = useBoolean(false)

	// 移除成员
	const [
		removeGroupMemberModalOpen,
		{ setTrue: openRemoveGroupMemberModal, setFalse: closeRemoveGroupMemberModal },
	] = useBoolean(false)

	const extraUserIds = useMemo(() => members.map((item) => item.user_id), [members])

	/**
	 * 退出群聊
	 */
	const handleLeaveGroup = useMemoizedFn(() => {
		if (!conversation) return
		const isOwner = groupInfo?.group_owner === userStore.user.userInfo?.user_id
		if (isOwner) {
			DelightfulModal.info({
				centered: true,
				title: t("common.tip", { ns: "interface" }),
				content: t("chat.groupSetting.ownerLeaveGroupTip", { ns: "interface" }),
				okText: t("common.confirm", { ns: "interface" }),
			})
			return
		}

		// 二次确认
		DelightfulModal.confirm({
			centered: true,
			title: t("common.tip", { ns: "interface" }),
			content: t("chat.groupSetting.leaveGroupConfirm", { ns: "interface" }),
			okText: t("common.confirm", { ns: "interface" }),
			cancelText: t("common.cancel", { ns: "interface" }),
			onOk: () => {
				ChatApi.leaveGroup({ group_id: conversation.receive_id }).then(() => {
					message.success(t("chat.groupSetting.leaveGroupSuccess"))
					ConversationService.deleteConversation(conversation.id)
				})
			},
		})
	})

	/**
	 * 解散群聊
	 */
	const handleRemoveGroup = useMemoizedFn(() => {
		if (!conversation) return

		DelightfulModal.confirm({
			centered: true,
			title: t("common.tip", { ns: "interface" }),
			content: t("chat.groupSetting.removeGroupConfirm", { ns: "interface" }),
			okText: t("common.confirm", { ns: "interface" }),
			cancelText: t("common.cancel", { ns: "interface" }),
			onOk: () => {
				ChatApi.removeGroup({ group_id: conversation.receive_id }).then(() => {
					message.success(t("chat.groupSetting.removeGroupSuccess"))
					ConversationService.deleteConversation(conversation.id)
				})
			},
		})
	})

	// 计算网格property
	const ITEMS_PER_ROW = 5
	const itemsData = useMemo(() => {
		// 若是管理员，添加operationbutton
		if (isAdmin) {
			return [...members, { id: "add-member" }, { id: "remove-member" }]
		}

		return members
	}, [members, isAdmin])

	// 计算行数
	const totalRows = Math.ceil(itemsData.length / ITEMS_PER_ROW)

	// 虚拟list渲染function
	const renderMemberRow = (index: number) => {
		const startIdx = index * ITEMS_PER_ROW
		const rowItems = itemsData.slice(startIdx, startIdx + ITEMS_PER_ROW)

		return (
			<Row key={index} gutter={[16, 8]} align="middle" justify="start">
				{rowItems.map((item) => {
					// 添加成员button
					if (item.id === "add-member") {
						return (
							<Col key="add-member" className={styles.member}>
								<Flex vertical align="center" justify="center" gap={4}>
									<DelightfulButton
										className={styles.addMember}
										icon={<DelightfulIcon component={IconPlus} size={24} />}
										type="default"
										onClick={openAddGroupMemberModal}
									/>
									<AutoTooltipText maxWidth={50} className={styles.text}>
										{t("chat.userSetting.addMember")}
									</AutoTooltipText>
								</Flex>
							</Col>
						)
					}

					// 移除成员button
					if (item.id === "remove-member") {
						return (
							<Col key="remove-member" className={styles.member}>
								<Flex vertical align="center" justify="center" gap={4}>
									<DelightfulButton
										className={styles.addMember}
										icon={<DelightfulIcon component={IconMinus} size={24} />}
										type="default"
										onClick={openRemoveGroupMemberModal}
									/>
									<AutoTooltipText maxWidth={50} className={styles.text}>
										{t("chat.userSetting.removeMember")}
									</AutoTooltipText>
								</Flex>
							</Col>
						)
					}

					// 普通成员头像
					return (
						<Col
							key={(item as GroupConversationMember).user_id}
							className={styles.member}
						>
							<DelightfulMemberAvatar
								size={44}
								uid={(item as GroupConversationMember).user_id}
								showName="vertical"
							/>
						</Col>
					)
				})}
			</Row>
		)
	}

	return (
		<Flex vertical gap={10} align="start" className={styles.container}>
			<Flex gap={6} align="center" className={styles.groupInfo}>
				<DelightfulGroupAvatar
					gid={conversation?.receive_id ?? ""}
					className={styles.groupAvatar}
					size={40}
				/>
				<Flex vertical className={styles.groupInfoContent}>
					<div className={styles.groupName}>{groupInfo?.group_name}</div>
					<div className={styles.groupNotice}>
						{t("chat.groupSetting.groupInWhich", {
							name: organization?.organization_name,
						})}
					</div>
				</Flex>
			</Flex>
			<div className={styles.memberSection}>
				<div
					ref={containerRef}
					style={{ width: "100%", height: totalRows > 4 ? 210 : "auto" }}
				>
					{totalRows <= 4 ? (
						// 当行数较少时直接渲染，避免不必要的虚拟化
						Array.from({ length: totalRows }).map((_, index) => renderMemberRow(index))
					) : (
						// 否则使用虚拟list
						<Virtuoso
							style={{
								height: 210,
								width: "100%",
								overflowY: "auto",
								overflowX: "hidden",
							}}
							totalCount={totalRows}
							itemContent={renderMemberRow}
							overscan={5}
						/>
					)}
				</div>
			</div>
			<Typography.Text className={styles.title}>{t("chat.groupInfo")}</Typography.Text>
			<DelightfulList
				gap={0}
				className={styles.list}
				items={groupInfoListItem}
				listItemComponent={SettingListItem}
				onItemClick={onGroupInfoListItemClick}
			/>
			<Typography.Text className={styles.title}>
				{t("chat.groupSetting.userSetting")}
			</Typography.Text>
			<DelightfulList
				gap={0}
				className={styles.list}
				items={listItems}
				listItemComponent={SettingListItem}
			/>
			{/* {isAdmin ? (
				<>
					<Typography.Text className={styles.title}>
						{t("chat.groupSetting.groupAdmin")}
					</Typography.Text>
					<DelightfulList
						gap={0}
						className={styles.list}
						items={groupAdminListItem}
						// @ts-ignore
						listItemComponent={SettingListItem}
					/>
				</>
			) : null} */}
			<Flex vertical flex={1} className={commonStyles.buttonList}>
				{/* <DelightfulButton type="link" danger block>
					{t("chat.chatSetting.deleteMessageRecords")}
				</DelightfulButton> */}
				<DelightfulButton type="link" danger block onClick={handleLeaveGroup}>
					{t("chat.groupSetting.leaveGroup")}
				</DelightfulButton>
				{isAdmin && (
					<DelightfulButton type="link" danger block onClick={handleRemoveGroup}>
						{t("chat.groupSetting.dissolveGroup")}
					</DelightfulButton>
				)}
			</Flex>
			<UpdateGroupNameModal
				initialGroupName={groupInfo?.group_name}
				conversationId={conversation?.receive_id ?? ""}
				open={updateGroupNameModalOpen}
				onClose={closeUpdateGroupNameModal}
				onSave={onUpdateGroupName}
			/>
			<AddGroupMemberModal
				open={addGroupMemberModalOpen}
				onClose={closeAddGroupMemberModal}
				onSubmit={onSubmitAddMember}
				groupId={conversation?.receive_id ?? ""}
				extraUserIds={extraUserIds}
			/>
			<RemoveGroupMember
				groupId={conversation?.receive_id ?? ""}
				open={removeGroupMemberModalOpen}
				onClose={closeRemoveGroupMemberModal}
			/>
		</Flex>
	)
})

export default GroupSetting

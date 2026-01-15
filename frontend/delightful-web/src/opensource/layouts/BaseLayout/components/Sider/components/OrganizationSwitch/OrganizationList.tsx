import { memo, useRef } from "react"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { IconCheck, IconPlus } from "@tabler/icons-react"
import DelightfulScrollBar from "@/opensource/components/base/DelightfulScrollBar"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { colorScales } from "@/opensource/providers/ThemeProvider/colors"
import { Badge, Flex, Affix } from "antd"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import type { User } from "@/types/user"
import { useAccount } from "@/opensource/stores/authentication"
import { useAccount as useAccountHook, useUserInfo } from "@/opensource/models/user/hooks"
import AccountModal from "@/opensource/pages/login/AccountModal"
import { useClusterConfig } from "@/opensource/models/config/hooks"
import { userService } from "@/services"
import OrganizationDotsStore from "@/opensource/stores/chatNew/dots/OrganizationDotsStore"
import { observer } from "mobx-react-lite"
import { useOrganizationListStyles } from "./styles"
import { interfaceStore } from "@/opensource/stores/interface"

interface OrganizationItemProps {
	disabled: boolean
	isSelected: boolean
	onClick?: () => void
	account: User.UserAccount
	organization: User.DelightfulOrganization
}

const OrganizationItem = observer((props: OrganizationItemProps) => {
	const { disabled, organization, account, onClick, isSelected } = props

	const { styles, cx } = useOrganizationListStyles()

	const { accountSwitch } = useAccount()

	const { userInfo } = useUserInfo()

	const unreadDotsGroupByOrganization = OrganizationDotsStore.dots

	const switchOrganization = useMemoizedFn(
		async (accountInfo: User.UserAccount, organizationInfo: User.DelightfulOrganization) => {
			if (disabled) {
				return
			}

			try {
				interfaceStore.setIsSwitchingOrganization(true)
				// Account mismatch requires account switch
				if (accountInfo?.delightful_id !== userInfo?.delightful_id) {
					await accountSwitch(
						accountInfo?.delightful_id,
						organizationInfo.delightful_user_id,
						organizationInfo.delightful_organization_code,
					)
				} else if (
					organizationInfo?.delightful_organization_code !== userInfo?.organization_code
				) {
					try {
						await userService.switchOrganization(
							organizationInfo.delightful_user_id,
							organizationInfo.delightful_organization_code,
							userInfo,
						)
					} catch (err) {
						console.error(err)
						// Switch failed, restore current organization
						userService.setDelightfulOrganizationCode(userInfo?.organization_code)
						userService.setUserInfo(userInfo)
					}
				}
				onClick?.()
			} catch (err) {
				console.error(err)
			} finally {
				interfaceStore.setIsSwitchingOrganization(false)
			}
		},
	)

	return (
		<div
			key={organization.delightful_organization_code}
			onClick={() => switchOrganization(account, organization)}
			className={cx(styles.item, {
				[styles.itemDisabled]: disabled,
				[styles.itemSelected]: isSelected,
			})}
		>
			<div className={styles.itemIcon}>
				<DelightfulAvatar
					src={organization.organization_logo}
					size={30}
					className={cx(styles.avatar, {
						[styles.avatarDisabled]: disabled,
					})}
				>
					{organization.organization_name}
				</DelightfulAvatar>
			</div>
			<div className={styles.itemText}>{organization.organization_name}</div>
			<Flex>
				{isSelected ? (
					<DelightfulIcon
						color={colorScales.brand[5]}
						size={20}
						stroke={2}
						component={IconCheck}
					/>
				) : (
					<Badge
						count={
							unreadDotsGroupByOrganization[organization.delightful_organization_code]
						}
					/>
				)}
			</Flex>
		</div>
	)
})

interface OrganizationListItemProps {
	onClose?: () => void
}

function OrganizationListComponent(props: OrganizationListItemProps) {
	const { onClose } = props

	const { styles, cx } = useOrganizationListStyles()
	const { t } = useTranslation("interface")

	const ref = useRef<HTMLDivElement>({} as HTMLDivElement)

	const { accounts } = useAccountHook()
	const { clustersConfig } = useClusterConfig()

	const { userInfo } = useUserInfo()

	const handleAddAccount = () => {
		AccountModal()
		onClose?.()
	}

	return (
		<div className={styles.container}>
			<DelightfulScrollBar
				className={styles.scroll}
				autoHide={false}
				scrollableNodeProps={{
					ref,
				}}
			>
				{accounts.map((account, index) => {
					const validOrgs = account.organizations.map(
						(org) => org.third_platform_organization_code,
					)

					return (
						<div className={styles.group} key={account.delightful_id}>
							<Affix target={() => ref.current}>
								<div className={styles.groupHeader}>
									<div className={styles.groupSection}>
										<span>Account {index + 1}</span>
										<span className={styles.groupHeaderLine} />
									</div>
									<div className={styles.groupWrapper}>
										<DelightfulAvatar
											src={account.avatar}
											className={cx(styles.avatar, {
												[styles.avatarDisabled]: false,
											})}
										>
											{account.nickname}
										</DelightfulAvatar>
										<span className={styles.groupTitle}>
											{account.nickname}
										</span>
										<span className={styles.groupDesc}>
											{clustersConfig?.[account.deployCode]?.name}
										</span>
									</div>
								</div>
							</Affix>
							{account.organizations?.map((organization) => {
								return (
									<OrganizationItem
										key={organization.delightful_organization_code}
										onClick={onClose}
										account={account}
										disabled={
											!validOrgs.includes(
												organization.delightful_organization_code,
											)
										}
										isSelected={
											userInfo?.user_id === organization?.delightful_user_id
										}
										organization={organization}
									/>
								)
							})}
						</div>
					)
				})}
			</DelightfulScrollBar>
			<div className={styles.button} onClick={handleAddAccount}>
				<IconPlus size={20} />
				{t("sider.addAccount")}
			</div>
		</div>
	)
}

const OrganizationList = memo(OrganizationListComponent)

export default OrganizationList

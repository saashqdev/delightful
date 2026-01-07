import { RoutePath } from "@/const/routes"
import { userStore } from "@/opensource/models/user"
import { useAccount } from "@/opensource/stores/authentication"
import { IconLogout, IconSwitchHorizontal } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { Modal } from "antd"
import { createStyles } from "antd-style"
import { observer } from "mobx-react-lite"
import { useState } from "react"
import { useTranslation } from "react-i18next"
import { useNavigate } from "react-router-dom"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"
import ComponentRender from "@/opensource/components/ComponentRender"
import DelightfulDropdown from "@/opensource/components/base/DelightfulDropdown"

interface AccountActionsProps {
	onSwitchOrganization?: () => void
	onLogout?: () => void
	fetchWorkspaces?: () => void
}

const useStyles = createStyles(({ token, css, isDarkMode }) => {
	const backgroundColor = isDarkMode
		? token.delightfulColorScales.grey[0]
		: token.delightfulColorScales.white

	return {
		container: {
			display: "flex",
			flexDirection: "column",
			justifyContent: "space-between",
			borderTop: `1px solid ${token.colorBorder}`,
			fontSize: "14px",
		},
		switchOrganization: {
			fontSize: "16px",
			color: "#333",
		},
		logoutItem: {
			color: token.colorError,
		},
		icon: {
			width: "20px",
			height: "20px",
		},
		item: {
			display: "flex",
			alignItems: "center",
			padding: "10px 11px",
			gap: "8px",
			cursor: "pointer",
		},
		dropdownRender: css`
			width: 300px;
			flex-direction: column;
			align-items: flex-start;
			margin-right: 10px;
			gap: 10px;
			background-color: ${backgroundColor};
			box-shadow: 0 4px 14px 0 rgba(0, 0, 0, 0.1), 0 0 1px 0 rgba(0, 0, 0, 0.3);
			border-radius: 8px;
		`,
	}
})

export default observer(function AccountActions({
	onSwitchOrganization = () => console.log("Switch organization"),
	onLogout = () => console.log("Logout"),
}: AccountActionsProps) {
	const { t } = useTranslation("interface")
	const { styles, cx } = useStyles()
	const navigate = useNavigate()
	const [modalVisible, setModalVisible] = useState(false)
	/** Clear authorization */
	const { accountLogout, accountSwitch } = useAccount()

	const [modal, contextHolder] = Modal.useModal()

	const onClose = () => {
		setModalVisible(false)
		onSwitchOrganization?.()
	}
	/** Logout */
	const handleLogout = useMemoizedFn(async () => {
		const config = {
			title: t("sider.exitTitle"),
			content: t("sider.exitContent"),
		}
		console.log(1111)
		const confirmed = await modal.confirm(config)
		console.log(confirmed, "confirmed")
		if (confirmed) {
			const accounts = userStore.account.accounts

		// If and only if multiple accounts exist, switch account first, then remove account
			if (accounts?.length > 1) {
				const info = userStore.user.userInfo
				const otherAccount = accounts.filter(
					(account) => account.delightful_id !== info?.delightful_id,
				)?.[0]

				const targetOrganization = otherAccount?.organizations.find(
					(org) => org.delightful_organization_code === otherAccount?.organizationCode,
				)

				accountSwitch(
					targetOrganization?.delightful_id ?? "",
					targetOrganization?.delightful_id ?? "",
					targetOrganization?.delightful_organization_code ?? "",
				).catch(console.error)

				if (info?.delightful_id) {
					await accountLogout(info?.delightful_id)
					/** Broadcast account deletion */
					BroadcastChannelSender.deleteAccount(info?.delightful_id, { navigateToLogin: false })
				}
			} else {
				await accountLogout()
				/** Broadcast account deletion */
				BroadcastChannelSender.deleteAccount(undefined, { navigateToLogin: true })
				navigate(RoutePath.Login)
			}
			onLogout?.()
		}
	})

	return (
		<div className={styles.container}>
			<DelightfulDropdown
				trigger={["click"]}
				placement="top"
				open={modalVisible}
				onOpenChange={setModalVisible}
				dropdownRender={(originNode) => (
					<div className={styles.dropdownRender}>
						<ComponentRender componentName="OrganizationList" onClose={onClose}>
							{originNode}
						</ComponentRender>
					</div>
				)}
			>
				<div className={styles.item}>
					<IconSwitchHorizontal className={styles.icon} /> <span>Switch Organization</span>
				</div>
			</DelightfulDropdown>
			<div className={cx(styles.item, styles.logoutItem)} onClick={handleLogout}>
				<IconLogout className={styles.icon} /> <span>Logout</span>
			</div>
			{contextHolder}
		</div>
	)
})

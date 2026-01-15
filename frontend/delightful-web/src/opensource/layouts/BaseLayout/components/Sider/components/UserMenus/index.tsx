import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulMenu from "@/opensource/components/base/DelightfulMenu"
import { useAccount } from "@/opensource/stores/authentication"
import {
	IconCheck,
	IconChevronRight,
	IconLogout,
	IconUserCog,
	IconWorld,
} from "@tabler/icons-react"
import { useBoolean, useMemoizedFn } from "ahooks"
import type { MenuProps } from "antd"
import { Popover, Modal, Flex } from "antd"
import { useMemo, type PropsWithChildren } from "react"
import { useTranslation } from "react-i18next"
import { last } from "lodash-es"
import { RoutePath } from "@/const/routes"
import { useStyles } from "./styles"
import { UserMenuKey } from "./constants"
import { ItemType } from "antd/es/menu/interface"
import { useTheme } from "antd-style"
import {
	setGlobalLanguage,
	useGlobalLanguage,
	useSupportLanguageOptions,
} from "@/opensource/models/config/hooks"
import { SettingSection } from "@/opensource/pages/settings/types"
import useNavigate from "@/opensource/hooks/useNavigate"
import { userStore } from "@/opensource/models/user"
import { observer } from "mobx-react-lite"
import { BroadcastChannelSender } from "@/opensource/broadcastChannel"

interface UserMenusProps extends PropsWithChildren {}

const UserMenus = observer(function UserMenus({ children }: UserMenusProps) {
	const { t } = useTranslation("interface")
	const { styles, cx } = useStyles()
	const { delightfulColorUsages } = useTheme()

	const navigate = useNavigate()
	const [modal, contextHolder] = Modal.useModal()

	const [open, { setFalse, set }] = useBoolean(false)

	/** Clear authorization */
	const { accountLogout, accountSwitch } = useAccount()

	/** Logout */
	const handleLogout = useMemoizedFn(async () => {
		const config = {
			title: t("sider.exitTitle"),
			content: t("sider.exitContent"),
		}
		const confirmed = await modal.confirm(config)
		if (confirmed) {
			const accounts = userStore.account.accounts

			// When multiple accounts exist, prioritize account switching before account removal
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
					/** Broadcast delete account */
					BroadcastChannelSender.deleteAccount(info?.delightful_id, {
						navigateToLogin: false,
					})
				}
			} else {
				await accountLogout()
				/** Broadcast delete account */
				BroadcastChannelSender.deleteAccount(undefined, { navigateToLogin: true })
				navigate(RoutePath.Login)
			}
		}
	})

	const accountManagement = useMemoizedFn(() => {
		navigate(RoutePath.Settings, { state: { type: SettingSection.ACCOUNT_MANAGE } })
	})

	const deviceManagement = useMemoizedFn(() => {
		navigate(RoutePath.Settings, { state: { type: SettingSection.LOGIN_DEVICES } })
	})

	/** Current language */
	const language = useGlobalLanguage(true)
	/** Language list */
	const languageList = useSupportLanguageOptions()

	const languageOptions = useMemo(() => {
		return languageList.reduce((acc, item) => {
			const label = item.translations?.[item.value] ?? item.label

			acc.push({
				key: item.value,
				label: (
					<Flex align="center" justify="space-between">
						<span className={styles.menuItemTopName}>{label}</span>
						{item.value === language && (
							<DelightfulIcon
								className={styles.arrow}
								component={IconCheck}
								color={delightfulColorUsages.primary.default}
							/>
						)}
					</Flex>
				),
			})

			if (item.value === "auto") {
				acc.push({
					type: "divider",
				})
			}

			return acc
		}, [] as ItemType[])
	}, [
		languageList,
		styles.menuItemTopName,
		styles.arrow,
		language,
		delightfulColorUsages.primary.default,
	])

	// const isAdmin = userStore.user.isAdmin

	const menu = useMemo<MenuProps["items"]>(() => {
		return [
			{
				label: (
					<Flex align="center" justify="center" gap={24}>
						<span>{t("sider.switchLanguage")}</span>
						<DelightfulIcon className={styles.arrow} component={IconChevronRight} />
					</Flex>
				),
				key: UserMenuKey.SwitchLanguage,
				icon: <DelightfulIcon size={20} component={IconWorld} color="currentColor" />,
				children: languageOptions,
			},
			{
				label: t("sider.accountManagement"),
				key: UserMenuKey.AccountManagement,
				icon: <DelightfulIcon size={20} component={IconUserCog} color="currentColor" />,
			},
			// {
			// 	label: t("sider.deviceManagement"),
			// 	key: UserMenuKey.DeviceManagement,
			// 	icon: <DelightfulIcon size={20} component={IconDeviceMobile} color="currentColor" />,
			// },
			// isAdmin && {
			// 	type: "divider",
			// },
			// isAdmin && {
			// 	label: t("sider.admin"),
			// 	key: UserMenuKey.Admin,
			// 	icon: <DelightfulIcon size={20} component={IconDeviceImacCog} color="currentColor" />,
			// },
			{
				type: "divider",
			},
			{
				label: t("sider.logout"),
				icon: <DelightfulIcon size={20} component={IconLogout} color="currentColor" />,
				danger: true,
				key: UserMenuKey.Logout,
			},
		].filter(Boolean) as ItemType[]
	}, [t, styles.arrow, languageOptions])

	const selectKeys = useMemo(
		() => (language ? [UserMenuKey.SwitchLanguage, language] : []),
		[language],
	)

	// const navigateToAdmin = useMemoizedFn(() => {
	// 	openNewTab(AdminRoutePath.AdminHome.replace("*", AdminRoutePath.Ai))
	// })

	const handleMenuClick = useMemoizedFn<Exclude<MenuProps["onClick"], undefined>>(
		({ key, keyPath }) => {
			switch (last(keyPath)) {
				case UserMenuKey.Logout:
					handleLogout()
					break
				case UserMenuKey.SwitchLanguage:
					setGlobalLanguage(key)
					break
				case UserMenuKey.AccountManagement:
					accountManagement()
					break
				case UserMenuKey.DeviceManagement:
					deviceManagement()
					break
				// case UserMenuKey.Admin:
				// 	navigateToAdmin()
				// break
				default:
					break
			}
			setFalse()
		},
	)

	return (
		<>
			<Popover
				classNames={{ root: styles.popover }}
				placement="rightTop"
				arrow={false}
				open={open}
				onOpenChange={set}
				content={
					<DelightfulMenu
						rootClassName={cx(styles.menu)}
						items={menu}
						expandIcon={null}
						onClick={handleMenuClick}
						selectedKeys={selectKeys}
					/>
				}
				trigger="click"
			>
				{children}
			</Popover>
			{contextHolder}
		</>
	)
})

export default UserMenus

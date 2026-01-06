import { Flex, Menu } from "antd"
import { forwardRef, useMemo } from "react"
import { IconDots } from "@tabler/icons-react"
import { useLocation } from "react-router"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { useMemoizedFn } from "ahooks"
import { useGlobalLanguage } from "@/opensource/models/config/hooks"
import Divider from "@/opensource/components/other/Divider"
import type { MenuItemType } from "antd/es/menu/interface"
import { useAutoCollapsed } from "./hooks"
import { useStyles } from "./styles"
import UserMenus from "./components/UserMenus"
import OrganizationSwitch from "./components/OrganizationSwitch"
import { RoutePath } from "@/const/routes"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { userStore } from "@/opensource/models/user"
import { observer } from "mobx-react-lite"

interface SiderProps {
	collapsed?: boolean
	className?: string
	menuItems?: Array<Array<MenuItemType>>
}

const userSquareRoundedKeys = [
	RoutePath.ContactsOrganization.replace(":id?", ""), // Replace route id parameter for convenient validation
	RoutePath.ContactsAiAssistant,
	RoutePath.ContactsMyFriends,
	RoutePath.ContactsMyGroups,
]

const Sider = observer(
	forwardRef<HTMLDivElement, SiderProps>(function Sider(
		{ collapsed = false, className, menuItems }: SiderProps,
		ref,
	) {
		const navigate = useNavigate()
		const { pathname } = useLocation()

		// Check if current path is contacts-related route
		const isUserSquareRoundedActive = useMemo(() => {
			return userSquareRoundedKeys.some((key) => pathname.startsWith(key))
		}, [pathname])

		// Calculate selected state
		const selectedKeys = useMemo(() => {
			// If contacts-related route, return contacts key
			if (isUserSquareRoundedActive) {
				return [RoutePath.ContactsOrganization]
			}
			// For other cases, return current path
			return [pathname]
		}, [pathname, isUserSquareRoundedActive])

		const language = useGlobalLanguage(false)

		const { userInfo } = userStore.user

		const { styles, cx } = useStyles({ collapsed: useAutoCollapsed(collapsed), language })

		const handleNavigate = useMemoizedFn(({ key }: { key: string }) => {
			navigate(key)
		})

		const OrganizationSwitchChildren = useMemo(
			() => (
				<div className={styles.icon}>
					<DelightfulIcon color="currentColor" size={16} component={IconDots} />
				</div>
			),
			[styles.icon],
		)

		return (
			<Flex
				ref={ref}
				className={cx(styles.sider, className)}
				vertical
				align="center"
				justify="space-between"
			>
				<UserMenus>
					<DelightfulAvatar src={userInfo?.avatar} size={40}>
						{userInfo?.nickname}
					</DelightfulAvatar>
				</UserMenus>
				<Divider direction="horizontal" className={styles.divider} />
				<Flex vertical flex={1} className={styles.menus}>
					{menuItems?.map((menu, index) => {
						const key = `index-${index}`
						return (
							<Menu
								key={key}
								mode="inline"
								selectedKeys={selectedKeys}
								className={cx(styles.menu)}
								items={menu}
								onClick={handleNavigate}
							/>
						)
					})}
				</Flex>
				<Divider direction="horizontal" className={styles.divider} />
				<Flex gap={4} align="center" className={styles.organizationSwitchWrapper}>
					<OrganizationSwitch showPopover={false} />
					<OrganizationSwitch showPopover>
						{OrganizationSwitchChildren}
					</OrganizationSwitch>
				</Flex>
			</Flex>
		)
	}),
)

export default Sider

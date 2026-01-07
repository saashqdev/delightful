import SwitchOrganization from "@/layouts/BaseLayout/components/Sider/components/OrganizationSwitch"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import UserMenus from "@/opensource/layouts/BaseLayout/components/Sider/components/UserMenus"
import { userStore } from "@/opensource/models/user"
import { IconLayoutGrid } from "@tabler/icons-react"
import { useResponsive } from "ahooks"
import { Flex, Tooltip } from "antd"
import { createStyles, cx } from "antd-style"
import { observer } from "mobx-react-lite"
import { useMemo } from "react"
import { Outlet, useLocation, useNavigate } from "react-router-dom"
import DelightfulLogo from "./assets/svg/be_delightful_logo.svg"

const useStyles = createStyles(({ token }) => ({
	container: {
		display: "flex",
		overflow: "hidden",
		background: "#F9F9F9",
		height: "calc(100% - 44px)",
	},
	superContainer: {
		height: "100%",
	},
	normalContainer: {
		height: "calc(100% - 44px)",
	},
	sideNavContainer: {
		width: "50px",
		minWidth: "50px",
		height: "100%",
		background: "#fff",
		borderRight: `1px solid ${token.colorBorderSecondary}`,
		display: "flex",
		flexDirection: "column",
		alignItems: "center",
		justifyContent: "space-between",
		padding: 10,
	},
	menuItem: {
		display: "flex",
		alignItems: "center",
		justifyContent: "center",
		width: 30,
		height: 30,
		borderRadius: 8,
		cursor: "pointer",
		color: token.delightfulColorUsages.text[2],
		userSelect: "none",
		"&:not(:last-child)": {
			marginBottom: 10,
		},
		"&:hover": {
			color: token.delightfulColorUsages.text[1],
			backgroundColor: token.delightfulColorUsages.fill[0],
		},
		"&:active": {
			color: token.delightfulColorUsages.text[1],
			backgroundColor: token.delightfulColorUsages.fill[1],
		},
	},
	active: {
		color: `${token.colorPrimary} !important`,
		backgroundColor: `${token.delightfulColorUsages.primaryLight.default} !important`,
	},
	content: {
		flex: 1,
		overflow: "hidden",
	},
	logo: {
		width: "20px",
		height: "20px",
	},
}))

// Define mapping from routes to menu items
const getMenuKeyFromPath = (pathname: string) => {
	const pathSegments = pathname.split("/")
	const lastSegment = pathSegments[pathSegments.length - 1]

	// Default to workspace
	if (lastSegment === "be-delightful") {
		return "workspace"
	}

	// Other sub-routes
	return lastSegment
}

// Define mapping from menu items to routes
const menuKeyToPath = {
	workspace: "workspace",
	archived: "archived",
	files: "files",
}

function BeDelightful() {
	const { styles } = useStyles()
	const navigate = useNavigate()
	const location = useLocation()
	const { userInfo } = userStore.user
	// Determine active menu item based on current path
	const selectedKey = getMenuKeyFromPath(location.pathname)
	// Handle menu item selection
	const handleMenuSelect = ({ key }: { key: string }) => {
		const basePath = location.pathname.split("/")[1] // Get base path (super or be-delightful)
		navigate(`/${basePath}/${menuKeyToPath[key as keyof typeof menuKeyToPath]}`)
	}

	const menuItems = useMemo(() => {
		return [
			{
				key: "workspace",
				icon: <DelightfulIcon component={IconLayoutGrid} size={20} />,
				label: "Workspace",
			},
			// {
			// 	key: "archived",
			// 	icon: <DelightfulIcon component={IconArchive} size={20} />,
			// 	label: "Archived",
			// },
			// {
			// 	key: "files",
			// 	icon: <DelightfulIcon component={IconFolder} size={20} />,
			// 	label: "Quick Access",
			// },
		]
	}, [])

	const isBeDelightfulRouter = location?.pathname?.startsWith("/be-delightful")
	const isMobile = useResponsive().md === false
	return (
		// <DetailProvider>
		<div
			className={cx(
				styles.container,
				isBeDelightfulRouter ? styles.normalContainer : styles.superContainer,
			)}
		>
			{isMobile ? null : (
				<div className={styles.sideNavContainer}>
					<Flex
						vertical
						align="center"
						justify="space-between"
						style={{
							gap: 10,
						}}
					>
						{isBeDelightfulRouter ? null : (
							<img src={DelightfulLogo} alt="" className={styles.logo} />
						)}

						{menuItems.map((item) => {
							return (
								<Tooltip key={item.key} title={item.label} placement="right">
									<div
										className={cx(
											styles.menuItem,
											selectedKey === item.key && styles.active,
										)}
										onClick={() => {
											handleMenuSelect(item)
										}}
									>
										{item.icon}
									</div>
								</Tooltip>
							)
						})}
					</Flex>

					<Flex
						vertical
						align="center"
						justify="space-between"
						style={{
							gap: 10,
						}}
					>
						{isBeDelightfulRouter ? null : (
							<>
								<UserMenus isPreviewMode>
									<DelightfulAvatar src={userInfo?.avatar} size={40}>
										{userInfo?.nickname}
									</DelightfulAvatar>
								</UserMenus>
								<SwitchOrganization showPopover needRefresh />
							</>
						)}
					</Flex>
				</div>
			)}
			<div className={styles.content}>
				<Outlet />
			</div>
		</div>
		// </DetailProvider>
	)
}

export default observer(BeDelightful)

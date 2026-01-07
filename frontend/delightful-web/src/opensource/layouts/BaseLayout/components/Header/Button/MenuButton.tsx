import { IconMessages, IconPlus } from "@tabler/icons-react"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { useTranslation } from "react-i18next"
import { useBoolean, useMemoizedFn } from "ahooks"
import { Popover, Menu } from "antd"
import { useMemo, useState } from "react"
import CreateGroupConversationModal from "./CreateGroupConversationModal"
import Button from "./Button"
import { usePopoverStyles } from "./styles"

/** Menu type */
const enum MenuItemType {
	/** Settings button */
	SettingsAction = "SettingsAction",
	/** Menu button */
	MenuAction = "MenuAction",
}

function MenuButton() {
	const { t } = useTranslation("interface")

	const { styles } = usePopoverStyles()

	const [popoverOpen, setPopoverOpen] = useState(false)

	const [
		CreateGroupConversationOpen,
		{ setTrue: openCreateGroupConversationModal, setFalse: closeCreateGroupConversationModal },
	] = useBoolean(false)

	const onClick = useMemoizedFn((event) => {
		switch (event.key) {
			case MenuItemType.MenuAction:
				openCreateGroupConversationModal()
				break
			default:
				break
		}
		setPopoverOpen(false)
	})

	const items = useMemo(() => {
		return [
			{
				key: MenuItemType.MenuAction,
				icon: <DelightfulIcon component={IconMessages} size={20} />,
				label: t("sider.LaunchAGroupChat", { ns: "interface" }),
			},
		]
	}, [t])

	// Handle Popover state changes
	const onOpenChange = (newOpen: boolean) => {
		setPopoverOpen(newOpen)
	}

	return (
		<>
			<Popover
				overlayClassName={styles.popover}
				content={
					<Menu
						className={styles.menu}
						selectable={false}
						onClick={onClick}
						items={items}
						expandIcon={null}
					/>
				}
				trigger="click"
				placement="bottomRight"
				arrow={false}
				open={popoverOpen}
				onOpenChange={onOpenChange}
			>
				<Button>
					<IconPlus size={18} stroke={1.5} />
				</Button>
			</Popover>
			<CreateGroupConversationModal
				open={CreateGroupConversationOpen}
				close={closeCreateGroupConversationModal}
			/>
		</>
	)
}

export default MenuButton

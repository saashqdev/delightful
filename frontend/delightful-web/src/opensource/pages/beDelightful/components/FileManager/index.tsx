import { useMemo, useState } from "react"
import { cx } from "antd-style"
import MarkedFIle from "@/opensource/pages/beDelightful/assets/svg/marked_file.svg"
import ShareThread from "@/opensource/pages/beDelightful/assets/svg/share_thread.svg"
import ShareWebSite from "@/opensource/pages/beDelightful/assets/svg/share_website.svg"
import MarkedFiles from "../MarkedFiles"
import SharedTopics from "../SharedTopics"
import DeployedWebsites from "../DeployedWebsites"
import { useStyles } from "./styles"

const FileManager: React.FC = () => {
	const { styles } = useStyles()

	const [activeTabKey, setActiveTabKey] = useState<string>("1")

	// Define tab items
	const tabItems = useMemo(() => {
		return [
			{
				key: "1",
				label: "Marked files",
				icon: MarkedFIle,
				children: <MarkedFiles />,
			},
			{
				key: "2",
				label: "Shared topics",
				icon: ShareThread,
				children: <SharedTopics />,
			},
			{
				key: "3",
				label: "Created websites",
				icon: ShareWebSite,
				children: <DeployedWebsites />,
			},
		]
	}, [])

	// Handle tab switch
	const handleTabChange = (key: string) => {
		setActiveTabKey(key)
	}

	// Get active tab content
	const getActiveTabContent = () => {
		const activeTab = tabItems.find((item) => item.key === activeTabKey)
		return activeTab ? activeTab.children : null
	}

	const isActiveFirstTab = activeTabKey === tabItems[0].key

	return (
		<div className={styles.container}>
			<ul className={styles.tabsList}>
				{tabItems.map((item) => (
					<button
						key={item.key}
						className={cx(
							styles.tabItem,
							activeTabKey === item.key && styles.tabItemActive,
						)}
						onClick={() => handleTabChange(item.key)}
						type="button"
					>
						<img src={item.icon} className={styles.tabIcon} alt="" />
						{item.label}
					</button>
				))}
			</ul>
			<div
				className={cx(
					styles.tabContent,
					isActiveFirstTab && styles.tabContentActiveFirstTab,
				)}
			>
				{getActiveTabContent()}
			</div>
		</div>
	)
}

export default FileManager

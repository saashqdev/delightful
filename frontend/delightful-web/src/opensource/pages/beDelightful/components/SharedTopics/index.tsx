import { memo, useCallback, useMemo, useRef, useState } from "react"
import { Input, Dropdown, message, Table, Modal, Empty, Button, Space } from "antd"
import { IconChevronDown } from "@tabler/icons-react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import type { ColumnsType } from "antd/es/table"
import { useSize } from "ahooks"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useStyles } from "../MarkedFiles/styles"
import { useStyles as useCommonStyles } from "../FileManager/styles"
import type { DataItem } from "./useData"
import useData from "./useData"

export default memo(function SharedTopics() {
	const { styles } = useStyles()
	const { styles: commonStyles } = useCommonStyles()
	const containerRef = useRef<HTMLDivElement>(null)
	const containerSize = useSize(containerRef)
	const [selectedRowKeys, setSelectedRowKeys] = useState<React.Key[]>([])
	const [searchKeywords, setSearchKeywords] = useState("")

	const { data, loading, setData } = useData({ searchKeywords })

	const handleCancel = useCallback(() => {
		setSelectedRowKeys([])
		// Modal.confirm({
		// 	title: "Deselect",
		// 	content: `Are you sure you want to remove marking from ${selectedRowKeys.length} selected files?`,
		// 	okText: "OK",
		// 	cancelText: "Cancel",
		// 	onOk: async () => {
		// 		try {
		// 			setSelectedRowKeys([])
		// 			message.success("Deselected successfully")
		// 		} catch (error) {
		// 			message.error("Failed to cancel")
		// 			console.error("Failed to batch remove stars:", error)
		// 		}
		// 	},
		// })
	}, [])

	const handleRemove = useCallback(() => {
		if (selectedRowKeys.length === 0) {
			message.warning("Please select files first")
			return
		}
		Modal.confirm({
			title: "Batch Remove Marking",
			content: `Are you sure you want to remove marking from ${selectedRowKeys.length} selected files?`,
			okText: "OK",
			cancelText: "Cancel",
			onOk: async () => {
				try {
					setData((prev) => prev.filter((file) => !selectedRowKeys.includes(file.id)))
					setSelectedRowKeys([])
					message.success("Batch unmarked successfully")
				} catch (error) {
					message.error("Failed to cancel")
					console.error("Failed to batch remove stars:", error)
				}
			},
		})
	}, [selectedRowKeys, setData])

	const handleRemoveItem = useCallback((id: string) => {}, [])

	const dropdownItems = useMemo(() => {
		return [
			{
				key: "1",
				label: "Deselect",
				onClick: handleCancel,
			},
		]
	}, [handleCancel])

	const columns: ColumnsType<DataItem> = useMemo(() => {
		return [
			{
				title: "Topic Name",
				dataIndex: "name",
			},
			{
				title: "Share Type",
			},
			{
				title: "Workspace",
			},
			{
				title: "Marked Time",
				dataIndex: "uploadTime",
				width: "25%",
			},
			{
				title: "Actions",
				align: "center",
				width: 160,
				render: (_, record) => (
					<Space size={10}>
						<Button type="link" className={styles.operationButton}>
							Set Permissions
						</Button>
						<Button type="link" className={styles.operationButton}>
							Stop Sharing
						</Button>
					</Space>
				),
			},
		]
	}, [styles.operationButton])

	const content = data.length ? (
		<Table
			rowKey="id"
			columns={columns}
			className={styles.table}
			dataSource={data}
			loading={loading}
			rowSelection={{
				selectedRowKeys,
				onChange: (keys) => {
					setSelectedRowKeys(keys)
				},
			}}
			pagination={{
				pageSize: 20,
				showSizeChanger: false,
				className: styles.pagination,
			}}
			scroll={{ x: "100%", y: (containerSize?.height ?? 0) - 112 }}
		/>
	) : (
		<div className={commonStyles.emptyWrapper}>
			<Empty description="No marked files" image={Empty.PRESENTED_IMAGE_SIMPLE} />
		</div>
	)

	return (
		<div className={commonStyles.pageContainer}>
			<div className={commonStyles.title}>Shared Topics</div>
			<div className={commonStyles.description}>Manage publicly shared topics</div>
			<div className={commonStyles.formHeader}>
				<Input
					placeholder="Search topics"
					className={commonStyles.searchInput}
					value={searchKeywords}
					onChange={(event) => {
						setSearchKeywords(event.target.value)
					}}
				/>
				<Dropdown menu={{ items: dropdownItems }} disabled={selectedRowKeys.length === 0}>
					<DelightfulButton className={styles.batchOperationButton} onClick={handleRemove}>
						<span>Batch Actions</span>
						<DelightfulIcon component={IconChevronDown} size={20} stroke={2} />
					</DelightfulButton>
				</Dropdown>
			</div>
			<div ref={containerRef} className={commonStyles.pageContent}>
				{loading || !containerSize ? (
					<div className={commonStyles.loadingWrapper}>
						<DelightfulSpin />
					</div>
				) : (
					content
				)}
			</div>
		</div>
	)
})

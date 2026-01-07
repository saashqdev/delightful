import { memo, useCallback, useMemo, useRef, useState } from "react"
import { Input, Dropdown, message, Table, Modal, Empty } from "antd"
import { IconChevronDown } from "@tabler/icons-react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import type { ColumnsType } from "antd/es/table"
import { useSize } from "ahooks"
import { StarFilled } from "@ant-design/icons"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useStyles } from "./styles"
import { useStyles as useCommonStyles } from "../FileManager/styles"
import type { DataItem } from "./useData"
import useData from "./useData"

export default memo(function MarkedFiles() {
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
		// 	title: "Clear selection",
		// 	content: `Clear marks from ${selectedRowKeys.length} selected files?`,
		// 	okText: "Confirm",
		// 	cancelText: "Cancel",
		// 	onOk: async () => {
		// 		try {
		// 			setSelectedRowKeys([])
		// 			message.success("Selection cleared")
		// 		} catch (error) {
		// 			message.error("Clear failed")
		// 			console.error("Failed to batch remove stars:", error)
		// 		}
		// 	},
		// })
	}, [])

	const handleRemove = useCallback(() => {
		if (selectedRowKeys.length === 0) {
			message.warning("Please select marked files first")
			return
		}
		Modal.confirm({
			title: "Batch remove marks",
			content: `Remove marks from ${selectedRowKeys.length} selected files?`,
			okText: "Confirm",
			cancelText: "Cancel",
			onOk: async () => {
				try {
					setData((prev) => prev.filter((file) => !selectedRowKeys.includes(file.id)))
					setSelectedRowKeys([])
					message.success("Removed marks for selected files")
				} catch (error) {
					message.error("Failed to unmark")
					console.error("Failed to batch remove stars:", error)
				}
			},
		})
	}, [selectedRowKeys, setData])

	const handleRemoveItem = useCallback((id: string) => {}, [])

	const handleTopicClick = useCallback((topicId: string) => {
		//
	}, [])

	const dropdownItems = useMemo(() => {
		return [
			{
				key: "1",
				label: "Clear selection",
				onClick: handleCancel,
			},
		]
	}, [handleCancel])

	const columns: ColumnsType<DataItem> = useMemo(() => {
		return [
			{
				title: "File Name",
				dataIndex: "name",
			},
			{
				title: "Related Topic",
				dataIndex: "topicName",
				width: "15%",
				render: (value, record) => {
					return (
						<DelightfulButton
							className={styles.topicButton}
							type="link"
							onClick={() => {
								handleTopicClick(record.id)
							}}
						>
							{value}
						</DelightfulButton>
					)
				},
			},
			{
				title: "Marked Time",
				dataIndex: "uploadTime",
				width: "25%",
			},
			{
				title: "Actions",
				align: "center",
				width: 80,
				render: (_, record) => (
					<DelightfulButton
						type="text"
						className={styles.starIconButton}
						icon={<StarFilled className={styles.starIcon} />}
						onClick={() => handleRemoveItem(record.id)}
					/>
				),
			},
		]
	}, [
		styles.topicButton,
		styles.starIconButton,
		styles.starIcon,
		handleTopicClick,
		handleRemoveItem,
	])

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
			<Empty description="No marked files yet" image={Empty.PRESENTED_IMAGE_SIMPLE} />
		</div>
	)

	return (
		<div className={commonStyles.pageContainer}>
			<div className={commonStyles.title}>Marked Files</div>
			<div className={commonStyles.description}>
				View and manage all marked files here.
			</div>
			<div className={commonStyles.formHeader}>
				<Input
					placeholder="Search files"
					className={commonStyles.searchInput}
					value={searchKeywords}
					onChange={(event) => {
						setSearchKeywords(event.target.value)
					}}
				/>
				<Dropdown menu={{ items: dropdownItems }} disabled={selectedRowKeys.length === 0}>
					<DelightfulButton className={styles.batchOperationButton} onClick={handleRemove}>
						<span>Batch actions</span>
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

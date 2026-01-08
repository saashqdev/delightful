import { useTranslation } from "react-i18next"
import { useState, useEffect, useRef } from "react"
import { Flex, message, Input, Button, Tag, Dropdown, Table } from "antd"
import { useMemoizedFn, useDebounceFn } from "ahooks"
import { IconChevronDown, IconDots, IconPlus } from "@tabler/icons-react"
import { useUpload } from "@/opensource/hooks/useUploadFiles"
import { genFileData } from "@/opensource/pages/chatNew/components/MessageEditor/components/InputFiles/utils"
import { KnowledgeApi } from "@/apis"
import type { Knowledge } from "@/types/knowledge"
import {
	documentSyncStatusMap,
	getFileIconByType,
	SegmentationMode,
} from "@/opensource/pages/vectorKnowledge/constant"
import { useDocumentOperations } from "../../hooks/useDocumentOperations"
import { useVectorKnowledgeDocumentStyles } from "./styles"
import DocumentUpload from "@/opensource/pages/vectorKnowledge/components/Upload/DocumentUpload"
import {
	hasEditRight,
	hasAdminRight,
} from "@/opensource/pages/flow/components/AuthControlButton/types"
import type { OperationTypes } from "@/opensource/pages/flow/components/AuthControlButton/types"
import IconActionButton from "@/enhance/tabler/icons-react/icons/IconActionButton"
import { useNavigate } from "react-router-dom"
import { RoutePath } from "@/const/routes"

interface Props {
	className: string
	knowledgeBaseCode: string
	userOperation: OperationTypes
}

export default function Document({ className, knowledgeBaseCode, userOperation }: Props) {
	const { styles } = useVectorKnowledgeDocumentStyles()
	const { t } = useTranslation("flow")
	const navigate = useNavigate()

	const rightContainerRef = useRef<HTMLDivElement>(null)
	const headerRef = useRef<HTMLDivElement>(null)

	const [documentList, setDocumentList] = useState<Knowledge.EmbedDocumentDetail[]>([])
	const [searchText, setSearchText] = useState("")
	const [tableHeight, setTableHeight] = useState<number | string>("100%")
	const [selectedRowKeys, setSelectedRowKeys] = useState<string[]>([])
	const [loading, setLoading] = useState(false)

	const [pageInfo, setPageInfo] = useState<{
		page: number
		pageSize: number
		total: number
	}>({
		page: 1,
		pageSize: 10,
		total: 0,
	})

	// Search handler - using debounce
	const handleSearch = useMemoizedFn((value: string) => {
		setSearchText(value)
	})

	const { uploadAndGetFileUrl } = useUpload({
		storageType: "private",
	})

	/** Upload file */
	const handleFileUpload = useMemoizedFn(async (file: File) => {
		try {
			// Upload file
			const newFile = genFileData(file)
			// Already passed beforeFileUpload pre-validation, so pass () => true to skip method validation
			const { fullfilled } = await uploadAndGetFileUrl([newFile], () => true)
			// Update uploaded file list state
			if (fullfilled && fullfilled.length) {
				const { path } = fullfilled[0].value
				// Use type assertion to handle API call
				const res = await (KnowledgeApi as any).addKnowledgeDocument({
					knowledge_code: knowledgeBaseCode,
					enabled: true,
					document_file: {
						name: file.name,
						key: path,
					},
				})
				if (res) {
					message.success(
						t("knowledgeDatabase.uploadDocumentSuccess", { name: file.name }),
					)
					debouncedGetDocumentList(
						knowledgeBaseCode,
						searchText,
						pageInfo.page,
						pageInfo.pageSize,
					)
				}
			}
		} catch (error) {
			console.error("File upload failed:", error)
			message.error(t("knowledgeDatabase.uploadFailed"))
		}
	})

	/**
	 * Get knowledge base document list
	 */
	const getKnowledgeDocumentList = useMemoizedFn(
		async (
			code: string,
			name: string,
			page: number,
			pageSize: number,
			showLoading: boolean = true,
		) => {
			try {
				if (showLoading) {
					setLoading(true)
				}
				// Use type assertion to handle API call
				const res = await (KnowledgeApi as any).getKnowledgeDocumentList({
					code,
					name: name || undefined,
					page,
					pageSize,
				})
				if (res) {
						// Only update existing documents in documentList
						if (documentList.length > 0 && res.page !== pageInfo.page) {
							// Create document code mapping for quick lookup
						const documentListMap = new Map(
							documentList.map((item) => [item.code, item]),
						)

							// Use mapping to update documents
						const updatedDocumentList = [...documentList]
						let hasUpdates = false

						res.list.forEach((newItem: Knowledge.EmbedDocumentDetail) => {
							if (documentListMap.has(newItem.code)) {
									// Find current document's index in array
									const index = updatedDocumentList.findIndex(
										(item) => item.code === newItem.code,
									)
									if (index !== -1) {
										// Update document
									updatedDocumentList[index] = newItem
									hasUpdates = true
								}
							}
						})

						// Only set state when there are updates
						if (hasUpdates) {
							setDocumentList(updatedDocumentList)
						}
					} else {
						// Set data directly on initialization
						setDocumentList(res.list)
					}

					setPageInfo((prev) => ({
						...prev,
						total: res.total,
					}))
				}
			} catch (error) {
				console.error("Failed to get knowledge base document list:", error)
			} finally {
				setLoading(false)
			}
		},
	)

	// Use ahooks' useDebounceFn instead of custom debounce
	const { run: debouncedGetDocumentList } = useDebounceFn(
		(
			code: string,
			name: string,
			page: number,
			pageSize: number,
			showLoading: boolean = true,
		) => {
			return getKnowledgeDocumentList(code, name, page, pageSize, showLoading)
		},
		{ wait: 300, leading: true, trailing: false },
	)

	// Use extracted document operations hook
	const {
		handleEnableSingleFile,
		handleDisableSingleFile,
		handleDeleteSingleFile,
		handleBatchDelete,
		handleBatchEnable,
		handleBatchDisable,
		handleRenameFile,
	} = useDocumentOperations({
		knowledgeBaseCode,
		documentList,
		selectedRowKeys,
		setSelectedRowKeys,
		pageInfo,
		searchText,
		getKnowledgeDocumentList: debouncedGetDocumentList, // Use debounced version of the method
	})

	// Get document status tag
	const getStatusTag = (syncStatus: number, record: Knowledge.EmbedDocumentDetail) => {
		switch (syncStatus) {
			case documentSyncStatusMap.Pending:
				return (
					<Tag className={styles.statusTag} bordered={false} color="default">
						{t("knowledgeDatabase.syncStatus.pending")}
					</Tag>
				)
			case documentSyncStatusMap.Processing:
				return (
					<Tag className={styles.statusTag} bordered={false} color="processing">
						{t("knowledgeDatabase.syncStatus.processing")}
					</Tag>
				)
			case documentSyncStatusMap.Success:
				return (
					<>
						{record.enabled ? (
							<Tag className={styles.statusTag} bordered={false} color="success">
								{t("knowledgeDatabase.syncStatus.available")}
							</Tag>
						) : (
							<Tag className={styles.statusTag} bordered={false} color="warning">
								{t("knowledgeDatabase.syncStatus.disabled")}
							</Tag>
						)}
					</>
				)
			case documentSyncStatusMap.Failed:
				return (
					<Tag className={styles.statusTag} bordered={false} color="error">
						{t("knowledgeDatabase.syncStatus.failed")}
					</Tag>
				)
		}
	}

	/** Navigate to document configuration page */
	const handleFileConfig = useMemoizedFn((record: Knowledge.EmbedDocumentDetail) => {
		navigate(
			`${RoutePath.VectorKnowledgeCreate}?knowledgeBaseCode=${knowledgeBaseCode}&documentCode=${record.code}`,
		)
	})

	/**
	 * Pagination change handler
	 */
	const handlePageChange = useMemoizedFn((page: number, pageSize: number) => {
		setPageInfo((prev) => ({
			...prev,
			page,
			pageSize,
		}))
	})

	// Calculate table height
	useEffect(() => {
		const calculateTableHeight = () => {
			if (rightContainerRef.current && headerRef.current) {
				const containerHeight = rightContainerRef.current.clientHeight
				const headerHeight = headerRef.current.clientHeight
				const tableHeaderHeight = 45 // Table header height, adjust as needed
				const paginationHeight = 64 // Pagination height, adjust as needed
				const padding = 40 // Adjust according to actual padding
				setTableHeight(
					containerHeight - headerHeight - tableHeaderHeight - paginationHeight - padding,
				)
			}
		}

		calculateTableHeight()
		window.addEventListener("resize", calculateTableHeight)

		return () => {
			window.removeEventListener("resize", calculateTableHeight)
		}
	}, [])

	// Get knowledge base document list
	useEffect(() => {
		if (knowledgeBaseCode) {
			debouncedGetDocumentList(
				knowledgeBaseCode,
				searchText,
				pageInfo.page,
				pageInfo.pageSize,
			)
		}
	}, [knowledgeBaseCode, searchText, pageInfo.page, pageInfo.pageSize])

	// Periodically refresh document list
	const timeoutRef = useRef<NodeJS.Timeout | null>(null)

	useEffect(() => {
		if (
			documentList.length &&
			documentList.some((item) =>
				[documentSyncStatusMap.Pending, documentSyncStatusMap.Processing].includes(
					item.sync_status,
				),
			)
		) {
			// Clear previous timeout
			if (timeoutRef.current) {
				clearTimeout(timeoutRef.current)
			}

			// Set new timeout and save reference
			timeoutRef.current = setTimeout(() => {
				// Polling request doesn't show loading
				debouncedGetDocumentList(
					knowledgeBaseCode,
					searchText,
					pageInfo.page,
					pageInfo.pageSize,
				false,
			)
		}, 5000)
	}

	// Clear timeout when component unmounts
	}, [documentList])

	// Table column definitions
	const columns = [
		{
			title: t("knowledgeDatabase.documentTitle"),
			dataIndex: "name",
			key: "name",
			width: 300,
			render: (name: string, record: Knowledge.EmbedDocumentDetail) => (
				<Flex align="center">
					<span className={styles.fileTypeIcon}>
						{getFileIconByType(record.doc_type)}
					</span>
					{name}
				</Flex>
			),
		},
		{
			title: t("knowledgeDatabase.segmentMode"),
			dataIndex: "fragment_config",
			key: "fragment_config",
			width: 100,
			render: (value: number, record: Knowledge.EmbedDocumentDetail) => {
				const mode = record.fragment_config?.mode
				switch (mode) {
					case SegmentationMode.General:
						return (
							<div className={styles.segmentMode}>
								{t("knowledgeDatabase.segmentationMode.general")}
							</div>
						)
					case SegmentationMode.ParentChild:
						return (
							<div className={styles.segmentMode}>
								{t("knowledgeDatabase.segmentationMode.parentChild")}
							</div>
						)
					default:
						return (
							<div className={styles.segmentMode}>
								{t("knowledgeDatabase.segmentationMode.unknown")}
							</div>
						)
				}
			},
		},
		{
			title: t("knowledgeDatabase.wordCount"),
			dataIndex: "word_count",
			key: "word_count",
			width: 100,
		},
		{
			title: t("knowledgeDatabase.createTime"),
			dataIndex: "created_at",
			key: "created_at",
			width: 200,
		},
		{
			title: t("knowledgeDatabase.status"),
			dataIndex: "sync_status",
			key: "sync_status",
			width: 120,
			render: getStatusTag,
		},
		...(hasEditRight(userOperation)
			? [
					{
						title: t("knowledgeDatabase.operation"),
						key: "operation",
						width: 100,
						render: (_: any, record: Knowledge.EmbedDocumentDetail) => (
							<div className={styles.operation}>
								<div
									className={styles.actionButton}
									onClick={() => handleFileConfig(record)}
								>
									<IconActionButton size={24} />
								</div>
								<Dropdown
									placement="bottomRight"
									menu={{
										items: [
											{
												label: <div>{t("knowledgeDatabase.rename")}</div>,
												key: "rename",
												onClick: () => handleRenameFile(record),
											},
											{
												label: <div>{t("common.enable")}</div>,
												key: "enable",
												onClick: () => handleEnableSingleFile(record),
											},
											{
												label: <div>{t("common.disabled")}</div>,
												key: "disabled",
												onClick: () => handleDisableSingleFile(record),
											},
											...(hasAdminRight(userOperation)
												? [
														{
															label: (
																<div className={styles.deleteText}>
																	{t("knowledgeDatabase.delete")}
																</div>
															),
															key: "delete",
															onClick: () =>
																handleDeleteSingleFile(record),
														},
												  ]
												: []),
										],
									}}
								>
									<Flex
										align="center"
										justify="center"
										className={styles.operationButton}
									>
										<IconDots size={20} />
									</Flex>
								</Dropdown>
							</div>
						),
					},
			  ]
			: []),
	]

	return (
		<Flex vertical className={className} ref={rightContainerRef}>
			<div ref={headerRef}>
				<div className={styles.title}>{t("knowledgeDatabase.documentTitle")}</div>
				<div className={styles.subTitle}>{t("knowledgeDatabase.documentDesc")}</div>

				<Flex align="center" justify="space-between">
					<Input
						className={styles.searchBar}
						placeholder={t("knowledgeDatabase.search")}
						onChange={(e) => handleSearch(e.target.value)}
						allowClear
					/>
					{hasEditRight(userOperation) && (
						<Flex align="stretch" gap={10}>
							<Dropdown
								menu={{
									items: [
										{
											label: <div>{t("common.enable")}</div>,
											key: "enable",
											onClick: () => handleBatchEnable(),
										},
										{
											label: <div>{t("common.disabled")}</div>,
											key: "disable",
											onClick: () => handleBatchDisable(),
										},
										...(hasAdminRight(userOperation)
											? [
													{
														label: (
															<div className={styles.deleteText}>
																{t("knowledgeDatabase.delete")}
															</div>
														),
														key: "delete",
														onClick: () => handleBatchDelete(),
													},
											  ]
											: []),
									],
								}}
							>
								<Flex align="center" gap={4} className={styles.batchOperation}>
									<div>{t("knowledgeDatabase.batchOperation")}</div>
									<IconChevronDown size={16} />
								</Flex>
							</Dropdown>
							<DocumentUpload handleFileUpload={handleFileUpload} dragger={false}>
								<Button type="primary" icon={<IconPlus size={16} />}>
									{t("knowledgeDatabase.addDocument")}
								</Button>
							</DocumentUpload>
						</Flex>
					)}
				</Flex>
			</div>

			<div className={styles.tableContainer}>
				<Table
					rowKey="code"
					loading={loading}
					rowSelection={
						hasEditRight(userOperation)
							? {
									selectedRowKeys,
									onChange: (codes) => setSelectedRowKeys(codes as string[]),
							  }
							: undefined
					}
					columns={columns}
					dataSource={documentList}
					scroll={{ scrollToFirstRowOnChange: true, y: tableHeight }}
					pagination={{
						position: ["bottomLeft"],
						total: pageInfo.total,
						pageSize: pageInfo.pageSize,
						showSizeChanger: true,
						showQuickJumper: false,
						pageSizeOptions: ["10", "20", "50"],
						onChange: handlePageChange,
					}}
				/>
			</div>
		</Flex>
	)
}

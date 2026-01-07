import DelightfulFileIcon from "@/opensource/components/base/DelightfulFileIcon"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import FoldIcon from "@/opensource/pages/beDelightful/assets/svg/file-folder.svg"
import topicEmpty from "@/opensource/pages/beDelightful/assets/svg/topic-empty.svg"
import { getTemporaryDownloadUrl } from "@/opensource/pages/beDelightful/utils/api"
import { getFileType } from "@/opensource/pages/beDelightful/utils/handleFIle"
import { IconChevronDown, IconChevronRight, IconDownload } from "@tabler/icons-react"
import { useResponsive } from "ahooks"
import { Button, Input, Tooltip, Typography } from "antd"
import { useMemo, useState } from "react"
import useStyles from "./style"

const { Text } = Typography

// Define file and folder types
interface FileItem {
	file_id: string
	file_name: string
	filename?: string
	file_extension: string
	is_directory?: boolean
	display_filename?: string
	[key: string]: any
}

interface FolderItem {
	name: string
	type: string
	is_directory: true
	children: (FileItem | FolderItem)[]
	path: string
	[key: string]: any
}

type AttachmentItem = FileItem | FolderItem

export default function AttachmentList({
	attachments,
	setUserSelectDetail,
}: {
	attachments: any[]
	setUserSelectDetail?: (detail: any) => void
}) {
	const { styles, cx } = useStyles()
	const [isFileListCollapsed, setIsFileListCollapsed] = useState(false)
	const [fileSearchText, setFileSearchText] = useState("")
	const [collapsedFolders, setCollapsedFolders] = useState<Record<string, boolean>>({})
	const responsive = useResponsive()
	const isMobile = responsive.md === false

	// Toggle folder expand/collapse state
	const toggleFolder = (folderId: string, e: React.MouseEvent) => {
		e.stopPropagation()
		setCollapsedFolders((prev) => ({
			...prev,
			[folderId]: !prev[folderId],
		}))
	}

	// Filter file list
	const filteredFiles = useMemo(() => {
		if (!attachments) return []
		if (!fileSearchText.trim()) return attachments

		const filterItems = (items: AttachmentItem[]): AttachmentItem[] => {
			return items.filter((item) => {
				// Check if it is a folder
				if (item.is_directory && "children" in item) {
					// Search by folder name
					const folderMatch = (item.name || "")
						.toLowerCase()
						.includes(fileSearchText.toLowerCase())

					// Recursively search subfiles/folders
					const filteredChildren = filterItems(item.children)

					// Keep folder if its name matches or children match
					return folderMatch || filteredChildren.length > 0
				} else {
					// Search by file name
					return (item.filename || item.file_name || "")
						.toLowerCase()
						.includes(fileSearchText.toLowerCase())
				}
			})
		}

		return filterItems(attachments)
	}, [attachments, fileSearchText])

	const handleDownloadFile = (file_id: string, e: React.MouseEvent<HTMLSpanElement>) => {
		e.stopPropagation()
		getTemporaryDownloadUrl({ file_ids: [file_id] }).then((res: any) => {
			window.open(res[0]?.url, "_blank")
		})
	}

	const handleOpenFile = (item: AttachmentItem) => {
		// If it's a folder, do nothing
		if (item.is_directory) return

		// getTemporaryDownloadUrl({ file_ids: [item.file_id] }).then((res: any) => {
		const fileName = item.display_filename || item.file_name || item.filename
		const type = getFileType(item.file_extension)
		if (type) {
			setUserSelectDetail?.({
				type, // Determine type based on file extension
				data: {
					// content: data,
					file_name: fileName,
					// file_url: res[0]?.url,
					file_extension: item.file_extension,
					file_id: item.file_id,
				},
				currentFileId: item.file_id,
				attachments,
			})
			// })
		} else {
			setUserSelectDetail?.({
				type: "empty",
				data: {
					text: "Preview not supported for this file, please download it",
				},
			})
		}
		// })
	}

	// Render text based on whether it is mobile
	const renderText = (text: string, tooltipTitle: string) => {
		if (isMobile) {
			return <Text className={styles.ellipsis}>{text}</Text>
		}

		return (
			<Tooltip title={tooltipTitle} placement="right">
				<Text className={styles.ellipsis}>{text}</Text>
			</Tooltip>
		)
	}

	// Recursively render files and folders
	const renderItems = (items: AttachmentItem[], level = 0) => {
		// Check if at least one folder exists
		const hasFolders = items.some((item) => item.is_directory && "children" in item)

		return items.map((item: AttachmentItem) => {
			// Determine whether it is a folder
			if (item.is_directory && "children" in item) {
				const isFolderCollapsed = collapsedFolders[`/${item.path}`]

				// Calculate indent width for nested levels
				const indentWidth = level * 24

				return (
					<div key={item.name} className={styles.folderContainer}>
						<div
							className={styles.fileItem}
							onClick={(e) => toggleFolder(`/${item.path}`, e)}
						>
							{/* Use fixed structured layout */}
							<div
								style={{
									display: "flex",
									alignItems: "center",
									flex: 1,
									overflow: "hidden",
									paddingLeft: indentWidth + "px",
								}}
							>
								<div className={styles.iconWrapper}>
									<Button
										type="text"
										size="small"
										icon={
											<DelightfulIcon
												size={18}
												component={
													isFolderCollapsed
														? IconChevronDown
														: IconChevronRight
												}
												stroke={2}
											/>
										}
										onClick={(e) => toggleFolder(`/${item.path}`, e)}
										className={styles.iconButton}
									/>
								</div>
								<div className={styles.iconWrapper} style={{ marginLeft: "2px" }}>
									<img src={FoldIcon} alt="folder" width={18} height={18} />
								</div>
								<div className={styles.fileNameContainer}>
									{renderText(item.name, item.name)}
								</div>
							</div>
						</div>
						{isFolderCollapsed && (
							<div className={styles.folderContent}>
								{renderItems(item.children, level + 1)}
							</div>
						)}
					</div>
				)
			} else {
				// Render files
				// Calculate indent width for nested levels
				const indentWidth = level * 24

				return (
					<div
						key={item.file_id}
						className={styles.fileItem}
						onClick={(e) => {
							e.stopPropagation()
							handleOpenFile(item)
						}}
					>
						{/* Use fixed structured layout */}
						<div
							style={{
								display: "flex",
								alignItems: "center",
								flex: 1,
								overflow: "hidden",
								paddingLeft: indentWidth + "px",
							}}
						>
							{/* Show spacer button only when folders exist or when not at the top level */}
							{(hasFolders || level > 0) && (
								<div className={styles.iconWrapper}>
									<Button
										type="text"
										size="small"
										className={styles.iconButton}
										style={{
											visibility: "hidden",
											width: "18px",
											height: "18px",
										}}
									/>
								</div>
							)}
							<div
								className={styles.iconWrapper}
								style={{ marginLeft: hasFolders || level > 0 ? "2px" : "0" }}
							>
								<DelightfulFileIcon
									type={item.file_extension}
									size={18}
									className={styles.threadTitleImage}
								/>
							</div>
							<div className={styles.fileNameContainer}>
								{renderText(item.file_name, item.file_name)}
							</div>
						</div>
						<DelightfulIcon
							className={styles.attachmentAction}
							onClick={(e: any) => handleDownloadFile(item.file_id, e)}
							component={IconDownload}
							stroke={2}
							size={18}
						/>
					</div>
				)
			}
		})
	}

	return (
		<div className={cx(styles.section, isFileListCollapsed && styles.collapsed)}>
			<div className={styles.header}>
				<div className={styles.titleContainer}>
					<div className={styles.iconWrapper}>
						<Button
							type="text"
							size="small"
							icon={
								<DelightfulIcon
									size={18}
									component={
										isFileListCollapsed ? IconChevronRight : IconChevronDown
									}
									stroke={2}
								/>
							}
							onClick={() => {
								setIsFileListCollapsed(!isFileListCollapsed)
							}}
							className={styles.iconButton}
						/>
					</div>
					<span>Topic files</span>
				</div>
			</div>
			{!isFileListCollapsed && (
				<div className={styles.content}>
					<div className={styles.searchContainer}>
						<Input
							placeholder="Search files"
							value={fileSearchText}
							onChange={(e) => setFileSearchText(e.target.value)}
							className={styles.searchInput}
						/>
					</div>
					{!!filteredFiles.length && (
						<div className={styles.listContainer}>{renderItems(filteredFiles, 0)}</div>
					)}
					{!!attachments?.length && !filteredFiles.length && fileSearchText && (
						<div className={styles.emptyText}>
							<img src={topicEmpty} alt="" className={styles.emptyTextIcon} />
							No related files found
						</div>
					)}
					{attachments?.length === 0 && (
						<div className={styles.emptyText}>
							<img src={topicEmpty} alt="" className={styles.emptyTextIcon} />
							No related files
						</div>
					)}
				</div>
			)}
		</div>
	)
}

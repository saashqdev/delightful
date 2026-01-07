import DelightfulFileIcon from "@/opensource/components/base/DelightfulFileIcon"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { getTemporaryDownloadUrl } from "@/opensource/pages/beDelightful/utils/api"
import { memo, useEffect, useMemo, useState } from "react"
import MarkDown from "react-markdown"
import remarkGfm from "remark-gfm"
import CommonHeader from "../../components/CommonHeader"
import { useStyles } from "./styles"
import CommonFooter from "../../components/CommonFooter"
import { useFileData } from "@/opensource/pages/beDelightful/hooks/useFileData"

interface AttachmentFile {
	file_id: string
	file_name: string
	is_directory?: boolean
	setUserSelectDetail?: (detail: any) => void
	children?: AttachmentFile[]
	[key: string]: any
}

export default memo(function TextEditor(props: any) {
	const {
		data: displayData,
		attachments,
		setUserSelectDetail,
		type,
		currentIndex,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		totalFiles,
		hasUserSelectDetail,
		isFromNode,
		onClose,
		userSelectDetail,
		isFullscreen,
	} = props
	const { styles } = useStyles()
	const { fileData } = useFileData({ file_id: displayData?.file_id })

	const data = useMemo(() => {
		return {
			...displayData,
			content: fileData ? fileData : displayData?.content,
		}
	}, [displayData, fileData])

	const [processedContent, setProcessedContent] = useState<string>(data?.content || "")
	const [isLoading, setIsLoading] = useState<boolean>(!data?.content)

	useEffect(() => {
		const processMarkdownImages = async () => {
			if (!data?.content) {
				setIsLoading(true)
				return
			}

			setIsLoading(true)
			// Match images in markdown content
			const imageRegex = /!\[.*?\]\(([^)]+)\)/g
			const matches = [...data.content.matchAll(imageRegex)]

			if (matches.length === 0) {
				setProcessedContent(data.content)
				setIsLoading(false)
				return
			}

			// Collect all image paths that need processing
			const imagesToProcess: string[] = []
			const fileIdMap = new Map<string, string>()

			for (const match of matches) {
				const imgUrl = match[1]
				// Skip remote URLs
				if (imgUrl.startsWith("http://") || imgUrl.startsWith("https://")) {
					continue
				}

				// Extract file name from path
				const pathParts = imgUrl.split("/")
				const fileName = pathParts[pathParts.length - 1]

				// Find matching file in attachments
				const findFile = (items: AttachmentFile[]): AttachmentFile | null => {
					if (!Array.isArray(items) || items.length === 0) {
						return null
					}

					for (const item of items) {
						if (item.is_directory && item.children) {
							const found = findFile(item.children)
							if (found) return found
						} else if (item.file_name === fileName) {
							return item
						}
					}
					return null
				}

				const matchedFile = findFile(attachments)
				if (matchedFile) {
					imagesToProcess.push(matchedFile.file_id)
					fileIdMap.set(matchedFile.file_id, imgUrl)
				}
			}

			if (imagesToProcess.length > 0) {
				try {
					// Fetch temporary download links in batch
					const downloadUrls =
						(await getTemporaryDownloadUrl({
							file_ids: imagesToProcess,
						})) || []

					// Replace image URLs in the content
					let newContent = data.content
					for (const urlInfo of downloadUrls) {
						const originalUrl = fileIdMap.get(urlInfo.file_id)
						if (originalUrl) {
							// Use regex to replace specific image URL
							const imgRegex = new RegExp(
								`!\\[(.*?)\\]\\(${originalUrl.replace(
									/[.*+?^${}()|[\]\\]/g,
									"\\$&",
								)}\\)`,
								"g",
							)
							newContent = newContent.replace(imgRegex, `![$1](${urlInfo.url})`)
						}
					}

					setProcessedContent(newContent)
					setIsLoading(false)
				} catch (error) {
					console.error("Error fetching download URLs:", error)
					setProcessedContent(data.content)
					setIsLoading(false)
				}
			} else {
				setProcessedContent(data.content)
				setIsLoading(false)
			}
		}

		processMarkdownImages()
	}, [data?.content, attachments])

	return (
		<div className={styles.textEditorContainer}>
			<CommonHeader
				icon={<DelightfulFileIcon size={20} type="md" />}
				title={data?.file_name || data?.title}
				setUserSelectDetail={setUserSelectDetail}
				type={type}
				currentAttachmentIndex={currentIndex}
				totalFiles={totalFiles}
				onPrevious={onPrevious}
				onNext={onNext}
				onFullscreen={onFullscreen}
				onDownload={onDownload}
				hasUserSelectDetail={hasUserSelectDetail}
				isFromNode={isFromNode}
				onClose={onClose}
				isFullscreen={isFullscreen}
			/>
			<div className={styles.editorBody}>
				{isLoading ? (
					<div className={styles.loadingContainer}>
						<DelightfulSpin spinning />
					</div>
				) : (
					<MarkDown
						remarkPlugins={[remarkGfm]}
						components={{
							a: ({ node, children, ...linkProps }) => {
								// Check if link is an anchor (# prefix)
								if (linkProps.href && linkProps.href.startsWith("#")) {
									// Render as plain text for anchor links
									return <span>{children}</span>
								}
								// Render non-anchor links normally
								return (
									<a {...linkProps} target="_blank" rel="noopener noreferrer">
										{children}
									</a>
								)
							},
							li: ({ node, className, ...liProps }: any) => {
								// Check if this is a task list item
								if ("checked" in liProps) {
									const { checked } = liProps
									// Remove checked from props to avoid passing to li element
									delete liProps.checked

									return (
										<li
											className={`task-list-item ${className || ""}`}
											{...liProps}
										>
											<input
												type="checkbox"
												checked={checked || false}
												readOnly
											/>
											{liProps.children}
										</li>
									)
								}
								return <li className={className} {...liProps} />
							},
						}}
						className={styles.githubMarkdown}
					>
						{processedContent}
					</MarkDown>
				)}
			</div>
			{isFromNode && (
				<CommonFooter
					setUserSelectDetail={setUserSelectDetail}
					userSelectDetail={userSelectDetail}
					onClose={onClose}
				/>
			)}
		</div>
	)
})

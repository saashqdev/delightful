import { getTemporaryDownloadUrl } from "@/opensource/pages/superMagic/utils/api"
import { memo, useEffect, useMemo, useState } from "react"
import BrowserNavigate from "../../components/BrowserNavigate"
import CommonFooter from "../../components/CommonFooter"
import IsolatedHTMLRenderer from "./IsolatedHTMLRenderer"
import { useStyles } from "./styles"
import ActionButtons from "../../components/CommonHeader/ActionButtons"
import { useFileData } from "@/opensource/pages/superMagic/hooks/useFileData"
import { Flex } from "antd"

interface HTMLProps {
	data: string | any
	attachments?: any[]
	type?: string
	currentIndex?: number
	onPrevious?: () => void
	onNext?: () => void
	onFullscreen?: () => void
	onDownload?: () => void
	totalFiles?: number
	userSelectDetail?: any
	hasUserSelectDetail?: boolean
	setUserSelectDetail?: (detail: any) => void
	isFromNode?: boolean
	onClose?: () => void
	isFullscreen?: boolean
}

export default memo(function HTML(props: HTMLProps) {
	const {
		data: displayData,
		attachments,
		type,
		currentIndex,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		totalFiles,
		userSelectDetail,
		setUserSelectDetail,
		isFromNode,
		onClose,
		hasUserSelectDetail,
		isFullscreen,
	} = props
	const [processedContent, setProcessedContent] = useState<string>("")
	const { styles } = useStyles()
	const { fileData } = useFileData({ file_id: displayData?.file_id })

	const data = useMemo(() => {
		return {
			...displayData,
			content: fileData ? fileData : displayData?.content,
		}
	}, [displayData, fileData])

	useEffect(() => {
		if (!data?.content) return
		const processHtmlContent = async () => {
			if (!data?.content || !attachments || attachments.length === 0) {
				setProcessedContent(data?.content || "")
				return
			}
			// Create a temporary DOM element to parse the HTML
			const parser = new DOMParser()
			const htmlDoc = parser.parseFromString(data.content, "text/html")

			// 移除tailwindcss的link标签和echarts的script标签
			const linkElements = htmlDoc.getElementsByTagName("link")
			const scriptElements = htmlDoc.getElementsByTagName("script")

			// 从后向前遍历以避免因移除元素导致的索引问题
			for (let i = linkElements.length - 1; i >= 0; i--) {
				const link = linkElements[i]
				const href = link.getAttribute("href")
				if (href && (href.includes("tailwindcss") || href.includes("tailwind.css"))) {
					link.parentNode?.removeChild(link)
				}
			}

			for (let i = scriptElements.length - 1; i >= 0; i--) {
				const script = scriptElements[i]
				const src = script.getAttribute("src")
				if ((src && src.includes("echarts")) || src?.includes("tailwindcss")) {
					script.parentNode?.removeChild(script)
				}
			}

			// 将修改后的HTML文档转换回字符串
			const serializer = new XMLSerializer()
			const modifiedHtmlContent = serializer.serializeToString(htmlDoc)

			// 创建新的解析器和文档对象，使用修改后的内容继续处理
			const newParser = new DOMParser()
			const newHtmlDoc = newParser.parseFromString(modifiedHtmlContent, "text/html")
			const imgElements = newHtmlDoc.getElementsByTagName("img")
			const remainingLinkElements = newHtmlDoc.getElementsByTagName("link")
			const remainingScriptElements = newHtmlDoc.getElementsByTagName("script")
			// Create a flat array of all files from attachments (including those in subdirectories)
			const flattenAttachments = (items: any[]): any[] => {
				return items.reduce((acc: any[], item) => {
					if (item.is_directory && item.children) {
						return [...acc, ...flattenAttachments(item.children)]
					}
					return [...acc, item]
				}, [])
			}

			const allFiles = flattenAttachments(attachments)

			// Collect all relative image paths that need to be replaced
			const urlsToReplace = []
			const fileIdsToFetch = []
			const urlMap = new Map()

			// 处理图片标签
			for (let i = 0; i < imgElements.length; i++) {
				const img = imgElements[i]
				const src = img.getAttribute("src")
				// Check if the src is a relative path (not starting with http://, https://, data:, etc.)
				if (src && !src.match(/^(https?:\/\/|data:|blob:|\/\/)/i)) {
					// Extract the file name from the path
					const fileName = src.split("/").pop()
					// Find the matching file in attachments
					const matchedFile = allFiles.find(
						(file) =>
							file.file_name === fileName ||
							file.file_name === src.replace(/^\.\//, "").replace(/^\//, ""),
					)
					if (matchedFile) {
						urlsToReplace.push(src)
						fileIdsToFetch.push(matchedFile.file_id)
						urlMap.set(matchedFile.file_id, { path: src, attr: "src", tag: "img" })
					}
				}
			}

			// 处理CSS链接标签
			for (let i = 0; i < remainingLinkElements.length; i++) {
				const link = remainingLinkElements[i]
				const href = link.getAttribute("href")
				const rel = link.getAttribute("rel")

				if (
					href &&
					rel === "stylesheet" &&
					!href.match(/^(https?:\/\/|data:|blob:|\/\/)/i)
				) {
					const fileName = href.split("/").pop()
					const matchedFile = allFiles.find(
						(file) =>
							file.file_name === fileName ||
							file.file_name === href.replace(/^\.\//, "").replace(/^\//, ""),
					)
					if (matchedFile) {
						urlsToReplace.push(href)
						fileIdsToFetch.push(matchedFile.file_id)
						urlMap.set(matchedFile.file_id, { path: href, attr: "href", tag: "link" })
					}
				}
			}

			// 处理JavaScript脚本标签
			for (let i = 0; i < remainingScriptElements.length; i++) {
				const script = remainingScriptElements[i]
				const src = script.getAttribute("src")

				if (src && !src.match(/^(https?:\/\/|data:|blob:|\/\/)/i)) {
					const fileName = src.split("/").pop()
					const matchedFile = allFiles.find(
						(file) =>
							file.file_name === fileName ||
							file.file_name === src.replace(/^\.\//, "").replace(/^\//, ""),
					)
					if (matchedFile) {
						urlsToReplace.push(src)
						fileIdsToFetch.push(matchedFile.file_id)
						urlMap.set(matchedFile.file_id, { path: src, attr: "src", tag: "script" })
					}
				}
			}

			// If there are resources to replace, fetch their temporary URLs
			if (fileIdsToFetch.length > 0) {
				try {
					const response = await getTemporaryDownloadUrl({ file_ids: fileIdsToFetch })
					const urlData = response || []

					// Replace URLs in the HTML content
					let updatedContent = modifiedHtmlContent
					urlData.forEach((item: any) => {
						const resourceInfo = urlMap.get(item.file_id)
						if (resourceInfo && item.url) {
							// Create a regex that escapes special characters in the path
							const escapedPath = resourceInfo.path.replace(
								/[.*+?^${}()|[\]\\]/g,
								"\\$&",
							)
							const regex = new RegExp(
								`${resourceInfo.attr}=["']${escapedPath}["']`,
								"g",
							)
							updatedContent = updatedContent.replace(
								regex,
								`${resourceInfo.attr}="${item.url}"`,
							)
						}
					})

					setProcessedContent(updatedContent)
				} catch (error) {
					console.error("Error fetching resource URLs:", error)
					setProcessedContent(modifiedHtmlContent)
				}
			} else {
				setProcessedContent(modifiedHtmlContent)
			}
		}
		processHtmlContent()
	}, [data, attachments])

	return (
		<div className={styles.htmlContainer}>
			<Flex className={styles.header} gap={4} justify="space-between" align="center">
				<BrowserNavigate url={`/${data.file_name}`} className={styles.navigate} />
				<ActionButtons
					type={type}
					currentAttachmentIndex={currentIndex}
					totalFiles={totalFiles}
					onPrevious={onPrevious}
					onNext={onNext}
					onFullscreen={onFullscreen}
					onDownload={onDownload}
					onClose={onClose}
					setUserSelectDetail={setUserSelectDetail}
					hasUserSelectDetail={hasUserSelectDetail}
					isFromNode={isFromNode}
					isFullscreen={isFullscreen}
				/>
			</Flex>
			<div className={styles.htmlBody}>
				<IsolatedHTMLRenderer content={processedContent} sandboxType="iframe" />
			</div>
			{isFromNode && (
				<CommonFooter
					userSelectDetail={userSelectDetail}
					setUserSelectDetail={setUserSelectDetail}
				/>
			)}
		</div>
	)
})

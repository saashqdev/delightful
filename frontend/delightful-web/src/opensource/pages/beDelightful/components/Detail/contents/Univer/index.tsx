import { useEffect, useRef, useState } from "react"
import CommonHeader from "@/opensource/pages/beDelightful/components/Detail/components/CommonHeader"
// import ExcelIcon from "@/opensource/pages/beDelightful/assets/file_icon/excel.svg"
// import PowerPointIcon from "@/opensource/pages/beDelightful/assets/file_icon/powerpoint.svg"
import { Skeleton } from "antd"
import { UniverComponent } from "@/opensource/components/UniverComponent"
import DelightfulFileIcon from "@/opensource/components/base/DelightfulFileIcon"
import CommonFooter from "../../components/CommonFooter"
import { useFileUrl } from "@/opensource/pages/beDelightful/hooks/useFileUrl"

export default function UniverViewer(props: any) {
	const {
		data,
		type,
		currentIndex,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		totalFiles,
		hasUserSelectDetail,
		isFromNode,
		file_extension = "xlsx",
		userSelectDetail,
		setUserSelectDetail,
		onClose,
		isFullscreen,
	} = props

	const { file_name, file_id } = data
	const { fileUrl: file_url } = useFileUrl({ file_id })
	const [loading, setLoading] = useState(true)
	const [fileContent, setFileContent] = useState<any>(null)
	const containerRef = useRef<HTMLDivElement>(null)

	// Choose icon based on file extension
	const getFileIcon = () => {
		const extension = (file_extension || "").toLowerCase()
		return <DelightfulFileIcon type={extension} size={18} />
	}

	// Determine file type
	const getFileType = () => {
		const extension = (file_extension || "").toLowerCase()
		if (["xlsx", "xls", "csv"].includes(extension)) {
			return "sheet"
		} else if (["pptx", "ppt"].includes(extension)) {
			return "slide"
		}
		return "sheet" // Default to sheet
	}

	// Load file from URL
	const fetchFileFromUrl = async (url: string) => {
		try {
			setLoading(true)

			// Fetch file over network
			const response = await fetch(url)
			if (!response.ok) {
				throw new Error(`Failed to fetch file: ${response.status} ${response.statusText}`)
			}

			// Handle according to file type
			const fileType = getFileType()

			if (fileType === "sheet") {
				// For Excel, get raw blob
				const blob = await response.blob()
				// Create File object
				const file = new File([blob], file_name, {
					type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
				})
				setFileContent(file)
			} else {
				// For other types, read as text
				const text = await response.text()
				setFileContent(text)
			}
		} catch (error) {
			console.error("Failed to fetch file:", error)
			setFileContent("Unable to load file content")
		} finally {
			setLoading(false)
		}
	}

	// Fetch file when component loads or URL changes
	useEffect(() => {
		if (file_url) {
			fetchFileFromUrl(file_url)
		} else {
			setLoading(false)
		}
	}, [file_url])

	return (
		<div style={{ height: "100%", display: "flex", flexDirection: "column" }}>
			<CommonHeader
				title={file_name}
				icon={getFileIcon()}
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
				setUserSelectDetail={setUserSelectDetail}
			/>
			<div
				ref={containerRef}
				style={{ flex: 1, overflow: "hidden", padding: "16px", minHeight: "500px" }}
			>
				{loading ? (
					<Skeleton active paragraph={{ rows: 10 }} />
				) : (
					<UniverComponent
						data={fileContent}
						fileType={getFileType()}
						fileName={file_name}
						mode="readonly"
					/>
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
}

import { useState, useEffect, useMemo } from "react"
import {
	downloadFileContent,
	getTemporaryDownloadUrl,
} from "@/opensource/pages/beDelightful/utils/api"
import { getFileType } from "@/opensource/pages/beDelightful/utils/handleFIle"

// Image file extensions to skip from navigation
const IMAGE_EXTENSIONS = [
	"jpg",
	"jpeg",
	"png",
	"gif",
	"bmp",
	"svg",
	"webp",
	"ico",
	"tiff",
	"tif",
	"sh",
	// "xlsx",
]

export function useDetailActions({
	disPlayDetail,
	setUserSelectDetail,
	attachments,
}: {
	disPlayDetail: any
	setUserSelectDetail?: (detail: any) => void
	attachments?: any[]
}) {
	const [isFullscreen, setIsFullscreen] = useState(false)
	// Whether selection originates from a node click
	const isFromNode = disPlayDetail?.isFromNode || false
	const [currentIndex, setCurrentIndex] = useState<number>(-1)

	// Decide if we should use the message attachment list (instead of the whole topic list)
	const shouldUseMessageAttachments = useMemo(() => {
		return !!disPlayDetail?.attachments && Array.isArray(disPlayDetail.attachments)
	}, [disPlayDetail?.attachments])

	// Pick the effective attachment list
	const effectiveAttachments = useMemo(() => {
		return shouldUseMessageAttachments ? disPlayDetail.attachments : attachments
	}, [attachments, disPlayDetail?.attachments, shouldUseMessageAttachments])

	// Collect all non-directory files, flatten, and filter out image files
	const collectFiles = (items: any[]): any[] => {
		let files: any[] = []
		if (!items || !Array.isArray(items)) return files

		items.forEach((item) => {
			if (item.is_directory && Array.isArray(item.children)) {
				files = [...files, ...collectFiles(item.children)]
			} else if (!item.is_directory) {
				// Get file extension in lowercase
				const extension = (item.file_extension || "").toLowerCase()

				// Add to list if not an image
				if (!IMAGE_EXTENSIONS.includes(extension)) {
					files.push(item)
				}
			}
		})
		return files
	}

	// Flattened list of all files
	const allFiles = useMemo(() => collectFiles(effectiveAttachments || []), [effectiveAttachments])

	// When attachments and currentFileId exist, locate the current file index
	useEffect(() => {
		if (allFiles.length > 0 && disPlayDetail?.currentFileId) {
			const fileIndex = allFiles.findIndex(
				(file) => file.file_id === disPlayDetail?.currentFileId,
			)
			if (fileIndex !== -1) {
				setCurrentIndex(fileIndex)
			}
		}
	}, [allFiles, disPlayDetail, setCurrentIndex])

	// Handle open file
	const handleOpenFile = (item: any) => {
		if (!item || !item.file_id) return

		getTemporaryDownloadUrl({ file_ids: [item.file_id] }).then((res: any) => {
			downloadFileContent(res[0]?.url).then((data: any) => {
				const fileName = item.display_filename || item.file_name || item.filename
				const type = getFileType(item.file_extension)
				if (type) {
					setUserSelectDetail?.({
						type,
						data: {
							content: data,
							file_name: fileName,
							file_url: res[0]?.url,
							file_extension: item.file_extension,
						},
						currentFileId: item.file_id,
						// Preserve the same attachment list as current context
						attachments:
							disPlayDetail?.attachments && Array.isArray(disPlayDetail.attachments)
								? disPlayDetail.attachments
								: attachments,
					})
				} else {
					setUserSelectDetail?.({
						type: "empty",
						data: {
							text: "Preview not supported yet, please download this file",
						},
					})
				}
			})
		})
	}

	// Go to previous file
	const handlePrevious = () => {
		if (allFiles.length > 0 && currentIndex > 0) {
			handleOpenFile(allFiles[currentIndex - 1])
		}
	}

	// Go to next file
	const handleNext = () => {
		if (allFiles.length > 0 && currentIndex < allFiles.length - 1) {
			handleOpenFile(allFiles[currentIndex + 1])
		}
	}

	// Toggle fullscreen
	const handleFullscreen = () => {
		setIsFullscreen(!isFullscreen)
	}

	// Handle file download
	const handleDownload = () => {
		if (disPlayDetail?.data?.file_name && disPlayDetail?.currentFileId) {
			getTemporaryDownloadUrl({ file_ids: [disPlayDetail?.currentFileId] }).then(
				(res: any) => {
					window.open(res[0]?.url, "_blank")
				},
			)
		}
	}

	return {
		isFullscreen,
		isFromNode,
		handlePrevious,
		handleNext,
		handleFullscreen,
		handleDownload,
		handleOpenFile,
		allFiles,
		currentIndex,
		effectiveAttachments,
	}
}

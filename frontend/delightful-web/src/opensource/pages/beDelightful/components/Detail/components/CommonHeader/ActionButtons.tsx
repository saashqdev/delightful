import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { memo } from "react"
import {
	IconArrowsDiagonal,
	IconArrowsDiagonalMinimize2,
	IconChevronLeft,
	IconChevronRight,
	IconDownload,
	IconX,
} from "@tabler/icons-react"
import { Flex } from "antd"
import { DetailType } from "../../types"
import { useResponsive } from "ahooks"
import { useStyles } from "./ActionButton.style"

interface ActionButtonsProps {
	type?: string
	currentAttachmentIndex?: number
	totalFiles?: number
	onPrevious?: () => void
	onNext?: () => void
	onFullscreen?: () => void
	onDownload?: () => void
	onClose?: () => void
	setUserSelectDetail?: (detail: any) => void
	hasUserSelectDetail?: boolean
	isFromNode?: boolean
	isFullscreen?: boolean
	isMobile?: boolean
}

export default memo(function ActionButtons(props: ActionButtonsProps) {
	const {
		type,
		currentAttachmentIndex = -1,
		totalFiles = 0,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		onClose,
		setUserSelectDetail,
		hasUserSelectDetail = true,
		isFromNode = false,
		isFullscreen = false,
	} = props

	const { styles } = useStyles()

	const isMobile = useResponsive().md === false

	// If opened from Node, do not show buttons
	if (isFromNode) {
		return null
	}

	// Check if navigation is possible
	const canNavigatePrev = currentAttachmentIndex > 0
	const canNavigateNext = currentAttachmentIndex < totalFiles - 1 && totalFiles > 0

	// Check if current file type is downloadable
	const isDownloadable = () => {
		if (isFromNode) return false
		// Check if file type is downloadable: pdf, html, md, text or code files
		return (
			type &&
			(type === DetailType.Pdf ||
				type === DetailType.Html ||
				type === DetailType.Md ||
				type === DetailType.Text ||
				type === DetailType.Code ||
				type === DetailType.Excel ||
				type === DetailType.PowerPoint)
		)
	}

	// Handle navigation
	const handlePrevious = () => {
		if (onPrevious) {
			onPrevious()
		}
	}

	const handleNext = () => {
		if (onNext) {
			onNext()
		}
	}

	// Handle fullscreen
	const handleFullscreen = () => {
		if (onFullscreen) {
			onFullscreen()
		}
	}

	// Handle download
	const handleDownload = () => {
		if (onDownload) {
			onDownload()
		}
	}

	// Handle close
	const handleClose = () => {
		if (onClose) {
			onClose()
		} else if (setUserSelectDetail) {
			setUserSelectDetail(null)
		}
	}

	return (
		<Flex gap={4}>
			{hasUserSelectDetail && (
				<DelightfulIcon
					size={28}
					component={IconChevronLeft}
					stroke={2}
					className={`${styles.iconCommon} ${!canNavigatePrev && styles.disabled}`}
					onClick={canNavigatePrev ? handlePrevious : undefined}
				/>
			)}
			{hasUserSelectDetail && (
				<DelightfulIcon
					size={28}
					component={IconChevronRight}
					stroke={2}
					className={`${styles.iconCommon} ${!canNavigateNext && styles.disabled}`}
					onClick={canNavigateNext ? handleNext : undefined}
				/>
			)}

			{/* Mobile devices do not show fullscreen button */}
			{!isMobile && !isFullscreen && (
				<DelightfulIcon
					size={28}
					component={IconArrowsDiagonal}
					stroke={2}
					className={styles.iconCommon}
					onClick={handleFullscreen}
				/>
			)}
			{!isMobile && isFullscreen && (
				<DelightfulIcon
					size={28}
					component={IconArrowsDiagonalMinimize2}
					stroke={2}
					className={styles.iconCommon}
					onClick={handleFullscreen}
				/>
			)}

			{hasUserSelectDetail && isDownloadable() && (
				<DelightfulIcon
					size={28}
					component={IconDownload}
					stroke={2}
					className={`${styles.iconCommon}`}
					onClick={isDownloadable() ? handleDownload : undefined}
				/>
			)}

			{hasUserSelectDetail && !isMobile && (
				<DelightfulIcon
					size={28}
					component={IconX}
					stroke={2}
					className={`${styles.iconCommon}`}
					onClick={handleClose}
				/>
			)}
		</Flex>
	)
})

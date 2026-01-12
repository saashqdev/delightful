import { observer } from "mobx-react-lite"
import { IconX, IconArrowsDiagonal, IconArrowsDiagonalMinimize2 } from "@tabler/icons-react"
import { computed } from "mobx"
import { lazy, Suspense, useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"

import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulEmpty from "@/opensource/components/base/DelightfulEmpty"

import { useStyles } from "./styles"

import ChatFileService from "@/opensource/services/chat/file/ChatFileService"
import MessageFilePreviewService from "@/opensource/services/chat/message/MessageFilePreview"
import MessageFilePreviewStore from "@/opensource/stores/chatNew/messagePreview/FilePreviewStore"

const UniverComponent = lazy(() => import("@/opensource/components/UniverComponent"))
const DelightfulPdfRender = lazy(() => import("@/opensource/components/base/DelightfulPdfRender"))

const ChatFilePreviewPanel = observer(function ChatFilePreviewPanel() {
	const { t } = useTranslation("interface")
	const { styles } = useStyles()

	const previewInfo = MessageFilePreviewStore.previewInfo
	const fileInfo = useMemo(() => {
		return computed(() => {
			if (!previewInfo) return null

			if (previewInfo.src) {
				return {
					download_name: previewInfo.file_name!,
					url: previewInfo.src,
					file_extension: previewInfo.file_extension,
				}
			}

			return ChatFileService.getFileInfoCache(previewInfo.file_id)
		})
	}, [previewInfo]).get()

	const [data, setData] = useState<File | null>(null)
	const [isFullscreen, setIsFullscreen] = useState(false)

	useEffect(() => {
		if (fileInfo?.url) {
			fetch(fileInfo.url)
				.then((res) => res.blob())
				.then((blob) => {
					const file = new File([blob], fileInfo.download_name, { type: blob.type })
					setData(file)
				})
		}
	}, [fileInfo?.download_name, fileInfo?.url])

	// Toggle fullscreen logic
	const toggleFullscreen = () => {
		setIsFullscreen(!isFullscreen)
	}

	const Content = () => {
		if (!fileInfo || !data) return <DelightfulEmpty />

		switch (previewInfo?.file_extension) {
			case "xlsx":
			case "xls":
				return (
					<UniverComponent
						data={data}
						fileType="sheet"
						fileName={fileInfo.download_name}
						mode="readonly"
					/>
				)
			case "pdf":
				return <DelightfulPdfRender file={data} height="100%" />
			default:
				return <DelightfulEmpty description={t("chat.filePreview.notSupportFileType")} />
		}
	}

	return (
		<div className={`${styles.container} ${isFullscreen ? styles.fullscreen : ""}`}>
			<div className={styles.header}>
				<div className={styles.headerLeft}>
					<div className={styles.headerLeftTitle}>
						<span>{t("chat.filePreview.title")}</span>
					</div>
				</div>
				<div className={styles.headerRight}>
					<DelightfulButton
						type="text"
						icon={
							<DelightfulIcon
								component={
									isFullscreen ? IconArrowsDiagonalMinimize2 : IconArrowsDiagonal
								}
							/>
						}
						onClick={toggleFullscreen}
					/>
					<DelightfulButton
						type="text"
						icon={<DelightfulIcon component={IconX} />}
						onClick={MessageFilePreviewService.clearPreviewInfo}
					/>
				</div>
			</div>
			<div className={styles.content} style={{ height: "calc(100% - 40px)" }}>
				<Suspense fallback={null}>{Content()}</Suspense>
			</div>
		</div>
	)
})

export default ChatFilePreviewPanel

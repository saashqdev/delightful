import { useMessageRenderContext } from "@/opensource/components/business/MessageRenderProvider/hooks"
import { useBoolean, useMemoizedFn, useUpdateEffect } from "ahooks"
import type React from "react"
import { useState, memo, useMemo, useRef, useEffect } from "react"
import { isString } from "lodash-es"
import { useTranslation } from "react-i18next"
import { Skeleton, Tooltip } from "antd"
import MessageFilePreviewService from "@/opensource/services/chat/message/MessageImagePreview"
import MessageFilePreviewStore from "@/opensource/stores/chatNew/messagePreview/ImagePreviewStore"
import useChatFileUrls from "@/opensource/hooks/chat/useChatFileUrls"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import { observer } from "mobx-react-lite"
import type { ImagePreviewInfo } from "@/types/chat/preview"
import useImageSize from "../../hooks/useImageSize"
import { useStyles } from "./styles"
import { IconPhotoX } from "@tabler/icons-react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { safeJsonToBtoa } from "@/utils/encoding"

type HTMLImageElementProps = JSX.IntrinsicElements["img"]

interface ImageWrapperProps extends HTMLImageElementProps {
	/** Container className */
	containerClassName?: string
	/** File id */
	fileId?: string
	/** Old file id */
	oldFileId?: string
	/** Index of image in the message */
	index?: number
	/** Message id */
	messageId?: string
	/** Standalone render, not participating in page navigation */
	standalone?: boolean
	/** Whether to use HD image */
	useHDImage?: boolean
	/** Callback when image fails to load */
	reload?: () => void
	/** Image extension */
	imgExtension?: string
	/** Image size */
	fileSize?: number
	/** Placeholder during image loading */
	loader?: (cls?: string) => React.ReactNode
	/** Whether is loading */
	isLoading?: boolean
	/** Whether is Error */
	isError?: boolean
}

const ImageWrapper = observer((props: ImageWrapperProps) => {
	const {
		src: srcInProps,
		alt,
		className,
		containerClassName,
		fileId,
		oldFileId,
		index = 0,
		messageId,
		standalone = false,
		useHDImage = false,
		reload,
		onError: onErrorInProps,
		onLoad: onLoadInProps,
		imgExtension,
		fileSize,
		loader,
		isLoading: loading = false,
		isError: isError = false,
		...rest
	} = props
	const { t } = useTranslation("interface")
	const { styles, cx } = useStyles()
	const { hiddenDetail } = useMessageRenderContext()
	const imageRef = useRef<HTMLImageElement>(null)
	const [fileInfo, setFileInfo] = useState<ImagePreviewInfo>()
	const isLongImage = useImageSize(fileInfo?.url)

	const [error, { setTrue, setFalse }] = useBoolean(false)
	const { currentConversation: conversation } = conversationStore

	const { data: urls, isLoading } = useChatFileUrls(
		useMemo(() => {
			const files = []
			if (messageId && fileId && isString(fileId)) {
				files.push({
					message_id: messageId,
					file_id: fileId,
				})
				if (oldFileId && isString(oldFileId)) {
					files.push({
						message_id: messageId,
						file_id: oldFileId,
					})
				}
			}
			return files
		}, [messageId, fileId, oldFileId]),
	)

	useEffect(() => {
		const url = fileId && messageId ? urls?.[fileId]?.url : srcInProps
		const oldUrl = oldFileId && messageId ? urls?.[oldFileId]?.url : undefined

		if (imgExtension?.startsWith("svg")) {
			setFileInfo({
				url,
				ext: { ext: "svg+xml", mime: "image/svg+xml" },
				fileId,
				messageId,
				conversationId: conversation?.id,
				index,
				standalone,
				useHDImage,
				fileSize,
			})
		} else {
			setFileInfo({
				url,
				ext: { ext: "jpg", mime: "image/jpeg" }, // Default to jpg, no need to know exact type, won't affect judgment for now
				fileId,
				oldFileId,
				oldUrl,
				messageId,
				conversationId: conversation?.id,
				index,
				standalone,
				useHDImage,
				fileSize,
			})
		}
	}, [
		conversation?.id,
		fileId,
		fileSize,
		imgExtension,
		index,
		messageId,
		oldFileId,
		srcInProps,
		standalone,
		urls,
		useHDImage,
	])

	useUpdateEffect(() => {
		const { previewInfo, open } = MessageFilePreviewStore
		// If preview modal is open and old file id matches current preview file id and using HD image, reset preview info
		if (open && previewInfo?.fileId === fileInfo?.oldFileId && fileInfo?.useHDImage) {
			MessageFilePreviewService.setPreviewInfo(fileInfo)
		}
	}, [fileInfo, MessageFilePreviewStore.previewInfo])

	const handleReloadImage = useMemoizedFn(() => {
		reload?.()
		setFalse()
	})

	const onError = useMemoizedFn((e) => {
		onErrorInProps?.(e)
		setTrue()
	})

	const onLoad = useMemoizedFn((e) => {
		onLoadInProps?.(e)
	})

	const fileInfoBase64 = useMemo(() => {
		return safeJsonToBtoa(fileInfo ?? {})
	}, [fileInfo])

	const ImageNode = useMemo(() => {
		if (fileInfo?.ext?.ext?.startsWith("svg") && fileInfo?.url) {
			return (
				<button
					type="button"
					className={cx(styles.button)}
					disabled={!fileInfo?.url}
					draggable={false}
				>
					<div
						className={styles.image}
						dangerouslySetInnerHTML={{ __html: fileInfo?.url }}
					/>
				</button>
			)
		}

		if (loading || isLoading || !fileInfo?.url) {
			return loader ? (
				loader?.(className)
			) : (
				<Skeleton.Image className={styles.skeletonImage} active={isLoading} />
			)
		}

		return (
			<div style={{ height: "100%" }}>
				{isLongImage && (
					<span className={styles.longImageTip}>{t("chat.message.image.longImage")}</span>
				)}
				<img
					ref={imageRef}
					src={fileInfo?.url}
					alt={alt ?? fileInfo?.fileName}
					data-file-info={fileInfoBase64}
					className={cx(
						styles.image,
						{ [styles.longImage]: isLongImage },
						className,
						"delightful-image",
					)}
					onError={onError}
					onLoad={onLoad}
					draggable={false}
					{...rest}
				/>
			</div>
		)
	}, [
		alt,
		className,
		cx,
		fileInfo?.ext?.ext,
		fileInfo?.fileName,
		fileInfo?.url,
		fileInfoBase64,
		isLoading,
		isLongImage,
		loader,
		loading,
		onError,
		onLoad,
		rest,
		styles.button,
		styles.image,
		styles.longImage,
		styles.longImageTip,
		styles.skeletonImage,
		t,
	])

	if (hiddenDetail) {
		return t("chat.message.placeholder.image")
	}

	if (isError || error) {
		return (
			<Tooltip title={t("chat.message.networkError.reload")}>
				<div className={styles.imageLoadError} onClick={handleReloadImage}>
					<DelightfulIcon component={IconPhotoX} size={16} />
					{t("chat.message.imageLoadFailed")}
					<DelightfulButton
						className="reload-button"
						size="small"
						onClick={handleReloadImage}
					/>
				</div>
			</Tooltip>
		)
	}

	return <div className={cx(containerClassName, styles.wrapper)}>{ImageNode}</div>
})

const MemoizedImage = memo(ImageWrapper) as typeof ImageWrapper

export default MemoizedImage

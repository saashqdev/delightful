import { useTranslation } from "react-i18next"
import { Flex, Skeleton } from "antd"
import { IconDownload, IconEye } from "@tabler/icons-react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { calculateRelativeSize } from "@/utils/styles"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import type { ConversationMessageAttachment } from "@/types/chat/conversation_message"
import FileIcon from "@/opensource/components/business/FileIcon"
import { useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import { formatFileSize } from "@/utils/string"
import { downloadFile } from "@/utils/file"
import { IMAGE_EXTENSIONS } from "@/const/file"
import { useConversationMessage } from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageItem/components/ConversationMessageProvider/hooks"
import useChatFileUrls from "@/opensource/hooks/chat/useChatFileUrls"
import MessageFilePreviewService from "@/opensource/services/chat/message/MessageFilePreview"
import ImageWrapper from "@/opensource/components/base/DelightfulImagePreview/components/ImageWrapper"
import ConversationStore from "@/opensource/stores/chatNew/conversation"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import { useStyles } from "./style"
import Attachments from "./Attachments"
import { observer } from "mobx-react-lite"

interface DelightfulFileProps {
	data?: ConversationMessageAttachment[]
	messageId: string
	display?: boolean // 是否显示
}

const DelightfulFiles = observer(({ data, messageId, display = true }: DelightfulFileProps) => {
	const { t } = useTranslation("interface")
	const { fontSize, buttonSize } = useFontSize()
	const { isUnReceived } = useConversationMessage()

	const { styles } = useStyles({ fontSize })

	const { data: fileUrls, isLoading } = useChatFileUrls(
		useMemo(
			() =>
				data && messageId
					? data?.map((item) => ({
							file_id: item.file_id,
							message_id: messageId,
					  }))
					: [],
			[data, messageId],
		),
	)

	/**
	 * downloadfile
	 * @param fileId fileID
	 */
	const onDownload = useMemoizedFn((fileId: string) => {
		const fileUrl = fileUrls?.[fileId]?.url
		console.log("fileUrls?.[fileId]?.download_name =======> ", fileUrls)
		if (fileUrl) {
			downloadFile(fileUrl, fileUrls?.[fileId]?.download_name)
		}
	})

	/**
	 * 预览file
	 * @param fileInfo fileinformation
	 */
	const onPreview = useMemoizedFn((fileInfo: ConversationMessageAttachment) => {
		console.log("fileInfo =======> ", fileInfo)
		MessageFilePreviewService.openPreview({
			message_id: messageId,
			conversation_id: ConversationStore.currentConversation?.id,
			...fileInfo,
		})
	})

	if (!data || data.length === 0) return null

	if (data.length > 1) {
		return (
			<Attachments
				isLoading={isLoading}
				data={data}
				maxCount={3}
				onDownload={onDownload}
				onPreview={onPreview}
			/>
		)
	}

	const dataFirst = data[0]

	if (!dataFirst) {
		return (
			<Flex className={styles.container} style={{ padding: 10, width: "fit-content" }}>
				{t("chat.file.upload_failed")}
			</Flex>
		)
	}

	// 当file是图片class型时, 显示单个图片
	if (IMAGE_EXTENSIONS.includes(dataFirst.file_extension?.toLocaleLowerCase() ?? "")) {
		return (
			<ImageWrapper
				className={styles.image}
				src={fileUrls?.[dataFirst.file_id]?.url}
				alt={dataFirst.file_name}
				fileId={dataFirst.file_id}
				messageId={messageId}
			/>
		)
	}

	// 在录音纪要的情况下，只需要记录file数据，不需要显示fileUI，默认显示
	if (!display) return null
	const loading = isLoading || isUnReceived

	return (
		<Flex vertical className={styles.container} onClick={(e) => e.stopPropagation()}>
			<Flex className={styles.top} gap={calculateRelativeSize(8, fontSize)} align="center">
				<FileIcon ext={dataFirst.file_extension} size={32} />
				<Flex vertical justify="space-between" gap={loading ? 0 : 4}>
					<div className={styles.name}>
						<Skeleton active paragraph={false} loading={loading} style={{ width: 80 }}>
							{dataFirst.file_name}
						</Skeleton>
					</div>
					<Skeleton active paragraph={false} loading={loading} style={{ width: 40 }}>
						{dataFirst.file_size ? (
							<div className={styles.size}>{formatFileSize(dataFirst.file_size)}</div>
						) : null}
					</Skeleton>
				</Flex>
			</Flex>
			<Flex className={styles.footer}>
				<DelightfulButton
					type="text"
					block
					hidden={!MessageFilePreviewService.canPreview(dataFirst)}
					className={styles.button}
					size={buttonSize}
					disabled={loading}
					onClick={() => onPreview(dataFirst)}
				>
					<Flex align="center" justify="center" gap={4}>
						<DelightfulIcon
							component={IconEye}
							color="currentColor"
							size={calculateRelativeSize(18, fontSize)}
						/>
						{t("chat.file.preview")}
					</Flex>
				</DelightfulButton>
				<DelightfulButton
					type="text"
					block
					className={styles.button}
					size={buttonSize}
					disabled={loading}
					onClick={() => onDownload(dataFirst.file_id)}
				>
					<Flex align="center" justify="center" gap={4}>
						<DelightfulIcon
							component={IconDownload}
							color="currentColor"
							size={calculateRelativeSize(18, fontSize)}
						/>
						{t("chat.file.download")}
					</Flex>
				</DelightfulButton>
				{/* <DelightfulButton
					type="text"
					block
					className={styles.button}
					size={buttonSize}
					disabled={loading}
				>
					<Flex align="center" justify="center" gap={4}>
						<DelightfulIcon
							component={IconDeviceFloppy}
							color="currentColor"
							size={calculateRelativeSize(18, fontSize)}
						/>
						{t("chat.file.saveTo")}
					</Flex>
				</DelightfulButton> */}
			</Flex>
		</Flex>
	)
})

export default DelightfulFiles

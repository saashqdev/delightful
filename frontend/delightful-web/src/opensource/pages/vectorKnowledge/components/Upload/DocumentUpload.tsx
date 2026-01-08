import { message, Upload } from "antd"
import type { PropsWithChildren } from "react"
import { SUPPORTED_EMBED_FILE_TYPES, supportedFileExtensions } from "../../constant"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import { getFileExtension } from "../../utils"

interface DocumentUploadProps {
	children: React.ReactNode
	dragger?: boolean
	handleFileUpload: (file: File) => void
}

export default function DocumentUpload({
	children,
	handleFileUpload,
	dragger = true,
}: PropsWithChildren<DocumentUploadProps>) {
	const { t: flowT } = useTranslation("flow")

	/** Upload file - pre-validation */
	const beforeFileUpload = useMemoizedFn((file: File) => {
		const fileExtension = getFileExtension(file.name)

		if (!supportedFileExtensions.includes(fileExtension)) {
			message.error(flowT("knowledgeDatabase.unsupportedFileType", { type: fileExtension }))
			return false
		}

		// Validate file size
		const isLt15M = file.size / 1024 / 1024 < 15
		if (!isLt15M) {
			message.error(flowT("knowledgeDatabase.fileSizeLimit", { size: "15MB" }))
			return false
		}

		handleFileUpload(file)
		// Return false directly to prevent component upload and use custom upload
		return false
	})

	return (
		<>
			{dragger ? (
				<Upload.Dragger
					accept={SUPPORTED_EMBED_FILE_TYPES}
					multiple
					showUploadList={false}
					beforeUpload={beforeFileUpload}
				>
					{children}
				</Upload.Dragger>
			) : (
				<Upload
					accept={SUPPORTED_EMBED_FILE_TYPES}
					multiple
					showUploadList={false}
					beforeUpload={beforeFileUpload}
				>
					{children}
				</Upload>
			)}
		</>
	)
}

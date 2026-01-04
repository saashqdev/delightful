import ImageIcon from "@/opensource/components/base/MagicFileIcon/assets/image.svg"
import CommonHeader from "@/opensource/pages/superMagic/components/Detail/components/CommonHeader"
import { useState, useEffect, useRef, memo } from "react"
import { Image } from "antd"
import { useStyles } from "./style"
import CommonFooter from "../../components/CommonFooter"
import { useFileUrl } from "@/opensource/pages/superMagic/hooks/useFileUrl"

function Img(props: any) {
	const { styles } = useStyles()
	const {
		type,
		currentIndex,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		totalFiles,
		hasUserSelectDetail,
		setUserSelectDetail,
		userSelectDetail,
		isFromNode,
		onClose,
		isFullscreen,
		data,
	} = props

	const { file_name, file_id } = data
	const { fileUrl } = useFileUrl({ file_id })
	return (
		<div className={styles.pdfViewer}>
			<CommonHeader
				title={file_name}
				icon={<img src={ImageIcon} alt="" />}
				type={type}
				currentAttachmentIndex={currentIndex}
				totalFiles={totalFiles}
				onPrevious={onPrevious}
				onNext={onNext}
				onFullscreen={onFullscreen}
				onDownload={onDownload}
				hasUserSelectDetail={hasUserSelectDetail}
				setUserSelectDetail={setUserSelectDetail}
				isFromNode={isFromNode}
				onClose={onClose}
				isFullscreen={isFullscreen}
			/>
			<div className={styles.pdfContainer}>
				<Image src={fileUrl} alt="" preview={false} />
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

export default memo(Img)

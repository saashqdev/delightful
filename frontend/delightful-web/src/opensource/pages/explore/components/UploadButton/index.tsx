import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import UploadAction from "@/opensource/components/base/UploadAction"
import { IMStyle } from "@/opensource/providers/AppearanceProvider/context"
import { IconCloudUpload } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import { createStyles } from "antd-style"

const useStyles = createStyles(({ token, css }) => {
	return {
		uploadButton: css`
			display: flex;
			width: 90px;
			height: 32px;
			justify-content: center;
			align-items: center;
			gap: 10px;
			border-radius: 20px;
			border: 1px solid ${token.colorBorder};
		`,
	}
})

interface UploadButtonProps extends DelightfulButtonProps {
	icon?: React.ReactNode
	text?: string
	imStyle?: IMStyle
	loading?: boolean
	onFileChange?: (files: FileList) => void
}

function UploadButton({
	icon,
	text,
	imStyle: theme = IMStyle.Modern,
	onFileChange,
	loading,
	...props
}: UploadButtonProps) {
	const { t } = useTranslation("interface")
	const { styles } = useStyles()
	const UploadHandler = useMemoizedFn((onUpload) => {
		return (
			<DelightfulButton
				className={styles.uploadButton}
				type="text"
				loading={loading}
				onClick={onUpload}
				icon={icon || <DelightfulIcon size={20} component={IconCloudUpload} />}
				{...props}
			>
				{text || t("agent.upload")}
			</DelightfulButton>
		)
	})

	return <UploadAction multiple={false} handler={UploadHandler} onFileChange={onFileChange} />
}

export default UploadButton

import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import UploadAction from "@/opensource/components/base/UploadAction"
import { IMStyle } from "@/opensource/providers/AppearanceProvider/context"
import { IconFileUpload } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { memo, useMemo } from "react"
import { useTranslation } from "react-i18next"

interface UploadButtonProps extends DelightfulButtonProps {
	loading?: boolean
	icon?: React.ReactNode
	imStyle?: IMStyle
	onFileChange?: (files: FileList) => void
	multiple?: boolean
}

const UploadButton = ({
	icon,
	imStyle: theme = IMStyle.Modern,
	onFileChange,
	multiple = true,
	...props
}: UploadButtonProps) => {
	const { t } = useTranslation("interface")

	const isStandard = useMemo(() => theme === IMStyle.Standard, [theme])

	const UploadHandler = useMemoizedFn((onUpload) => {
		return (
			<DelightfulButton
				type="text"
				onClick={onUpload}
				icon={
					icon || (
						<DelightfulIcon color="currentColor" size={20} component={IconFileUpload} />
					)
				}
				{...props}
			>
				{isStandard ? t("chat.input.upload") : null}
			</DelightfulButton>
		)
	})

	return <UploadAction handler={UploadHandler} onFileChange={onFileChange} multiple={multiple} />
}

export default memo(UploadButton)

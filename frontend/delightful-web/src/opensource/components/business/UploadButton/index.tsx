import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import UploadAction from "@/opensource/components/base/UploadAction"
import type { IMStyle } from "@/opensource/providers/AppearanceProvider/context"
import { IconFileUpload } from "@tabler/icons-react"
import { useMemoizedFn } from "ahooks"
import { memo } from "react"

interface UploadButtonProps extends DelightfulButtonProps {
	loading?: boolean
	icon?: React.ReactNode
	imStyle?: IMStyle
	onFileChange?: (files: FileList) => void
	multiple?: boolean
}

const UploadButton = memo(
	({ icon, onFileChange, multiple = true, children, ...props }: UploadButtonProps) => {
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
					{children}
				</DelightfulButton>
			)
		})

		return (
			<UploadAction handler={UploadHandler} onFileChange={onFileChange} multiple={multiple} />
		)
	},
)

export default UploadButton

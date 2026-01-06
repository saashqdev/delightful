import type { DelightfulModalProps } from "@/opensource/components/base/DelightfulModal"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import type { OpenableProps } from "@/utils/react"
import { useMemoizedFn } from "ahooks"
import { Input } from "antd"
import { memo, useState, useEffect } from "react"
import { useTranslation } from "react-i18next"

export interface UpdateGroupNameModalProps extends DelightfulModalProps {
	initialGroupName?: string
	conversationId: string
	onSave?: (groupName: string) => Promise<void>
}

const UpdateGroupNameModal = memo((props: OpenableProps<UpdateGroupNameModalProps>) => {
	const { open, initialGroupName, onClose, onSave } = props
	console.log("initialGroupName", initialGroupName)

	const [groupName, setGroupName] = useState(initialGroupName ?? "")

	useEffect(() => {
		if (open) {
			setGroupName(initialGroupName ?? "")
		}
	}, [open])

	const { t } = useTranslation("interface")

	const close = useMemoizedFn(() => {
		onClose?.()
	})

	const onOk = useMemoizedFn(() => {
		onSave?.(groupName).finally(() => {
			close()
		})
	})

	const onCancel = useMemoizedFn(() => {
		close()
	})

	return (
		<DelightfulModal
			width={400}
			title={t("chat.groupSetting.updateGroupName")}
			open={open}
			onOk={onOk}
			centered
			onCancel={onCancel}
			okText={t("common.save")}
		>
			<Input
				placeholder={t("chat.groupSetting.groupNamePlaceholder")}
				value={groupName}
				showCount
				maxLength={50}
				onChange={(e) => setGroupName(e.target.value)}
			/>
		</DelightfulModal>
	)
})

UpdateGroupNameModal.displayName = "UpdateGroupNameModal"

export default UpdateGroupNameModal

import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { getShareInfoByCode } from "@/opensource/pages/beDelightful/utils/api"
import { memo, useCallback, useEffect, useMemo, useRef, useState } from "react"
import Share from "."
import { isEmpty } from "lodash-es"
import { message } from "antd"
import { createStyles } from "antd-style"
import type { ShareModalProps } from "./types"
import { ShareType } from "./types"

const useStyles = createStyles(() => ({
	shareModal: {
		padding: "10px !important",
	},
}))

export default memo(function ShareModel(props: ShareModalProps) {
	const { styles } = useStyles()
	const { types, shareContext, afterSubmit, onCancel, ...modalProps } = props

	const [type, setType] = useState<ShareType>(() => {
		return types[0] || ShareType.OnlySelf
	})
	const [extraData, setExtraData] = useState<any>({ passwordEnabled: true })
	const validateShareSettingsRef = useRef<() => boolean>(() => true)

	useEffect(() => {
		if (shareContext?.resource_id) {
			getShareInfoByCode({ code: shareContext.resource_id }).then((res: any) => {
				if (res?.resource_id) {
					setType(res?.share_type)
					setExtraData({
						password: res?.pwd,
						passwordEnabled: res?.has_password,
					})
				}
			})
		}
	}, [shareContext])

	// Generate link URL
	const shareUrl = useMemo(() => {
		return `${window.location.origin}/share/${shareContext?.resource_id}`
	}, [shareContext?.resource_id])

	// Copy link
	const handleCopyLink = useCallback(() => {
		const text = extraData?.passwordEnabled
			? `${extraData?.shareUrl}?password=${extraData?.password}`
			: extraData?.shareUrl || shareUrl
		navigator.clipboard.writeText(text)
		message.success("Link copied to clipboard")
	}, [extraData, shareUrl])

	const handleOk = async (newType: ShareType, newExtraData?: any) => {
		afterSubmit?.({
			type: newType,
			extraData: isEmpty(newExtraData) ? extraData : newExtraData,
		})
	}

	const handleCancel = async (event: React.MouseEvent<HTMLButtonElement>) => {
		onCancel?.(event)
		setExtraData({})
	}

	const getValidateShareSettings = (validate: () => boolean) => {
		validateShareSettingsRef.current = validate
	}

	const handleTypeChange = (newType: ShareType) => {
		if (newType === type) return

		if (newType === ShareType.OnlySelf) {
			DelightfulModal.confirm({
				title: "Notice",
				content: "After disabling topic sharing, all existing shared replay links will become invalid immediately",
				onOk: (e) => {
					console.log("onOk", e)
					e?.()
					setType(newType)
					handleOk(newType)
				},
				okText: "OK",
				cancelText: "Cancel",
			})
		} else if (newType === ShareType.Internet) {
			DelightfulModal.confirm({
				title: "Notice",
				content:
					"Topic replay is about to be shared on the internet. Once enabled, anyone can access the replay via the link or link with password",
				onOk: (e) => {
					e?.()
					setType(newType)
					handleOk(newType)
					handleCopyLink()
				},
				okText: "OK",
				cancelText: "Cancel",
			})
		}
	}

	return (
		<DelightfulModal
			title="Share"
			{...modalProps}
			//@ts-ignore Need to handle ts type compatibility here
			onOk={handleOk}
			onCancel={handleCancel}
			footer={null}
			classNames={{ body: styles.shareModal }}
		>
			<Share
				types={types}
				shareContext={shareContext}
				extraData={extraData}
				setExtraData={setExtraData}
				type={type}
				onChangeType={handleTypeChange}
				getValidateShareSettings={getValidateShareSettings}
				handleOk={handleOk}
				shareUrl={shareUrl}
				handleCopyLink={handleCopyLink}
			/>
		</DelightfulModal>
	)
})

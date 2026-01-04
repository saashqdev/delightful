import MagicModal from "@/opensource/components/base/MagicModal"
import { getShareInfoByCode } from "@/opensource/pages/superMagic/utils/api"
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

	// 生成链接URL
	const shareUrl = useMemo(() => {
		return `${window.location.origin}/share/${shareContext?.resource_id}`
	}, [shareContext?.resource_id])

	// 复制链接
	const handleCopyLink = useCallback(() => {
		const text = extraData?.passwordEnabled
			? `${extraData?.shareUrl}?password=${extraData?.password}`
			: extraData?.shareUrl || shareUrl
		navigator.clipboard.writeText(text)
		message.success("链接已复制到剪贴板")
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
			MagicModal.confirm({
				title: "提示",
				content: "关闭当前话题分享后，已分享的回放链接将即时失效",
				onOk: (e) => {
					console.log("onOk", e)
					e?.()
					setType(newType)
					handleOk(newType)
				},
				okText: "确定",
				cancelText: "取消",
			})
		} else if (newType === ShareType.Internet) {
			MagicModal.confirm({
				title: "提示",
				content:
					"当前话题回放即将开启互联网分享，开启后，任何人均可通过链接或链接及密码访问话题回放",
				onOk: (e) => {
					e?.()
					setType(newType)
					handleOk(newType)
					handleCopyLink()
				},
				okText: "确定",
				cancelText: "取消",
			})
		}
	}

	return (
		<MagicModal
			title="分享"
			{...modalProps}
			//@ts-ignore 这里需要兼容下ts类型
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
		</MagicModal>
	)
})

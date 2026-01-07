import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { IconCopy, IconUserCog } from "@tabler/icons-react"
import { Checkbox, message, Space, Switch } from "antd"
import { cx } from "antd-style"
import { memo, useCallback, useEffect, useMemo } from "react"
import useStyles from "./style"
import departmentOrMemberIcon from "./svg/department-or-member.svg"
import internetIcon from "./svg/internet.svg"
import organizationIcon from "./svg/organization.svg"
import StopShareIcon from "@/opensource/pages/beDelightful/assets/svg/stop_share.svg"
import type { ShareProps } from "./types"
import { ShareType } from "./types"

// Generate random password
const generateRandomPassword = (length: number) => {
	const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	let result = ""
	for (let i = 0; i < length; i += 1) {
		result += characters.charAt(Math.floor(Math.random() * characters.length))
	}
	return result
}

export default memo(function Share(props: ShareProps) {
	const {
		onChangeType,
		types,
		extraData,
		setExtraData,
		type,
		getValidateShareSettings,
		handleOk,
		shareUrl,
		handleCopyLink,
	} = props
	const { styles } = useStyles()

	useEffect(() => {
		if (setExtraData && !extraData?.shareUrl) {
			const newData = {
				passwordEnabled: false,
				password: generateRandomPassword(5),
				shareUrl,
				...extraData,
			}
			setExtraData(newData)
		}
	}, [extraData, setExtraData, shareUrl])

	// Toggle password switch
	const handlePasswordSwitch = useCallback(
		(checked: boolean) => {
			if (setExtraData) {
				const newExtraData = {
					...extraData,
					passwordEnabled: checked,
				// Auto-generate password if enabled
					password: checked ? generateRandomPassword(5) : extraData?.password,
				}
				setExtraData(newExtraData)
				handleOk?.(type, newExtraData)
			}
		},
		[extraData, setExtraData, type, handleOk],
	)

	// Reset password
	const handleResetPassword = useCallback(() => {
		DelightfulModal.confirm({
			title: "Notice",
			content: "After resetting the access password, the previously generated password will become invalid",
			onOk: () => {
				if (setExtraData) {
					const newExtraData = {
						...extraData,
						password: generateRandomPassword(5),
					}
					setExtraData(newExtraData)
					handleOk?.(type, newExtraData)
				}
			},
			okText: "OK",
			cancelText: "Cancel",
		})
	}, [extraData, setExtraData, handleOk, type])

	// Validate share settings
	const validateShareSettings = useCallback(() => {
		// Password is always auto-generated now, no validation needed
		return true
	}, [])

	// Pass validation function to parent component
	useEffect(() => {
		if (getValidateShareSettings) {
			getValidateShareSettings(validateShareSettings)
		}
	}, [getValidateShareSettings, validateShareSettings])

	const list = useMemo(() => {
		return [
			{
				type: ShareType.OnlySelf,
				icon: StopShareIcon,
				title: "Disable Sharing",
				description: "Turn off topic sharing feature",
				backgroundColor: styles.backgroundOnlySelf,
			},
			{
				type: ShareType.Organization,
				icon: organizationIcon,
				title: "Organization Access",
				description: "All members of the current organization can access",
				backgroundColor: styles.backgroundOrganization,
			},
			{
				type: ShareType.DepartmentOrMember,
				icon: departmentOrMemberIcon,
				title: "Specific Department/Member Access",
				description: "Specified departments or members can access",
				backgroundColor: styles.backgroundDepartmentOrMember,
				rightContent: (
					<DelightfulButton
						size="small"
						color="primary"
						variant="outlined"
						icon={<DelightfulIcon component={IconUserCog} size={16} color="currentColor" />}
						className={styles.departmentOrMemberButton}
						onClick={(event) => {
							event.stopPropagation()
						}}
					>
						Manage
					</DelightfulButton>
				),
			},
			{
				type: ShareType.Internet,
				icon: internetIcon,
				title: "Internet Access",
				description: "Anyone with the link can access",
				backgroundColor: styles.backgroundInternet,
				bottomContent: (
					<Space direction="vertical" size={12} className={styles.internetContent}>
						<div className={styles.internetLinkWrapper}>
							<div className={styles.internetLink}>
								<span>{extraData?.shareUrl || shareUrl}</span>
							</div>
							<DelightfulButton
								variant="filled"
								color="default"
								icon={<DelightfulIcon component={IconCopy} size={18} />}
								className={styles.copyButton}
								onClick={handleCopyLink}
							>
							Copy Link{extraData?.passwordEnabled ? " and Password" : null}
							</DelightfulButton>
						</div>
						<Space size={8}>
							<Switch
								size="default"
								checked={extraData?.passwordEnabled}
								onChange={handlePasswordSwitch}
							/>
						<span className={styles.passwordTitle}>Access Password</span>
						{extraData?.passwordEnabled && (
							<>
								<div className={styles.password}>
									{extraData?.password || ""}
								</div>
								<DelightfulButton size="middle" onClick={handleResetPassword}>
									Reset Password
									</DelightfulButton>
								</>
							)}
						</Space>
					</Space>
				),
			},
		].filter((item) => types.includes(item.type))
	}, [
		styles.backgroundOnlySelf,
		styles.backgroundOrganization,
		styles.backgroundDepartmentOrMember,
		styles.departmentOrMemberButton,
		styles.backgroundInternet,
		styles.internetContent,
		styles.internetLinkWrapper,
		styles.internetLink,
		styles.copyButton,
		styles.passwordTitle,
		styles.password,
		extraData?.shareUrl,
		extraData?.passwordEnabled,
		extraData?.password,
		shareUrl,
		handleCopyLink,
		handlePasswordSwitch,
		handleResetPassword,
		types,
	])

	return (
		<div className={styles.shareContainer}>
			{list.map((item) => {
				const isActive = item.type === type
				return (
					<div key={item.type}>
						<div
							className={styles.top}
							onClick={() => {
								onChangeType?.(item.type)
							}}
						>
							<div className={styles.left}>
								<div className={cx(styles.icon, item.backgroundColor)}>
									<img src={item.icon} alt={item.title} />
								</div>
								<div className={styles.info}>
									<div className={styles.title}>{item.title}</div>
									<div className={styles.description}>{item.description}</div>
								</div>
							</div>
							<div className={styles.right}>
								{isActive && item.rightContent}
								<Checkbox className={styles.checkbox} checked={isActive} />
							</div>
						</div>

						{isActive && item.bottomContent && (
							<div className={styles.bottom}>{item.bottomContent}</div>
						)}
					</div>
				)
			})}
		</div>
	)
})

import DelightfulCollapse from "@/opensource/components/base/DelightfulCollapse"
import { Flex, Input } from "antd"
import { useTranslation } from "react-i18next"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import PageContainer from "@/opensource/components/base/PageContainer"
import { useCurrentDelightfulOrganization, useUserInfo } from "@/opensource/models/user/hooks"
import AiCompletionSwitch from "./featrues/AiCompletionSwitch"
import AppearenceSwitch from "./featrues/AppearenceSwitch"
import FontSizeChanger from "./featrues/FontSizeChanger"
import IMStyleSwitch from "./featrues/IMStyleSwitch"
import LanguageSwitch from "./featrues/LanguageSwitch"
import LoginDevices from "./featrues/LoginDevices"
import SettingItem from "./featrues/SettingItem"
import ShortcutKeyInput from "./featrues/ShortcutKeyInput"
import { SettingSection } from "./types"
import AccountManageTip from "./components/AccountManageTip"
import { useStyles } from "./styles"
import { useLocation } from "react-router"
import { useEffect } from "react"

function SettingPage() {
	const { t } = useTranslation("interface")
	const { state } = useLocation()

	useEffect(() => {
		if (state?.type) {
			// Scroll to specified position
			const target = document.getElementById(state.type)
			if (target) {
				target.scrollIntoView({ behavior: "smooth" })
			}
		}
	}, [state?.type])

	const { styles } = useStyles()

	const { userInfo: info } = useUserInfo()

	const organization = useCurrentDelightfulOrganization()

	return (
		<PageContainer title={t("setting.systemSetting")} className={styles.container}>
			<DelightfulCollapse
				ghost={false}
				bordered={false}
				className={styles.collapse}
				defaultActiveKey={[
					SettingSection.GENERAL_SETTING,
					SettingSection.HOTKEYS_SETTING,
					SettingSection.ACCOUNT_MANAGE,
					SettingSection.LOGIN_DEVICES,
				]}
				items={[
					{
						key: SettingSection.GENERAL_SETTING,
						label: (
							<span id={SettingSection.GENERAL_SETTING}>
								{t("setting.generalSetting")}
							</span>
						),
						children: (
							<>
								<SettingItem
									title={t("setting.appearance")}
									description={t("setting.appearanceDescription")}
									extra={<AppearenceSwitch />}
								/>
								<SettingItem
									title={t("setting.language")}
									description={t("setting.languageDescription")}
									extra={<LanguageSwitch />}
								/>
								<IMStyleSwitch />
								<SettingItem
									title={t("setting.fontSize")}
									description={t("setting.fontSizeDescription")}
									extra={<FontSizeChanger />}
								/>
								<SettingItem
									title={t("setting.aiCompletion")}
									description={t("setting.aiCompletionDescription")}
									extra={<AiCompletionSwitch />}
								/>
							</>
						),
					},
					{
						key: SettingSection.HOTKEYS_SETTING,
						label: (
							<span id={SettingSection.HOTKEYS_SETTING}>
								{t("setting.hotKeySetting")}
							</span>
						),
						children: (
							<SettingItem
								title={t("setting.search")}
								description={t("setting.searchDescription")}
								extra={<ShortcutKeyInput />}
							/>
						),
					},
					{
						key: SettingSection.ACCOUNT_MANAGE,
						label: (
							<span id={SettingSection.ACCOUNT_MANAGE}>
								{t("setting.accountManage")}
							</span>
						),
						children: (
							<>
								<AccountManageTip />
								<SettingItem
									title={t("setting.delightfulId")}
									extra={info?.delightful_id}
								/>
								<SettingItem
									title={t("setting.enterprise")}
									extra={
										<Flex align="center" gap={10}>
											<DelightfulAvatar
												src={organization?.organization_logo}
												size={32}
											>
												{organization?.organization_name}
											</DelightfulAvatar>
											{organization?.organization_name}
										</Flex>
									}
								/>
								<SettingItem
									title={t("setting.avatar")}
									extra={
										<DelightfulAvatar src={info?.avatar}>
											{info?.nickname}
										</DelightfulAvatar>
									}
								/>
								<SettingItem
									title={t("setting.realName")}
									extra={
										<Input
											disabled
											placeholder={t("setting.realNamePlaceholder")}
											value={info?.nickname}
										/>
									}
								/>
							</>
						),
					},
					// {
					// 	key: SettingSection.LOGIN_DEVICES,
					// 	label: (
					// 		<span id={SettingSection.LOGIN_DEVICES}>
					// 			{t("setting.loginDevices")}
					// 		</span>
					// 	),
					// 	children: <LoginDevices />,
					// },
				]}
			/>
		</PageContainer>
	)
}

export default SettingPage

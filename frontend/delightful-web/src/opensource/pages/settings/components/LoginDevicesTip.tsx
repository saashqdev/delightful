import { Flex } from "antd"
import { useTheme, useThemeMode } from "antd-style"
import { useTranslation } from "react-i18next"
import { IconShieldLockFilled } from "@tabler/icons-react"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"

export default function LoginDevicesTip() {
	const { t } = useTranslation("interface")
	const { isDarkMode } = useThemeMode()

	const { delightfulColorScales, delightfulColorUsages } = useTheme()

	return (
		<Flex
			align="center"
			gap={4}
			style={{
				padding: "10px 20px",
				flex: 1,
				backgroundColor: isDarkMode
					? delightfulColorUsages.black
					: delightfulColorScales.green[0],
			}}
		>
			<DelightfulIcon
				component={IconShieldLockFilled}
				color={delightfulColorScales.green[5]}
				size={18}
			/>
			<span>{t("setting.tip.loginDevicesTip")}</span>
		</Flex>
	)
}

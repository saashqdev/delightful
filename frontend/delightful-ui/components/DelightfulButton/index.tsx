import { colorScales as baseColorScales } from "../ThemeProvider/colors"
import type { ButtonProps, ThemeConfig } from "antd"
import { Button, Tooltip } from "antd"
import type { GetAntdTheme } from "antd-style"
import { useTheme, ThemeProvider, cx } from "antd-style"
import { forwardRef, memo, useMemo, type CSSProperties, type ReactNode } from "react"
import { useStyles } from "./style"

export interface DelightfulButtonProps extends ButtonProps {
	justify?: CSSProperties["justifyContent"]
	theme?: boolean
	tip?: ReactNode
}

const DelightfulButton = memo(
	forwardRef<HTMLButtonElement, DelightfulButtonProps>(
		(
			{
				tip,
				className,
				justify = "center",
				theme = true,
				hidden,
				...props
			}: DelightfulButtonProps,
			ref,
		) => {
			const { styles } = useStyles({ justify })

			const { delightfulColorUsages } = useTheme()

			const themeConfigs = useMemo<ThemeConfig | GetAntdTheme | undefined>(() => {
				return theme
					? (appearence) => ({
							components: {
								Button: {
									colorLink: delightfulColorUsages.primary.default,
									colorLinkHover: delightfulColorUsages.primary.hover,
									colorLinkActive: delightfulColorUsages.primary.active,
									colorPrimary: delightfulColorUsages.primary.default,
									colorPrimaryHover: delightfulColorUsages.primary.hover,
									colorPrimaryActive: delightfulColorUsages.primary.active,
									textHoverBg:
										appearence === "dark"
											? baseColorScales.grey[8]
											: delightfulColorUsages.primaryLight.default,
								},
							},
					  })
					: undefined
			}, [
				delightfulColorUsages.primary.active,
				delightfulColorUsages.primary.default,
				delightfulColorUsages.primary.hover,
				delightfulColorUsages.primaryLight.default,
				theme,
			])

			if (hidden) {
				return null
			}

			return (
				<ThemeProvider theme={themeConfigs}>
					<Tooltip title={tip}>
						<Button
							ref={ref}
							className={cx(styles.delightfulButton, className)}
							{...props}
						/>
					</Tooltip>
				</ThemeProvider>
			)
		},
	),
)

export default DelightfulButton

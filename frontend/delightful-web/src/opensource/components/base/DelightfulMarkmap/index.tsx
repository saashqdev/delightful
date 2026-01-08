import type { HTMLAttributes } from "react"
import { useRef, memo, useState } from "react"
import "markmap-toolbar/dist/style.css"
import { Flex, Switch, Typography } from "antd"
import { useTranslation } from "react-i18next"
import { useDebounceEffect, useMemoizedFn, useSize, useUpdateEffect } from "ahooks"
import { IconMaximize, IconPhotoDown } from "@tabler/icons-react"
import { downloadFile } from "@/utils/file"
import type { IMarkmapOptions } from "markmap-common"
import Divider from "@/opensource/components/other/Divider"
import DelightfulButton from "../DelightfulButton"
import DelightfulIcon from "../DelightfulIcon"
import ExportPPTButton from "./components/ExportPPTButton"
import { useStyles } from "./styles"
import { exportMarkmapToPng } from "./utils"
import type { MarkmapBaseRef } from "./components/MarkmapBase"
import MarkmapBase from "./components/MarkmapBase"
import DelightfulModal from "../DelightfulModal"

interface DelightfulMarkmapProps extends HTMLAttributes<SVGSVGElement> {
	showToolBar?: boolean
	showTitle?: boolean
	pptData?: string | null
	data: string
	fullScreen?: boolean
	exitFullScreen?: () => void
}

const defaultOptions = (enable: boolean): Partial<IMarkmapOptions> => ({
	autoFit: true,
	pan: enable,
	zoom: enable,
})

const DelightfulMarkmap = memo(
	({
		data,
		pptData,
		className,
		showTitle = true,
		showToolBar = true,
		fullScreen = false,
		exitFullScreen,
	}: DelightfulMarkmapProps) => {
		const { styles, cx } = useStyles()
		const { t } = useTranslation("interface")

		const instanceRef = useRef<MarkmapBaseRef | null>(null)
		const containerRef = useRef<HTMLDivElement>(null)
		const [options] = useState<Partial<IMarkmapOptions>>(defaultOptions(fullScreen))
		const [zoomSwitch, setZoomSwitch] = useState(fullScreen)

		useUpdateEffect(() => {
			instanceRef.current?.instance.current?.setOptions(defaultOptions(zoomSwitch))
		}, [zoomSwitch])

		const size = useSize(containerRef.current)

		useDebounceEffect(
			() => {
				if (size && size.width && size.height) {
					instanceRef.current?.instance.current?.fit()
				}
			},
			[size?.width, size?.height],
			{ wait: 500 },
		)

		const [fullScreenOpen, setFullScreenOpen] = useState(false)
		/** Fullscreen logic */
		const handleFullScreen = useMemoizedFn(() => {
			if (fullScreen) {
				exitFullScreen?.()
			} else {
				setFullScreenOpen(true)
			}
		})

		/** Download image */
		const handleDownloadPicture = useMemoizedFn(() => {
			exportMarkmapToPng(data, 1920, 1080).then((blob) => {
				const url = URL.createObjectURL(blob)
				downloadFile(url, "markmap.png")
				URL.revokeObjectURL(url)
			})
		})

		return (
			<div ref={containerRef} className={cx(styles.container, className)}>
				{showToolBar && (
					<Flex
						align="center"
						justify="space-between"
						className={cx(styles.toolbarContainer)}
						gap={4}
					>
						<div className={styles.mindmapTitle}>
							{showTitle ? t("chat.aggregate_ai_search_card.mind_map") : null}
						</div>
						<DelightfulButton
							className={styles.button}
							type="text"
							onClick={handleFullScreen}
							icon={
								<DelightfulIcon
									color="currentColor"
									component={IconMaximize}
									size={20}
								/>
							}
						>
							{fullScreen
								? t("chat.markmap.exitFullScreen")
								: t("chat.markmap.fullScreen")}
						</DelightfulButton>
						<DelightfulModal
							centered
							width="90vw"
							classNames={{
								body: styles.fullScreenContainer,
							}}
							open={fullScreenOpen}
							title={t("chat.aggregate_ai_search_card.mind_map")}
							onCancel={() => setFullScreenOpen(false)}
							footer={null}
						>
							<DelightfulMarkmap
								data={data}
								pptData={pptData}
								fullScreen
								showTitle={false}
								exitFullScreen={() => setFullScreenOpen(false)}
							/>
						</DelightfulModal>
						<DelightfulButton
							className={styles.button}
							type="text"
							icon={
								<DelightfulIcon
									color="currentColor"
									component={IconPhotoDown}
									size={20}
								/>
							}
							onClick={handleDownloadPicture}
						>
							{t("chat.markmap.downloadPicture")}
						</DelightfulButton>
						{!fullScreen ? (
							<>
								<Divider direction="vertical" />
								<Flex
									align="center"
									gap={4}
									style={{ padding: "0 4px", flexShrink: 0 }}
								>
									<Typography.Text className={styles.toolbarText}>
										{t("chat.markmap.enableInteraction")}
									</Typography.Text>
									<Switch
										size="small"
										checked={zoomSwitch}
										onChange={setZoomSwitch}
									/>
								</Flex>
							</>
						) : null}
						<Divider direction="vertical" />
						<ExportPPTButton content={pptData ?? data} />
					</Flex>
				)}
				<MarkmapBase ref={instanceRef} options={options} data={data} />
			</div>
		)
	},
)

export default DelightfulMarkmap

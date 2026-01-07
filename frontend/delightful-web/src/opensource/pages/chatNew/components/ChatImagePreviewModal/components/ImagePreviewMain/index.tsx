import DelightfulImagePreview from "@/opensource/components/base/DelightfulImagePreview"
import { resolveToString } from "@delightful/es6-template-strings"
import { Flex, Progress } from "antd"
import useStyles from "../../styles"
import ImageCompareSlider from "../ImageCompareSlider"
import MessageImagePreview from "@/opensource/services/chat/message/MessageImagePreview"
import { ImagePreviewInfo } from "@/types/chat/preview"
import useCurrentImageSwitcher from "@/opensource/components/base/DelightfulImagePreview/hooks/useCurrentImageSwitcher"
import useImageAction from "../../hooks/useImageAction"
import { memo, useMemo, useRef } from "react"
import useImageSize from "@/opensource/components/base/DelightfulImagePreview/hooks/useImageSize"
import { useTranslation } from "react-i18next"
import { isEqual } from "lodash-es"
import DelightfulEmpty from "@/opensource/components/base/DelightfulEmpty"
import { useMemoizedFn } from "ahooks"
import DelightfulDropdown from "@/opensource/components/base/DelightfulDropdown"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconCopy } from "@tabler/icons-react"

interface ImagePreviewMainProps {
	info: ImagePreviewInfo | undefined
	loading: boolean
	progress: number
	containerClassName?: string
}

const ImagePreviewMain = memo(function ImagePreviewMain({
	info,
	containerClassName,
}: ImagePreviewMainProps) {
	const { styles } = useStyles()
	const { t } = useTranslation("interface")

	const imageRef = useRef<HTMLImageElement>(null)
	const isLongImage = useImageSize(info?.url)

	const { toNext, toPrev, nextDisabled, prevDisabled } = useCurrentImageSwitcher()
	const {
		currentImage,
		loading,
		progress,
		isCompare,
		isPressing,
		viewType,
		setViewType,
		onLongPressStart,
		onLongPressEnd,
	} = useImageAction(info)

	const ImageNode = useMemo(() => {
		switch (info?.ext?.ext) {
			case "svg":
			case "svg+xml":
				return (
					currentImage && (
						<div
							draggable={false}
							dangerouslySetInnerHTML={{ __html: currentImage }}
							className={styles.svg}
						/>
					)
				)
			default:
				return (
					<img
						ref={imageRef}
						src={currentImage}
						alt=""
						draggable={false}
						style={
							isLongImage
								? {
										objectFit: "contain",
										width: "100%",
								  }
								: {
										objectFit: "contain",
										width: "100%",
										height: "100%",
								  }
						}
					/>
				)
		}
	}, [info?.ext?.ext, currentImage, styles.svg, isLongImage])

	const getContextMenuItems = useMemoizedFn(() => {
		return [
			{
				key: "download",
				label: (
					<Flex align="center" gap={4}>
						<DelightfulIcon component={IconCopy} size={16} color="currentColor" />
						{t("chat.imagePreview.copy")}
					</Flex>
				),
				onClick: () => {
					if (imageRef.current) {
						MessageImagePreview.copy(imageRef.current)
					}
				},
			},
		]
	})

	if (!currentImage)
		return (
			<Flex justify="center" align="center" className={styles.imagePreview}>
				<DelightfulEmpty description={t("chat.NoContent", { ns: "message" })} />
			</Flex>
		)

	return (
		<DelightfulDropdown
			trigger={["contextMenu"]}
			menu={{
				items: getContextMenuItems(),
			}}
		>
			<div className={containerClassName}>
				<DelightfulImagePreview
					rootClassName={styles.imagePreview}
					onNext={info?.standalone ? undefined : toNext}
					onPrev={info?.standalone ? undefined : toPrev}
					nextDisabled={nextDisabled}
					prevDisabled={prevDisabled}
					hasCompare={isCompare}
					viewType={viewType}
					onChangeViewType={setViewType}
					onLongPressStart={onLongPressStart}
					onLongPressEnd={onLongPressEnd}
				>
					{isCompare ? (
						<ImageCompareSlider
							info={info}
							viewType={viewType}
							isPressing={isPressing}
						/>
					) : (
						ImageNode
					)}
				</DelightfulImagePreview>
				{loading && (
					<Flex align="center" gap={10} className={styles.mask}>
						<Progress percent={progress} showInfo={false} className={styles.progress} />
						<Flex
							vertical
							gap={2}
							align="center"
							justify="center"
							className={styles.progressText}
						>
							<span>
								{resolveToString(t("chat.imagePreview.hightImageConverting"), {
									num: progress,
								})}
							</span>
							<span>{t("chat.imagePreview.convertingCloseTip")}</span>
						</Flex>
					</Flex>
				)}
			</div>
		</DelightfulDropdown>
	)
},
isEqual)

export default ImagePreviewMain

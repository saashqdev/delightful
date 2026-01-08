import { Flex, Slider } from "antd"
import type { HTMLAttributes } from "react"
import { memo, useEffect, useMemo, useRef } from "react"
import {
	IconChevronLeft,
	IconChevronRight,
	IconColumns2,
	IconRelationOneToOne,
	IconRotateRectangle,
	IconSquares,
	IconSquareToggle,
	IconZoomIn,
	IconZoomOut,
} from "@tabler/icons-react"
import { useMemoizedFn, useUpdateEffect } from "ahooks"
import { useTranslation } from "react-i18next"
import DelightfulSegmented from "@/opensource/components/base/DelightfulSegmented"
import DelightfulButton from "../DelightfulButton"
import DelightfulIcon from "../DelightfulIcon"
import useScale from "./hooks/useScale"
import useRotate from "./hooks/useRotate"
import useOffset from "./hooks/useOffset"
import useStyles from "./styles"
import { CompareViewType } from "./constants"

const MAX_SCALE = 5
const SCALE_STEP = 0.1

interface Props extends HTMLAttributes<HTMLImageElement> {
	src?: string
	onNext?: () => void
	onPrev?: () => void
	nextDisabled?: boolean
	prevDisabled?: boolean
	rootClassName?: string
	hasCompare?: boolean
	viewType?: CompareViewType
	onChangeViewType?: (type: CompareViewType) => void
	onLongPressStart?: () => void
	onLongPressEnd?: () => void
}

/**
 * Image preview component
 */
const DelightfulImagePreview = memo((props: Props) => {
	const {
		hasCompare,
		viewType,
		onChangeViewType,
		onLongPressStart,
		onLongPressEnd,
		onNext,
		onPrev,
		nextDisabled,
		prevDisabled,
		children,
		rootClassName,
		className,
		...rest
	} = props
	const { styles, cx } = useStyles()
	const { t } = useTranslation("interface")

	const containerRef = useRef<HTMLDivElement>(null)

	const { scale, addTenPercent, subTenPercent, setScale } = useScale(containerRef, {
		step: SCALE_STEP,
		maxScale: MAX_SCALE,
	})
	const { rotate, rotateImage } = useRotate()

	const scaleRef = useRef(1)
	useEffect(() => {
		scaleRef.current = scale
	}, [scale])

	const { offset, setOffset } = useOffset(containerRef, scaleRef)

	// Reset image
	const resetImage = useMemoizedFn(() => {
		setOffset({
			x: 0,
			y: 0,
		})
		setScale(1)
	})

	/** Reset image position when switching images */
	useUpdateEffect(() => {
		// If in compare mode, do not reset image
		if (hasCompare) return
		resetImage()
	}, [children])

	const segmentedOptions = useMemo(() => {
		return [
			{
				value: CompareViewType.PULL,
				icon: <IconColumns2 size={18} />,
			},
			{
				value: CompareViewType.LONG_PRESS,
				icon: <IconSquares size={18} />,
			},
		]
	}, [])

	return (
		<div className={cx(styles.container, rootClassName)}>
			<div ref={containerRef} className={styles.imageDragWrapper}>
				<div
					className={cx(styles.imageWrapper, className)}
					draggable={false}
					style={{
						transform: `scale(${scale}) rotate(${rotate}deg) translate3d(${offset.x}px, ${offset.y}px, 0)`,
					}}
					{...rest}
				>
					{children}
				</div>
			</div>
			<Flex className={styles.toolContainer} align="center" gap={12}>
				{onPrev && (
					<DelightfulButton
						type="link"
						className={styles.toolButton}
						onClick={onPrev}
						disabled={prevDisabled}
					>
						<DelightfulIcon color="currentColor" component={IconChevronLeft} size={24} />
					</DelightfulButton>
				)}
				{onNext && (
					<DelightfulButton
						type="link"
						className={styles.toolButton}
						onClick={onNext}
						disabled={nextDisabled}
					>
						<DelightfulIcon color="currentColor" component={IconChevronRight} size={24} />
					</DelightfulButton>
				)}
				{(onPrev || onNext) && <div className={styles.divider} />}
				<Flex gap={8} align="center">
					<DelightfulButton type="link" className={styles.toolButton} onClick={subTenPercent}>
						<DelightfulIcon color="currentColor" component={IconZoomOut} size={24} />
					</DelightfulButton>
					<Slider
						className={styles.slider}
						min={0.1}
						max={MAX_SCALE}
						defaultValue={1}
						value={scale}
						step={SCALE_STEP}
						tooltip={{
							open: false,
						}}
						onChange={setScale}
					/>
					<span className={styles.sliderText}>{Math.round(scale * 100)}%</span>
					<DelightfulButton type="link" className={styles.toolButton} onClick={addTenPercent}>
						<DelightfulIcon color="currentColor" component={IconZoomIn} size={24} />
					</DelightfulButton>
				</Flex>
				{/* 1:1 */}
				<DelightfulButton type="link" className={styles.toolButton} onClick={resetImage}>
					<DelightfulIcon color="currentColor" component={IconRelationOneToOne} size={24} />
				</DelightfulButton>
			{/* Rotate */}
				<DelightfulButton type="link" className={styles.toolButton} onClick={rotateImage}>
					<DelightfulIcon color="currentColor" component={IconRotateRectangle} size={24} />
				</DelightfulButton>
				{hasCompare && (
					<>
						<div className={styles.divider} />
						<DelightfulSegmented
							options={segmentedOptions}
							className={styles.segmented}
							value={viewType}
							onChange={onChangeViewType}
						/>
					</>
				)}
			</Flex>
			{hasCompare && viewType === CompareViewType.LONG_PRESS && (
				<DelightfulButton
					type="default"
					className={styles.longPressButton}
					onPointerDown={onLongPressStart}
					onPointerUp={onLongPressEnd}
					onPointerLeave={onLongPressEnd}
				>
					<IconSquareToggle color="currentColor" size={18} />
					{t("chat.imagePreview.longPressCompare")}
				</DelightfulButton>
			)}
		</div>
	)
})

export default DelightfulImagePreview

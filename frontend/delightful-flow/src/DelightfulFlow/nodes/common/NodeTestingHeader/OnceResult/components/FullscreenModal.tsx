import { NodeTestConfig } from "@/DelightfulFlow/context/NodeTesingContext/Context"
import { IconCheck, IconCopy } from "@tabler/icons-react"
import clsx from "clsx"
import i18next from "i18next"
import React, { useEffect, useRef, useState } from "react"
import { createPortal } from "react-dom"
import styles from "../../index.module.less"
import { TestingResultRow } from "../../useTesting"
import { formatValue, isComplexValue } from "../utils"
import { MiniMap } from "./MiniMap"

// Fullscreen modal component
export const FullscreenModal = ({
	visible,
	onClose,
	inputList,
	outputList,
	testingResult,
	debugLogs,
	allowDebug,
}: {
	visible: boolean
	onClose: () => void
	inputList: TestingResultRow[]
	outputList: TestingResultRow[]
	testingResult?: NodeTestConfig
	debugLogs?: TestingResultRow[]
	allowDebug: boolean
}) => {
	// Create a reference for minimap navigation
	const contentRef = useRef<HTMLDivElement>(null)

	// Track copy status
	const [copyStates, setCopyStates] = useState({
		input: false,
		output: false,
		debug: false,
	})

	// Prevent scroll penetration
	useEffect(() => {
		if (visible) {
			document.body.style.overflow = "hidden"
		} else {
			document.body.style.overflow = ""
		}
		return () => {
			document.body.style.overflow = ""
		}
	}, [visible])

	// Copy functionality
	const handleCopy = (value: any, key: "input" | "output" | "debug") => {
		if (value) {
			const textToCopy =
				typeof value === "object" ? JSON.stringify(value, null, 2) : value.toString()
			navigator.clipboard.writeText(textToCopy)

			// Set corresponding copy state to success
			setCopyStates((prev) => ({
				...prev,
				[key]: true,
			}))

			// Reset state after 2 seconds
			setTimeout(() => {
				setCopyStates((prev) => ({
					...prev,
					[key]: false,
				}))
			}, 2000)
		}
	}

	if (!visible) return null

	return createPortal(
		<div className={styles.fullscreenModalPortal}>
			<div className={styles.fullscreenModalContent}>
				<div className={styles.fullscreenModalHeader}>
					<h3>
						{i18next.t("flow.testDetail", {
							ns: "delightfulFlow",
							defaultValue: "Test Details",
						})}
					</h3>
					<button onClick={onClose}>×</button>
				</div>
				<div className={styles.fullscreenContentWrapper}>
					<div className={styles.fullscreenModalBody} ref={contentRef}>
						{/* Input section */}
						<div className={styles.fullscreenSection}>
							<div className={styles.fullscreenSectionHeader}>
								<h4>{i18next.t("common.input", { ns: "delightfulFlow" })}</h4>
								<div className={styles.copyContainer}>
									<IconCopy
										className={styles.fullscreenCopyIcon}
										size={24}
										color="#1C1D2399"
										onClick={() => handleCopy(testingResult?.input, "input")}
										style={{ cursor: "pointer" }}
									/>
									{copyStates.input && (
										<IconCheck
											size={20}
											color="#52c41a"
											className={styles.checkIcon}
										/>
									)}
								</div>
							</div>
							<div className={styles.fullscreenSectionContent}>
								{inputList?.map?.((item, index) => {
									const isComplex = isComplexValue(item.value)
									const itemKey = `input-${item.key}-${index}`
									return (
										<div
											className={styles.fullscreenItem}
											key={index}
											data-item-key={itemKey}
										>
											<span className={styles.fullscreenKey}>{item.key}</span>
											<span className={styles.fullscreenSplitor}>:</span>
											<span
												className={clsx(styles.fullscreenValue, {
													[styles.fullscreenComplex]: isComplex,
												})}
											>
												{isComplex ? formatValue(item.value) : item.value}
											</span>
										</div>
									)
								})}
							</div>
						</div>

						{/* Output section */}
						<div className={styles.fullscreenSection}>
							<div className={styles.fullscreenSectionHeader}>
								<h4>{i18next.t("common.output", { ns: "delightfulFlow" })}</h4>
								<div className={styles.copyContainer}>
									<IconCopy
										className={styles.fullscreenCopyIcon}
										size={24}
										color="#1C1D2399"
										onClick={() => handleCopy(testingResult?.output, "output")}
										style={{ cursor: "pointer" }}
									/>
									{copyStates.output && (
										<IconCheck
											size={20}
											color="#52c41a"
											className={styles.checkIcon}
										/>
									)}
								</div>
							</div>
							<div className={styles.fullscreenSectionContent}>
								{outputList?.map?.((item, index) => {
									const isComplex = isComplexValue(item.value)
									const itemKey = `output-${item.key}-${index}`
									return (
										<div
											className={styles.fullscreenItem}
											key={index}
											data-item-key={itemKey}
										>
											<span className={styles.fullscreenKey}>{item.key}</span>
											<span className={styles.fullscreenSplitor}>:</span>
											<span
												className={clsx(styles.fullscreenValue, {
													[styles.fullscreenError]:
														!testingResult?.success,
													[styles.fullscreenComplex]: isComplex,
												})}
											>
												{isComplex ? formatValue(item.value) : item.value}
											</span>
										</div>
									)
								})}
							</div>
						</div>

						{/* Debug log section */}
						{allowDebug && debugLogs && debugLogs.length > 0 && (
							<div className={styles.fullscreenSection}>
								<div className={styles.fullscreenSectionHeader}>
									<h4>{i18next.t("flow.testDetail", { ns: "delightfulFlow" })}</h4>
									<div className={styles.copyContainer}>
										<IconCopy
											className={styles.fullscreenCopyIcon}
											size={18}
											color="#1C1D2399"
											onClick={() =>
												handleCopy(testingResult?.debug_log, "debug")
											}
											style={{ cursor: "pointer" }}
										/>
										{copyStates.debug && (
											<IconCheck
												size={18}
												color="#52c41a"
												className={styles.checkIcon}
											/>
										)}
									</div>
								</div>
								<div className={styles.fullscreenSectionContent}>
									{debugLogs?.map?.((item, index) => {
										const isComplex = isComplexValue(item.value)
										const itemKey = `debug-${item.key}-${index}`
										return (
											<div
												className={styles.fullscreenItem}
												key={index}
												data-item-key={itemKey}
											>
												<span className={styles.fullscreenKey}>
													{item.key}
												</span>
												<span className={styles.fullscreenSplitor}>:</span>
												<span
													className={clsx(styles.fullscreenValue, {
														[styles.fullscreenComplex]: isComplex,
													})}
												>
													{isComplex
														? formatValue(item.value)
														: item.value}
												</span>
											</div>
										)
									})}
								</div>
							</div>
						)}
					</div>

					{/* Add mini-map navigation */}
					<MiniMap
						contentRef={contentRef}
						inputList={inputList}
						outputList={outputList}
						debugLogs={debugLogs}
						allowDebug={allowDebug}
						testingResult={testingResult}
					/>
				</div>
			</div>
		</div>,
		document.body,
	)
}


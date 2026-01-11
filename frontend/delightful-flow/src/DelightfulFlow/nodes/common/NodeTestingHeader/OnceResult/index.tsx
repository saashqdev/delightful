import { useExternalConfig } from "@/DelightfulFlow/context/ExternalContext/useExternal"
import { NodeTestConfig } from "@/DelightfulFlow/context/NodeTesingContext/Context"
import { IconCheck, IconCopy, IconMaximize } from "@tabler/icons-react"
import clsx from "clsx"
import i18next from "i18next"
import React, { useState } from "react"
import { useTranslation } from "react-i18next"
import styles from "../index.module.less"
import { TestingResultRow } from "../useTesting"
import { FullscreenModal } from "./components/FullscreenModal"
import { formatValue, isComplexValue } from "./utils"

type OnceResultProps = {
	inputList: TestingResultRow[]
	outputList: TestingResultRow[]
	testingResult?: NodeTestConfig
	debugLogs?: TestingResultRow[]
}

export default function OnceResult({
	inputList,
	outputList,
	testingResult,
	debugLogs,
}: OnceResultProps) {
	const { t } = useTranslation()
	const { allowDebug } = useExternalConfig()

	// Add fullscreen modal state
	const [fullscreenModalVisible, setFullscreenModalVisible] = React.useState(false)

	// Track copy states
	const [copyStates, setCopyStates] = useState({
		input: false,
		output: false,
		debug: false,
	})

	// Function to open fullscreen modal
	const openFullscreenModal = () => {
		setFullscreenModalVisible(true)
	}

	// Close fullscreen modal
	const closeFullscreenModal = () => {
		setFullscreenModalVisible(false)
	}

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

	return (
		<>
			<div className={styles.input}>
				<div className={styles.header}>
					<span className={styles.inputTitle}>
						{i18next.t("common.input", { ns: "delightfulFlow" })}
					</span>
					<div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
						<div className={styles.copyContainer}>
							<IconCopy
								color="#1C1D2399"
								size={12}
								onClick={() => handleCopy(testingResult?.input!, "input")}
								style={{ cursor: "pointer" }}
							/>
							{copyStates.input && (
								<IconCheck size={12} color="#52c41a" className={styles.checkIcon} />
							)}
						</div>
						{/* Fullscreen view button placed next to copy button */}
						<button
							className={styles.fullscreenButton}
							onClick={openFullscreenModal}
							style={{
								padding: "2px 6px",
								height: "22px",
								fontSize: "12px",
								display: "flex",
								alignItems: "center",
								gap: "4px",
							}}
						>
							<IconMaximize size={12} />
							{i18next.t("flow.fullscreen", {
								ns: "delightfulFlow",
							defaultValue: "Fullscreen View",
							})}
						</button>
					</div>
				</div>
				<div className={clsx(styles.body, "nodrag")}>
					{inputList?.map?.((item, index) => {
						const isComplex = isComplexValue(item.value)
						return (
							<div className={styles.bodyItem} key={index}>
								<span className={styles.key}>{item.key}</span>
								<span className={styles.splitor}>:</span>
								<span
									className={clsx(styles.value, {
										[styles.complexValue]: isComplex,
									})}
								>
									{isComplex ? formatValue(item.value) : item.value}
								</span>
							</div>
						)
					})}
				</div>
			</div>
			<div className={clsx(styles.output, "nodrag")}>
				<div className={styles.header}>
					<span className={styles.inputTitle}>
						{i18next.t("common.output", { ns: "delightfulFlow" })}
					</span>
					<div className={styles.copyContainer}>
						<IconCopy
							color="#1C1D2399"
							size={16}
							onClick={() => handleCopy(testingResult?.output!, "output")}
							style={{ cursor: "pointer" }}
						/>
						{copyStates.output && (
							<IconCheck size={16} color="#52c41a" className={styles.checkIcon} />
						)}
					</div>
				</div>
				<div className={styles.body}>
					{outputList?.map?.((item, index) => {
						const isComplex = isComplexValue(item.value)
						return (
							<div className={styles.bodyItem} key={index}>
								<span className={styles.key}>{item.key}</span>
								<span className={styles.splitor}>:</span>
								<span
									className={clsx(styles.value, {
										[styles.error]: !testingResult?.success,
										[styles.complexValue]: isComplex,
									})}
								>
									{isComplex ? formatValue(item.value) : item.value}
								</span>
							</div>
						)
					})}
				</div>
			</div>
			{allowDebug && (
				<div className={styles.output}>
					<div className={styles.header}>
						<span className={styles.inputTitle}>
							{i18next.t("flow.testDetail", { ns: "delightfulFlow" })}
						</span>
						<div className={styles.copyContainer}>
							<IconCopy
								color="#1C1D2399"
								size={16}
								onClick={() => handleCopy(testingResult?.debug_log!, "debug")}
								style={{ cursor: "pointer" }}
							/>
							{copyStates.debug && (
								<IconCheck size={16} color="#52c41a" className={styles.checkIcon} />
							)}
						</div>
					</div>
					<div className={styles.body}>
						{debugLogs?.map?.((item, index) => {
							const isComplex = isComplexValue(item.value)
							return (
								<div className={styles.bodyItem} key={index}>
									<span className={styles.key}>{item.key}</span>
									<span className={styles.splitor}>:</span>
									<span
										className={clsx(styles.value, {
											[styles.complexValue]: isComplex,
										})}
									>
										{isComplex ? formatValue(item.value) : item.value}
									</span>
								</div>
							)
						})}
					</div>
				</div>
			)}

			{/* Use new Portal version of fullscreen modal window */}
			<FullscreenModal
				visible={fullscreenModalVisible}
				onClose={closeFullscreenModal}
				inputList={inputList}
				outputList={outputList}
				testingResult={testingResult}
				debugLogs={debugLogs}
				allowDebug={allowDebug}
			/>
		</>
	)
}


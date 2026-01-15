import DelightfulFileIcon from "@/opensource/components/base/DelightfulFileIcon"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import UploadAction from "@/opensource/components/base/UploadAction"
import { useUpload } from "@/opensource/hooks/useUploadFiles"
import { formatFileSize } from "@/utils/string"
import { IconFileUpload, IconSend, IconX, IconSchool } from "@tabler/icons-react"
import { useDebounceFn, useMemoizedFn } from "ahooks"
import TaskIcon from "@/opensource/pages/beDelightful/assets/svg/task_mode.svg"
import { Button, Input, message, Tooltip } from "antd"
import { cx } from "antd-style"
import type { TextAreaRef } from "antd/es/input/TextArea"
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react"
import { useTranslation } from "react-i18next"
import InterruptSvg from "../../assets/svg/interrupt.svg"
import type { FileItem } from "../../pages/Workspace/types"
import TaskList from "../TaskList"
import { useStyles } from "./styles"
import { FileData } from "@/opensource/pages/chatNew/components/MessageEditor/components/InputFiles/types"
import { genFileData } from "@/opensource/pages/chatNew/components/MessageEditor/components/InputFiles/utils"

export interface MessagePanelProps {
	onSendMessage?: (content: string, options?: any) => void
	fileList?: FileItem[]
	setFileList?: (fileList: any[]) => void
	taskData?: any
	className?: string
	textAreaWrapperClassName?: string
	containerRef?: React.RefObject<HTMLDivElement>
	showLoading?: boolean
	selectedThreadInfo?: any
	isEmptyStatus?: boolean
	topicModeInfo?: string
}

const MessagePanel: React.FC<MessagePanelProps> = ({
	onSendMessage,
	fileList,
	setFileList,
	taskData,
	className,
	textAreaWrapperClassName,
	containerRef,
	showLoading,
	selectedThreadInfo,
	isEmptyStatus,
	topicModeInfo,
}) => {
	const { styles } = useStyles()
	const textAreaRef = useRef<TextAreaRef>(null)
	const [inputValue, setInputValue] = React.useState("")
	const [composition, setComposition] = React.useState(false)
	const [inputMode, setInputMode] = useState<"chat" | "plan">("plan")
	const [uploading, setUploading] = useState(false)
	const { t } = useTranslation("interface")
	const { TextArea } = Input

	const { upload, reportFiles } = useUpload<FileData>({
		storageType: "private",
	})

	// File upload
	const onFileChange = useMemoizedFn(async (files: FileList) => {
		try {
			setUploading(true)
			message.loading({ content: "Uploading file...", key: "fileUpload" })
			const newFiles = Array.from(files).map(genFileData)
			const { fullfilled } = await upload(newFiles)

			// Check if all files uploaded successfully
			if (fullfilled.length !== newFiles.length) {
				message.error({ content: t("file.uploadFail"), key: "fileUpload" })
				return null
			}
			const data = fullfilled.map(({ value }) => ({
				file_key: value.key,
				file_name: value.name,
				file_size: value.size,
				file_extension: value?.name?.split(".").pop(),
			}))
			const res = await reportFiles(data)
			// Merge newly uploaded files with existing file list
			setFileList?.([...(fileList || []), ...res])
			message.success({ content: "File uploaded successfully", key: "fileUpload" })
			return null
		} catch (error) {
			message.error({ content: "File upload failed", key: "fileUpload" })
			return null
		} finally {
			setUploading(false)
		}
	})
	const handleSend = useCallback(() => {
		if (inputValue.trim()) {
			const data = isEmptyStatus
				? {
						instructs: [{ value: inputMode }],
				  }
				: {}
			onSendMessage?.(inputValue, data)
			setInputValue("")
			setFileList?.([])
		}
	}, [inputValue, inputMode, onSendMessage, setFileList, isEmptyStatus])

	const onPressEnter = (event: React.KeyboardEvent<HTMLTextAreaElement>) => {
		if (composition) {
			return
		}
		const { metaKey, shiftKey, ctrlKey } = event
		event.preventDefault()
		if (metaKey || ctrlKey || shiftKey) {
			const textArea = textAreaRef.current?.resizableTextArea?.textArea
			const cursorPosition = textArea?.selectionStart || 0
			setInputValue((oldState) => {
				const oldMessage = oldState || ""
				return `${oldMessage.slice(0, cursorPosition)}\n${oldMessage.slice(cursorPosition)}`
			})
			setTimeout(() => {
				textArea?.setSelectionRange(cursorPosition + 1, cursorPosition + 1, "none")
				if (textArea?.value.length === cursorPosition + 1) {
					textArea?.scrollBy({
						behavior: "smooth",
						top: Number.MAX_SAFE_INTEGER,
					})
				}
			})
		}
		if (!metaKey && !shiftKey && !ctrlKey) {
			handleSend?.()
		}
	}

	const handleInput = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
		setInputValue(e.target.value)
	}

	const handleFileChange = useCallback(
		(files: FileList) => {
			if (uploading) {
				message.info("File is uploading, please wait")
				return
			}
			console.log("Selected files:", files, "fileList", fileList)
			onFileChange(files)
		},
		[onFileChange, fileList, uploading],
	)

	const handleRemoveFile = (key: string | undefined) => {
		const updatedFileList = fileList?.filter((file: any) => file.file_key !== key)
		setFileList?.(updatedFileList || [])
	}

	useEffect(() => {
		setInputValue("")
		setFileList?.([])
	}, [selectedThreadInfo])

	const { run: handleInterrupt } = useDebounceFn(
		() => {
			onSendMessage?.("Terminate task", {
				instructs: [{ value: "interrupt" }],
			})
			console.log("Interrupt")
		},
		{ wait: 3000, leading: true, trailing: false },
	)

	const sendButtonDisabled = useMemo(() => {
		if (inputValue.trim()?.length && selectedThreadInfo?.id) {
			return false
		}
		return true
	}, [inputValue, selectedThreadInfo])

	const getFileType = (fileName: string) => {
		const fileType = fileName.split(".").pop()
		return fileType
	}

	return (
		<div ref={containerRef} className={cx(styles.container, className)}>
			<div className={styles.inputArea}>
				{taskData?.process?.length > 0 && (
					<div className={styles.taskListWrapper}>
						<TaskList taskData={taskData} isInChat />
					</div>
				)}
				<div className={styles.inputGroup}>
					{!!fileList && fileList.length > 0 && (
						<div className={styles.fileList}>
							{fileList.map((file: FileItem) => (
								<div key={file.file_id} className={styles.fileItem}>
									<Tooltip
										title={file.file_name}
										placement="top"
										mouseEnterDelay={0.5}
									>
										<div className={styles.fileItemContent}>
											<DelightfulFileIcon
												type={getFileType(file?.file_name || "")}
												size={14}
											/>
											<span className={styles.fileName}>
												{file.file_name}
											</span>
											<span className={styles.fileSize}>
												{formatFileSize(file.file_size)}
											</span>
										</div>
									</Tooltip>
									<div
										className={styles.deleteIcon}
										onClick={() => handleRemoveFile(file.file_key)}
									>
										<DelightfulIcon
											component={IconX}
											size={10}
											stroke={2}
											style={{ stroke: "white" }}
										/>
									</div>
								</div>
							))}
						</div>
					)}

					<div className={cx(styles.textAreaWrapper, textAreaWrapperClassName)}>
						<TextArea
							ref={textAreaRef}
							autoSize={false}
							value={inputValue}
							onChange={handleInput}
							onPressEnter={onPressEnter}
							className={styles.textArea}
							style={{ resize: "none" }}
							placeholder={
								showLoading
									? "You can continue talking to me to adjust the task in real time"
									: "Give Delightful a task..."
							}
							onCompositionStart={() => setComposition(true)}
							onCompositionEnd={() => setComposition(false)}
						/>
					</div>

					<div className={styles.toolBar}>
						<div className={styles.toolBarLeft}>
							{isEmptyStatus && (
								<div className={styles.modeToggle}>
									{isEmptyStatus ? (
										<>
											<Tooltip
												title="AI autonomously plans and executes tasks step by step"
												placement="top"
											>
												<div
													className={cx(
														styles.modeToggleButton,
														inputMode === "plan" &&
															styles.modeToggleButtonActive,
													)}
													onClick={() => setInputMode("plan")}
												>
													<img
														src={TaskIcon}
														alt=""
														className={styles.taskIcon}
													/>
													Task
												</div>
											</Tooltip>
											<Tooltip
												title="AI executes step by step, collaborating with humans through multi-turn dialogue"
												placement="top"
											>
												<div
													className={cx(
														styles.modeToggleButton,
														inputMode === "chat" &&
															styles.modeToggleButtonActive,
													)}
													onClick={() => setInputMode("chat")}
												>
													<DelightfulIcon
														component={IconSchool}
														size={16}
													/>
													{"Professional"}
												</div>
											</Tooltip>
										</>
									) : (
										<>
											{topicModeInfo === "plan" && (
												<div
													className={cx(
														styles.modeToggleButton,
														styles.modeToggleButtonActive,
													)}
												>
													<img
														src={TaskIcon}
														alt=""
														className={styles.taskIcon}
													/>
													Task
												</div>
											)}
											{topicModeInfo === "chat" && (
												<div
													className={cx(
														styles.modeToggleButton,
														styles.modeToggleButtonActive,
													)}
												>
													<DelightfulIcon
														component={IconSchool}
														size={16}
													/>
													{"Professional"}
												</div>
											)}
										</>
									)}
								</div>
							)}
							{!isEmptyStatus && topicModeInfo && (
								<div className={styles.modeToggle}>
									{topicModeInfo === "plan" && (
										<Tooltip
											title="AI autonomously plans and executes tasks step by step"
											placement="top"
										>
											<div
												className={cx(
													styles.modeToggleButton,
													styles.modeToggleButtonActive,
												)}
											>
												<img
													src={TaskIcon}
													alt=""
													className={styles.taskIcon}
												/>
												Task
											</div>
										</Tooltip>
									)}
									{topicModeInfo === "chat" && (
										<Tooltip
											title="AI executes step by step, collaborating with humans through multi-turn dialogue"
											placement="top"
										>
											<div
												className={cx(
													styles.modeToggleButton,
													styles.modeToggleButtonActive,
												)}
											>
												<DelightfulIcon component={IconSchool} size={16} />
												{"Professional"}
											</div>
										</Tooltip>
									)}
								</div>
							)}
							<UploadAction
								multiple
								onFileChange={handleFileChange}
								handler={(trigger) => (
									<Button
										className={styles.toolBarButton}
										onClick={trigger}
										icon={
											<DelightfulIcon component={IconFileUpload} size={20} />
										}
										type="text"
									>
										File
									</Button>
								)}
							/>
						</div>
						<div className={styles.toolBarRight}>
							{showLoading && (
								<span onClick={handleInterrupt} className={styles.interruptIcon}>
									<img src={InterruptSvg} alt="" />
								</span>
							)}
							<Button
								disabled={sendButtonDisabled}
								className={cx(
									styles.sendButton,
									sendButtonDisabled && styles.sendButtonDisabled,
								)}
								type="primary"
								icon={<DelightfulIcon component={IconSend} size={20} />}
								onClick={handleSend}
							>
								Send
							</Button>
						</div>
					</div>
				</div>
			</div>
		</div>
	)
}

export default MessagePanel

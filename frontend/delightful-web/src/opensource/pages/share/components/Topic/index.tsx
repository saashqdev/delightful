import FooterIcon from "@/opensource/pages/share/assets/icon/footer_icon.svg"
import AttachmentList from "@/opensource/pages/beDelightful/components/AttachmentList"
import Detail from "@/opensource/pages/beDelightful/components/Detail"
import LoadingMessage from "@/opensource/pages/beDelightful/components/LoadingMessage"
import Node from "@/opensource/pages/beDelightful/components/MessageList/components/Node"
import TaskList from "@/opensource/pages/beDelightful/components/TaskList"
import type { TaskData } from "@/opensource/pages/beDelightful/pages/Workspace/types"
import ReplayLogo from "@/opensource/pages/share/assets/icon/replay_logo.svg"
import PreviewDetailPopup from "@/opensource/pages/beDelightfulMobile/components/PreviewDetailPopup/index"
import { IconFolder, IconLayoutGrid, IconLogin } from "@tabler/icons-react"
import { Button } from "antd"
import { Popup, SafeArea } from "antd-mobile"
import { isEmpty } from "lodash-es"
import { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react"
import { useNavigate } from "react-router-dom"
import MessageList from "../MessageList"
import { useStyles } from "./style"

export default function Topic({
	data,
	resource_name,
	isMobile,
	attachments,
	menuVisible,
	setMenuVisible,
	isLogined,
}: {
	data: any
	resource_name: string
	isMobile: boolean
	attachments: any
	menuVisible: boolean
	setMenuVisible: (visible: boolean) => void
	isLogined: boolean
}) {
	const { styles } = useStyles()
	const [taskData, setTaskData] = useState<TaskData | null>(null)
	const previewDetailPopupRef = useRef(null) as any
	const [messageList, setMessageList] = useState<any[]>([])
	const [autoDetail, setAutoDetail] = useState<any>({})
	const [userDetail, setUserDetail] = useState<any>({})
	const timerRef = useRef<any>({})
	const messageContainerRef = useRef<HTMLDivElement>(null)
	const [taskIsEnd, setTaskIsEnd] = useState(false)
	const [isBottom, setIsBottom] = useState(false)
	const navigate = useNavigate()
	const [attachmentVisible, setAttachmentVisible] = useState(false)
	const [hasStarted, setHasStarted] = useState(false)
	const [countdown, setCountdown] = useState(10)
	// const [userDetail, , setUserDetail] = useState()

	// Load first 10 messages initially
	useEffect(() => {
		if (data?.list?.length) {
			// Only load first 10 messages, or all if total is less than 10
			const initialCount = Math.min(10, data.list.length)
			const initialMessages = data.list.slice(0, initialCount)
			setMessageList(initialMessages)
		}
	}, [data])

	// Handle starting to show messages
	const startShowingMessages = useCallback(() => {
		if (!data?.list?.length || hasStarted) return
		setHasStarted(true)
		setIsBottom(false)
		// Ensure clearing any existing timers
		if (timerRef.current.timer) {
			clearInterval(timerRef.current.timer)
		}

		// Clear message list and start over
		setMessageList([])

		// Start loading messages from the beginning
		let currentIndex = 0
		timerRef.current.timer = setInterval(() => {
			if (currentIndex < data.list.length) {
				const newMessage = data.list[currentIndex]
				if (newMessage?.tool?.detail) {
					setAutoDetail?.(newMessage?.tool?.detail)
				}
				setMessageList((prev: any[]) => [...prev, newMessage])
				currentIndex += 1
			} else {
				clearInterval(timerRef.current.timer)
			}
		}, 400)
	}, [data, hasStarted])

	// Countdown to auto-start showing
	useEffect(() => {
		if (hasStarted || !data?.list?.length) return undefined

		// Clear any existing timers
		if (timerRef.current.countdownTimer) {
			clearInterval(timerRef.current.countdownTimer)
		}

		timerRef.current.countdownTimer = setInterval(() => {
			setCountdown((prev) => {
				if (prev <= 1) {
					clearInterval(timerRef.current.countdownTimer)
					startShowingMessages()
					return 0
				}
				return prev - 1
			})
		}, 1000)

		return () => {
			if (timerRef.current.countdownTimer) {
				clearInterval(timerRef.current.countdownTimer)
			}
		}
	}, [data, hasStarted, startShowingMessages, messageList])

	// Scroll to appropriate position in message list to ensure initial messages are visible
	useEffect(() => {
		if (messageList.length > 0 && !hasStarted) {
			const container = messageContainerRef.current
			if (container) {
				// Scroll to 30% height position to make upper messages visible
				container.scrollTop = container.scrollHeight * 0.3
			}
		}
	}, [messageList, hasStarted])

	// Cleanup timers
	useEffect(() => {
		return () => {
			if (timerRef.current.timer) {
				clearInterval(timerRef.current.timer)
			}
			if (timerRef.current.countdownTimer) {
				clearInterval(timerRef.current.countdownTimer)
			}
		}
	}, [])

	const handlePreviewDetail = useCallback(
		(item: any) => {
			previewDetailPopupRef.current?.open(item, attachments.tree)
		},
		[attachments],
	)

	const isAtBottomRef = useRef(true) // 👈 Use ref to save old isAtBottom state

	useEffect(() => {
		const el = messageContainerRef.current
		if (!el) return

		const handleScroll = () => {
			const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
			isAtBottomRef.current = distanceFromBottom < 30
		}

		el.addEventListener("scroll", handleScroll)
		return () => el.removeEventListener("scroll", handleScroll)
	}, [messageList])

	// 👇 2. After DOM rendering is complete, determine whether to scroll to bottom
	useLayoutEffect(() => {
		const el = messageContainerRef.current
		if (!el) return

		if (isAtBottomRef.current) {
			requestAnimationFrame(() => {
				el.scrollTo({
					top: el.scrollHeight,
					behavior: "smooth",
				})
			})
		}
	}, [messageList]) // 👈 Note: Cannot judge isScrolledToBottom here, use previously recorded isAtBottomRef instead

	useEffect(() => {
		if (messageList.length === data?.list?.length) {
			setIsBottom(true)
		}
	}, [messageList.length, data?.list?.length])

	// When message list changes, find the last message with task and task.process length not 0
	useEffect(() => {
		if (messageList && messageList.length > 0) {
			// Traverse from back to front to find the first message that meets the condition
			let foundTaskData = false
			for (let i = messageList.length - 1; i >= 0; i -= 1) {
				const message = messageList[i]
				if (message?.steps && message?.steps?.length > 0) {
					// Set as current task data
					setTaskData({
						process: message.steps,
						topic_id: message.topic_id,
					})
					foundTaskData = true
					break
				}
			}
			// If no message meeting the condition is found, clear TaskData
			if (!foundTaskData) {
				setTaskData(null)
			}
			const lastMessageWithTaskId = messageList
				.slice()
				.reverse()
				.find((message) => message.role === "assistant")
			const lastMessage = messageList[messageList.length - 1]
			const isLoading =
				lastMessageWithTaskId?.status === "running" || lastMessage?.text?.content
			setTaskIsEnd(!isLoading)
		} else {
			// If message list is empty, also clear TaskData
			setTaskData(null)
		}
	}, [messageList])

	// Handle showing result directly: load all messages immediately, stop countdown
	const handleShowResult = useCallback(() => {
		setUserDetail({})
		setHasStarted(true)
		// Find the last message with detail among all messages
		const lastDetailItem = [...data.list]
			.reverse()
			.find((item) => item?.tool?.detail && !isEmpty(item.tool.detail))
		if (lastDetailItem?.tool?.detail) {
			setAutoDetail(lastDetailItem.tool.detail)
		}

		// Clear all timers
		if (timerRef.current.timer) {
			clearInterval(timerRef.current.timer)
		}
		if (timerRef.current.countdownTimer) {
			clearInterval(timerRef.current.countdownTimer)
		}

		// Set all messages at once
		setMessageList(data.list)

		// Scroll to bottom
		requestAnimationFrame(() => {
			const container = messageContainerRef.current
			if (container) {
				container.scrollTo({
					top: container.scrollHeight,
					behavior: "smooth",
				})
			}
		})
		setIsBottom(true)
	}, [data.list])

	return (
		<>
			<div className={styles.topicContainer}>
				<PreviewDetailPopup
					ref={previewDetailPopupRef}
					setUserSelectDetail={(detail) => {
						handlePreviewDetail(detail)
					}}
					onClose={() => {
						handlePreviewDetail(autoDetail)
					}}
				/>

				{hasStarted && (
					<>
						{isMobile ||
						(isEmpty(taskData) && isEmpty(attachments.tree)) ||
						(isEmpty(taskData) && !isEmpty(attachments.tree) && !isBottom) ? null : (
							<div className={styles.leftContainer}>
								{!isEmpty(taskData) ? (
									<div className={styles.taskData}>
										<TaskList taskData={taskData} mode="view" />
									</div>
								) : null}
								{!isEmpty(attachments.tree) && isBottom && (
									<div className={styles.attachmentList}>
										<AttachmentList
											attachments={attachments.tree}
											setUserSelectDetail={setUserDetail}
										/>
									</div>
								)}
							</div>
						)}
						{!isEmpty(autoDetail) || !isEmpty(userDetail) ? (
							isMobile ? null : (
								<div className={styles.detail}>
									<Detail
										disPlayDetail={
											isEmpty(userDetail) ? autoDetail : userDetail
										}
										attachments={attachments.tree}
										userSelectDetail={userDetail}
										setUserSelectDetail={setUserDetail}
									/>
								</div>
							)
						) : null}
					</>
				)}

				<div
					className={`${styles.messageContainer} ${
						(isEmpty(taskData) && isEmpty(autoDetail) && isEmpty(userDetail)) ||
						!hasStarted
							? styles.fullWidthMessageContainer
							: ""
					} ${!hasStarted ? styles.messageContainerNotStarted : ""}`}
				>
					<div className={styles.messageListHeader}>
						{resource_name || "Default Topic"}
					</div>
					<div className={styles.messageListContainer} ref={messageContainerRef}>
						<MessageList
							messageList={messageList}
							onSelectDetail={(detail) => {
								setUserDetail(detail)
								if (isMobile) {
									handlePreviewDetail(detail)
								}
							}}
						/>
						{!taskIsEnd && messageList?.length > 0 && !hasStarted && <LoadingMessage />}
					</div>
				</div>
			</div>

			{hasStarted && (
				<div className={styles.footer}>
					<div className={styles.footerContent}>
						<div className={styles.footerLeft}>
							<img src={FooterIcon} alt="" className={styles.footerIcon} />
							<span>
								Super Magi {taskIsEnd ? "Task completed" : "Executing task..."}
							</span>
						</div>
						{!isBottom ? (
							<Button type="primary" onClick={handleShowResult}>
								Show results directly
							</Button>
						) : null}
					</div>
				</div>
			)}
			{!hasStarted && (
				<div className={styles.waitingContainer}>
					<div className={styles.replayLogoContainer}>
						<div className={styles.overlay}></div>
						<div className={styles.replayLogoDiv}>
							<img src={ReplayLogo} alt="" className={styles.replayLogo} />
						</div>
					</div>
					<div className={styles.watingTitleWrapper}>
						<div className={styles.watingTitle}>
							You are viewing task 「{resource_name}」
						</div>
					</div>
					<div className={styles.waitingTextWrapper}>
						<div className={styles.waitingText}>
							Playback will start automatically in {countdown} seconds
						</div>
					</div>
					<Button
						type="primary"
						onClick={startShowingMessages}
						className={styles.waitingButton}
					>
						View now
					</Button>
				</div>
			)}
			<Popup
				visible={menuVisible}
				bodyStyle={{ width: "80%", backgroundColor: "#fff", padding: "20px" }}
				position="right"
				onMaskClick={() => {
					setMenuVisible(false)
				}}
			>
				<SafeArea position="top" />
				<div className={styles.menuContainer}>
					<div className={styles.title}>Navigation</div>
					{!isLogined ? (
						<div className={styles.item} onClick={() => navigate("/login")}>
							<IconLogin className={styles.icon} />
							Login
						</div>
					) : (
						<div className={styles.item} onClick={() => navigate("/super/workspace")}>
							<IconLayoutGrid className={styles.icon} />
							Enter Workspace
						</div>
					)}
				</div>
				<div className={styles.menuContainer}>
					<div className={styles.title}>Topic</div>
					<div className={styles.item} onClick={() => setAttachmentVisible(true)}>
						<IconFolder className={styles.icon} /> <span>View topic files</span>
					</div>
				</div>
				<SafeArea position="bottom" />
			</Popup>
			<Popup
				onMaskClick={() => {
					setAttachmentVisible(false)
				}}
				visible={attachmentVisible}
				bodyStyle={{ height: "90%", backgroundColor: "#fff" }}
			>
				<SafeArea position="top" />
				<div className={styles.attachmentList}>
					<AttachmentList
						attachments={attachments.tree}
						setUserSelectDetail={(detail) => {
							setUserDetail(detail)
							if (isMobile) {
								handlePreviewDetail(detail)
							}
						}}
					/>
				</div>
				<SafeArea position="bottom" />
			</Popup>
		</>
	)
}

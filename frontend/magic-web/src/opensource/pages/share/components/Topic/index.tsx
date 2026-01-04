import FooterIcon from "@/opensource/pages/share/assets/icon/footer_icon.svg"
import AttachmentList from "@/opensource/pages/superMagic/components/AttachmentList"
import Detail from "@/opensource/pages/superMagic/components/Detail"
import LoadingMessage from "@/opensource/pages/superMagic/components/LoadingMessage"
import Node from "@/opensource/pages/superMagic/components/MessageList/components/Node"
import TaskList from "@/opensource/pages/superMagic/components/TaskList"
import type { TaskData } from "@/opensource/pages/superMagic/pages/Workspace/types"
import ReplayLogo from "@/opensource/pages/share/assets/icon/replay_logo.svg"
import PreviewDetailPopup from "@/opensource/pages/superMagicMobile/components/PreviewDetailPopup/index"
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

	// 初始加载前10条消息
	useEffect(() => {
		if (data?.list?.length) {
			// 只加载前10条消息，或者如果总条数少于10则全部加载
			const initialCount = Math.min(10, data.list.length)
			const initialMessages = data.list.slice(0, initialCount)
			setMessageList(initialMessages)
		}
	}, [data])

	// 处理开始显示消息
	const startShowingMessages = useCallback(() => {
		if (!data?.list?.length || hasStarted) return
		setHasStarted(true)
		setIsBottom(false)
		// 确保清除任何现有的定时器
		if (timerRef.current.timer) {
			clearInterval(timerRef.current.timer)
		}

		// 清空消息列表，重新开始
		setMessageList([])

		// 从头开始加载消息
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

	// 倒计时自动开始显示
	useEffect(() => {
		if (hasStarted || !data?.list?.length) return undefined

		// 清除任何现有的定时器
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

	// 滚动到消息列表的合适位置，确保初始消息可见
	useEffect(() => {
		if (messageList.length > 0 && !hasStarted) {
			const container = messageContainerRef.current
			if (container) {
				// 滚动到50%高度位置，让上半部分消息可见
				container.scrollTop = container.scrollHeight * 0.3
			}
		}
	}, [messageList, hasStarted])

	// 清理定时器
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

	const isAtBottomRef = useRef(true) // 👈 用 ref 保存旧的 isAtBottom 状态

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

	// 👇 2. 在 DOM 完成渲染后再判断要不要滚到底部
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
	}, [messageList]) // 👈 注意：这里不能再判断 isScrolledToBottom，而是用之前记录的 isAtBottomRef

	useEffect(() => {
		if (messageList.length === data?.list?.length) {
			setIsBottom(true)
		}
	}, [messageList.length, data?.list?.length])

	// 当消息列表变化时，查找最后一条有task且task.process长度不为0的消息
	useEffect(() => {
		if (messageList && messageList.length > 0) {
			// 从后往前遍历找到第一个符合条件的消息
			let foundTaskData = false
			for (let i = messageList.length - 1; i >= 0; i -= 1) {
				const message = messageList[i]
				if (message?.steps && message?.steps?.length > 0) {
					// 设置为当前任务数据
					setTaskData({
						process: message.steps,
						topic_id: message.topic_id,
					})
					foundTaskData = true
					break
				}
			}
			// 如果没有找到符合条件的消息，清空TaskData
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
			// 如果消息列表为空，也清空TaskData
			setTaskData(null)
		}
	}, [messageList])

	// 直接显示结果的处理：立即加载所有消息，停止倒计时
	const handleShowResult = useCallback(() => {
		setUserDetail({})
		setHasStarted(true)
		// 查找所有消息中带有详情的最后一条
		const lastDetailItem = [...data.list]
			.reverse()
			.find((item) => item?.tool?.detail && !isEmpty(item.tool.detail))
		if (lastDetailItem?.tool?.detail) {
			setAutoDetail(lastDetailItem.tool.detail)
		}

		// 清除所有定时器
		if (timerRef.current.timer) {
			clearInterval(timerRef.current.timer)
		}
		if (timerRef.current.countdownTimer) {
			clearInterval(timerRef.current.countdownTimer)
		}

		// 一次性设置所有消息
		setMessageList(data.list)

		// 滚动到底部
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
					<div className={styles.messageListHeader}>{resource_name || "默认话题"}</div>
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
							<span>超级麦吉 {taskIsEnd ? "任务完成" : "正在执行任务..."}</span>
						</div>
						{!isBottom ? (
							<Button type="primary" onClick={handleShowResult}>
								直接显示结果
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
						<div className={styles.watingTitle}>您正在查看任务 「{resource_name}」</div>
					</div>
					<div className={styles.waitingTextWrapper}>
						<div className={styles.waitingText}>回放将在 {countdown} 秒后自动开始</div>
					</div>
					<Button
						type="primary"
						onClick={startShowingMessages}
						className={styles.waitingButton}
					>
						立即查看
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
					<div className={styles.title}>导航</div>
					{!isLogined ? (
						<div className={styles.item} onClick={() => navigate("/login")}>
							<IconLogin className={styles.icon} />
							登录
						</div>
					) : (
						<div className={styles.item} onClick={() => navigate("/super/workspace")}>
							<IconLayoutGrid className={styles.icon} />
							进入工作区
						</div>
					)}
				</div>
				<div className={styles.menuContainer}>
					<div className={styles.title}>话题</div>
					<div className={styles.item} onClick={() => setAttachmentVisible(true)}>
						<IconFolder className={styles.icon} /> <span>查看话题文件</span>
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

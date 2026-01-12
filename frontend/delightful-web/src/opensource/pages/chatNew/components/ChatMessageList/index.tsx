import { observer, useLocalObservable } from "mobx-react-lite"
import { useRef, useCallback, useEffect, useLayoutEffect } from "react"
import { useMemoizedFn } from "ahooks"
import MessageStore from "@/opensource/stores/chatNew/message"
import MessageService from "@/opensource/services/chat/message/MessageService"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import MessageImagePreview from "@/opensource/services/chat/message/MessageImagePreview"
import DelightfulDropdown from "@/opensource/components/base/DelightfulDropdown"
import MessageDropdownService from "@/opensource/services/chat/message/MessageDropdownService"
import MessageDropdownStore from "@/opensource/stores/chatNew/messageUI/Dropdown"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { useTranslation } from "react-i18next"
import { autorun, toJS } from "mobx"
import { cx } from "antd-style"
import { DomClassName } from "@/const/dom"
import { debounce, throttle } from "lodash-es"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import AiConversationMessageLoading from "./components/AiConversationMessageLoading"
import BackBottom from "./components/BackBottom"
import { useStyles } from "./styles"
import GroupSeenPanelStore, {
	domClassName as GroupSeenPanelDomClassName,
} from "@/opensource/stores/chatNew/groupSeenPanel"
import { isMessageInView } from "./utils"
import MessageRender from "./components/MessageRender"
import { safeBtoaToJson } from "@/utils/encoding"

let canScroll = true
let isScrolling = false
let lastScrollTop = 0
let lastMessageId = ""

const ChatMessageList = observer(() => {
	const { t } = useTranslation()
	const { fontSize } = useFontSize()
	const { styles } = useStyles({ fontSize })
	const bottomRef = useRef<HTMLDivElement | null>(null)
	const wrapperRef = useRef<HTMLDivElement | null>(null)
	const chatListRef = useRef<HTMLDivElement | null>(null)
	const resizeObserverRef = useRef<ResizeObserver | null>(null)
	const initialRenderRef = useRef(true)
	const isContentChanging = useRef(false)
	const checkMessagesFillViewportTimerRef = useRef<NodeJS.Timeout | null>(null)
	const state = useLocalObservable(() => ({
		isLoadingMore: false,
		isAtBottom: true,
		openDropdown: false,
		isConversationSwitching: false,
		marginSize: 4,
		size: { width: 92, height: 240 },
		dropdownPosition: { x: 0, y: 0 },
		setIsLoadingMore: (value: boolean) => {
			state.isLoadingMore = value
		},
		setIsAtBottom: (value: boolean) => {
			state.isAtBottom = value
		},
		setOpenDropdown: (value: boolean) => {
			state.openDropdown = value
		},
		setIsConversationSwitching: (value: boolean) => {
			state.isConversationSwitching = value
		},
		setDropdownPosition: (value: { x: number; y: number }) => {
			state.dropdownPosition = value
			state.adjustPosition()
		},
		adjustPosition: () => {
			// Adjust position to prevent overflow off screen
			if (typeof window !== "undefined") {
				const windowWidth = window.innerWidth - state.marginSize * 2
				const windowHeight = window.innerHeight - state.marginSize * 2

				// Ensure card right boundary doesn't exceed screen
				if (state.dropdownPosition.x + state.size.width + state.marginSize > windowWidth) {
					state.dropdownPosition.x = windowWidth - state.size.width - state.marginSize
				}

				// Ensure card does not exceed left boundary
				if (state.dropdownPosition.x < 0) {
					state.dropdownPosition.x = state.marginSize
				}

				// Ensure card bottom does not exceed screen
				if (state.dropdownPosition.y + state.size.height > windowHeight) {
					state.dropdownPosition.y = windowHeight - state.size.height - state.marginSize
				}

				// Ensure card does not exceed top boundary
				if (state.dropdownPosition.y < 0) {
					state.dropdownPosition.y = state.marginSize
				}
			}
		},
		reset() {
			state.isLoadingMore = false
			state.isAtBottom = true
			state.openDropdown = false
			state.isConversationSwitching = false
			state.dropdownPosition = { x: 0, y: 0 }
		},
	}))

	// const renderMessage = useMemoizedFn((message: any) => {
	// 	switch (message.type) {
	// 		case ControlEventMessageType.GroupAddMember:
	// 			return (
	// 				<InviteMemberTip
	// 					key={message.message_id}
	// 					content={message.message as GroupAddMemberMessage}
	// 				/>
	// 			)
	// 		case ControlEventMessageType.GroupCreate:
	// 			return (
	// 				<GroupCreateTip
	// 					key={message.message_id}
	// 					content={message.message as GroupCreateMessage}
	// 				/>
	// 			)
	// 		case ControlEventMessageType.GroupUsersRemove:
	// 			return (
	// 				<GroupUsersRemoveTip
	// 					key={message.message_id}
	// 					content={message.message as GroupUsersRemoveMessage}
	// 				/>
	// 			)
	// 		case ControlEventMessageType.GroupUpdate:
	// 			return (
	// 				<GroupUpdateTip
	// 					key={message.message_id}
	// 					content={message.message as GroupUpdateMessage}
	// 				/>
	// 			)
	// 		case ControlEventMessageType.GroupDisband:
	// 			return (
	// 				<GroupDisbandTip
	// 					key={message.message_id}
	// 					content={message.message as GroupDisbandMessage}
	// 				/>
	// 			)
	// 		default:
	// 			return message.revoked ? (
	// 				<RevokeTip key={message.message_id} senderUid={message.sender_id} />
	// 			) : (
	// 				<MessageItem
	// 					key={message.message_id}
	// 					message_id={message.message_id}
	// 					sender_id={message.sender_id}
	// 					name={message.name}
	// 					avatar={message.avatar}
	// 					is_self={message.is_self ?? false}
	// 					message={message.message}
	// 					unread_count={message.unread_count}
	// 					refer_message_id={message.refer_message_id}
	// 				/>
	// 			)
	// 	}
	// })

	const scrollToMessage = useMemoizedFn(
		(
			messageId: string,
			block: "center" | "start" | "end",
			behavior: "smooth" | "auto" = "smooth",
		) => {
			if (wrapperRef.current) {
				const messageElement = wrapperRef.current.querySelector(
					`[data-message-id="${messageId}"]`,
				)
				if (messageElement) {
					canScroll = false
					isScrolling = true
					messageElement.scrollIntoView({ behavior, block })
					setTimeout(() => {
						isScrolling = false
						canScroll = true
					}, 0)
				}
			}
		},
	)

	const scrollToBottom = useMemoizedFn((force?: boolean) => {
		// Don't allow scrolling
		if (!canScroll && !force) {
			return
		}

		if (bottomRef?.current) {
			isScrolling = true
			bottomRef.current.scrollIntoView({ behavior: "smooth" })
		}

		setTimeout(() => {
			isScrolling = false
			state.setIsAtBottom(true)
			canScroll = true
		}, 0)
	})

	// Load more history messages
	const loadMoreHistoryMessages = useMemoizedFn(async () => {
		if (state.isLoadingMore || !MessageStore.hasMoreHistoryMessage) return

		try {
			state.setIsLoadingMore(true)
			canScroll = false

			// Request history messages
			await MessageService.getHistoryMessages(
				conversationStore.currentConversation?.id ?? "",
				conversationStore.currentConversation?.current_topic_id ?? "",
			)
		} catch (error) {
			// Restore styles when error occurs
			if (chatListRef.current) {
				chatListRef.current.style.transform = ""
				chatListRef.current.style.position = ""
			}
		} finally {
			state.setIsLoadingMore(false)
		}
	})

	// Check scroll position and handle
	const checkScrollPosition = useMemoizedFn(() => {
		if (!wrapperRef.current || !initialRenderRef.current || isScrolling) return
		// Don't handle in initial state
		if (lastScrollTop === 0) {
			lastScrollTop = wrapperRef.current.scrollTop
			return
		}

		const { scrollTop, clientHeight, scrollHeight } = wrapperRef.current
		const distance = Math.abs(scrollTop + clientHeight - scrollHeight)

		state.setIsAtBottom(distance < 100)
		canScroll = distance < 100

		const isScrollUp = lastScrollTop - scrollTop > 0
		lastScrollTop = scrollTop
		if (isScrollUp && !state.isLoadingMore) {
			// Load more, check if the fourth message enters the view
			const messageId = MessageStore.messages[3]?.message_id

			if (isMessageInView(messageId, wrapperRef.current) || scrollTop < 150) {
				loadMoreHistoryMessages()
			}
		}
	})

	// Check if need to load more messages to fill viewport
	const checkMessagesFillViewport = useMemoizedFn(async () => {
		if (
			!wrapperRef.current ||
			!chatListRef.current ||
			state.isLoadingMore ||
			!MessageStore.hasMoreHistoryMessage
		) {
			return
		}

		const wrapperHeight = wrapperRef.current.clientHeight
		const listHeight = chatListRef.current.clientHeight

		// If content height is less than container height, and we have enough messages to load
		// Load more history messages until fill the viewport or no more messages
		if (listHeight < wrapperHeight && MessageStore.messages.length > 0) {
			console.log("Container not filled, trying to load more history messages", listHeight, wrapperHeight)

			try {
				await loadMoreHistoryMessages()

				// Recursively check until filled or no more messages
				if (checkMessagesFillViewportTimerRef.current) {
					clearTimeout(checkMessagesFillViewportTimerRef.current)
				}

				checkMessagesFillViewportTimerRef.current = setTimeout(() => {
					checkMessagesFillViewport()
				}, 300)
			} catch (error) {
				console.error("Load more messages failed", error)
			}
		}
	})

	// Handle container size change
	const handleResize = useMemoizedFn(() => {
		if (!chatListRef.current || isScrolling) return

		const { messages } = MessageStore
		if (!messages.length) return

		// If last message is empty, it's initial state, scroll to bottom
		if (!lastMessageId) {
			lastMessageId = messages[messages.length - 1]?.message_id
			scrollToBottom(true)

			return
		}

		// Has new message, and not current message, try to scroll to bottom
		const lastMessage = messages[messages.length - 1]
		// If it's a new message I sent, scroll to bottom, or is at bottom
		if (
			(lastMessage.is_self && lastMessage?.message_id !== lastMessageId) ||
			state.isAtBottom
		) {
			console.log("handleResize send bottom")
			lastMessageId = lastMessage?.message_id
			scrollToBottom(true)
			return
		}

		// update lastMessageId
		lastMessageId = lastMessage?.message_id

		// Other cases, scroll back to bottom
		if (canScroll && wrapperRef.current) {
			return wrapperRef.current.scrollTo({
				top: chatListRef.current.clientHeight,
				behavior: "smooth",
			})
		}

		// Data changed and scroll at top, load one more page
		if (
			wrapperRef.current &&
			wrapperRef.current.scrollTop === 0 &&
			!MessageStore.hasMoreHistoryMessage
		) {
			loadMoreHistoryMessages()
			requestAnimationFrame(() => {
				if (wrapperRef.current) {
					wrapperRef.current.scrollTop = 200
				}
			})
		}
	})

	const handleContainerScroll = throttle(() => {
		// When list size changes, don't handle
		if (isContentChanging.current) return

		checkScrollPosition()
	}, 50)

	// Switch conversation or topic
	useEffect(() => {
		if (wrapperRef.current) {
			wrapperRef.current.removeEventListener("scroll", handleContainerScroll)
		}

		// Immediately set switching state to prevent message crossover
		state.setIsConversationSwitching(true)
		state.reset()
		lastMessageId = ""
		canScroll = true
		lastScrollTop = 0
		initialRenderRef.current = false

		// Immediately scroll to bottom, no delay
		setTimeout(() => {
			scrollToBottom(true)
		}, 0)

		// Reduce delay time, quickly restore normal state
		setTimeout(() => {
			if (wrapperRef.current) {
				wrapperRef.current.addEventListener("scroll", handleContainerScroll)
			}
			initialRenderRef.current = true
			state.setIsConversationSwitching(false)

			// After conversation switch, check if messages fill viewport
			if (checkMessagesFillViewportTimerRef.current) {
				clearTimeout(checkMessagesFillViewportTimerRef.current)
			}
			checkMessagesFillViewport()
		}, 100) // Reduce delay from 1000ms to 100ms
	}, [MessageStore.conversationId, MessageStore.topicId])

	useLayoutEffect(() => {
		// Create ResizeObserver instance to listen for message list height changes
		resizeObserverRef.current = new ResizeObserver(
			debounce((entries) => {
				const chatList = entries[0]
				if (!chatList) return
				// List size change
				isContentChanging.current = true
				handleResize()
				// Reset
				setTimeout(() => {
					isContentChanging.current = false
				}, 0)
			}, 100),
		)

		// Start observation
		if (chatListRef.current) {
			resizeObserverRef.current.observe(chatListRef.current)
		}

		// Message focus
		const focusDisposer = autorun(() => {
			if (MessageStore.focusMessageId) {
				scrollToMessage(MessageStore.focusMessageId, "center")
			}
		})

		function handleClick(e: MouseEvent) {
			const target = e.target as HTMLElement
			if (target.classList.contains("message-item-menu")) {
				return
			}
			state.setOpenDropdown(false)
		}

		document.addEventListener("click", handleClick)

		return () => {
			focusDisposer()
			document.removeEventListener("click", handleClick)
			state.reset()
			resizeObserverRef.current?.disconnect()
			resizeObserverRef.current = null
			if (wrapperRef.current) {
				wrapperRef.current.removeEventListener("scroll", handleContainerScroll)
			}
			if (checkMessagesFillViewportTimerRef.current) {
				clearTimeout(checkMessagesFillViewportTimerRef.current)
			}
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	const handleContainerClick = useCallback((e: React.MouseEvent) => {
		const target = e.target as HTMLElement
		// Starting from clicked element, search upward until finding element with data-message-id
		const messageElement = target.closest("[data-message-id]")
		const messageId = messageElement?.getAttribute("data-message-id")

		// If image click, and not emoji
		if (target.tagName === "IMG" && target.classList.contains("delightful-image")) {
			const fileInfo = target.getAttribute("data-file-info")
			if (fileInfo) {
				try {
					const fileInfoObj = safeBtoaToJson(fileInfo)
					if (fileInfoObj) {
						// If same image, reset state first
						MessageImagePreview.setPreviewInfo({
							...fileInfoObj,
							messageId,
							conversationId: conversationStore.currentConversation?.id,
						})
					}
				} catch (error) {
					console.error("Parse file information failed", error)
				}
			}
		}

		if (messageElement && messageElement.classList.contains(GroupSeenPanelDomClassName)) {
			if (messageId) {
				GroupSeenPanelStore.openPanel(messageId, { x: e.clientX, y: e.clientY })
			}
		} else if (GroupSeenPanelStore.open) {
			GroupSeenPanelStore.closePanel()
		}
	}, [])

	const handleContainerContextMenu = (e: React.MouseEvent) => {
		e.preventDefault()
		const target = e.target as HTMLElement
		if (target.closest(`.${DomClassName.MESSAGE_ITEM}`)) {
			// Search upward from clicked element until finding element with data-message-id attribute
			const messageElement = target.closest("[data-message-id]")
			const messageId = messageElement?.getAttribute("data-message-id")
			MessageDropdownService.setMenu(messageId ?? "", e.target)
			state.setDropdownPosition({ x: e.clientX, y: e.clientY })
			state.setOpenDropdown(true)
		}
	}

	return (
		<div
			className={cx(styles.container)}
			onClick={handleContainerClick}
			onContextMenu={handleContainerContextMenu}
		>
			{state.isLoadingMore && (
				<div className={styles.loadingMore}>
					{t("message.chat.loadingMoreMessages", { ns: "interface" })}
				</div>
			)}
			<div
				ref={wrapperRef}
				className={cx(styles.wrapper)}
				// onScroll={handleContainerScroll()}
				style={{ position: "relative", overflow: "auto" }}
			>
				<div
					ref={chatListRef}
					className={cx(styles.chatList)}
					data-testid="chat-list"
					style={{
						willChange: "transform",
					}}
				>
					{/* Display loading state when conversation switching to prevent message crossover */}
					{state.isConversationSwitching ? (
						<div className={styles.conversationSwitching}>
							<div>
								{t("message.chat.switchingConversation", { ns: "interface" })}
							</div>
						</div>
					) : (
						MessageStore.messages
							.filter((message) => {
								// Filter messages, ensure only current conversation messages are displayed
								return (
									message.conversation_id === MessageStore.conversationId &&
									message.message.topic_id === MessageStore.topicId
								)
							})
							.map((message) => {
								// Use composite key to prevent component reuse between different conversations
								const messageKey = `${MessageStore.conversationId}-${MessageStore.topicId}-${message.message_id}`
								return (
									<div
										id={message.message_id}
										key={messageKey}
										style={{ willChange: "transform" }}
										data-conversation-id={message.conversation_id}
										data-message-id={message.message_id}
									>
										<MessageRender message={message} />
									</div>
								)
							})
					)}
					<AiConversationMessageLoading
						key={`ai-loading-${MessageStore.conversationId}-${MessageStore.topicId}`}
					/>
					<div ref={bottomRef} />
				</div>
				<DelightfulDropdown
					className="message-item-menu"
					autoAdjustOverflow
					open={state.openDropdown}
					overlayClassName={styles.dropdownMenu}
					trigger={[]}
					overlayStyle={{
						position: "fixed",
						left: state.dropdownPosition.x,
						top: state.dropdownPosition.y,
					}}
					menu={{
						items: MessageDropdownStore.menu.map((item) => {
							if (item.key.startsWith("divider")) {
								return {
									key: item.key,
									type: "divider",
								}
							}
							return {
								icon: item.icon ? (
									<DelightfulIcon
										color={item.icon.color}
										component={item.icon.component as any}
										size={item.icon.size}
									/>
								) : undefined,
								key: item.key,
								label: t(item.label ?? "", { ns: "interface" }),
								danger: item.danger,
								onClick: () => {
									MessageDropdownService.clickMenuItem(item.key as any)
								},
							}
						}),
					}}
				>
					<div style={{ display: "none" }} />
				</DelightfulDropdown>
			</div>
			<BackBottom visible={!state.isAtBottom} onScrollToBottom={() => scrollToBottom(true)} />
		</div>
	)
})

export default ChatMessageList

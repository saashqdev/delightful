import {
	getAttachmentsByThreadId,
	getMessagesByConversationId,
	smartTopicRename,
	getTopicsByWorkspaceId,
	getTopicDetailByTopicId,
} from "@/opensource/pages/beDelightful/utils/api"
import BeDelightfulMobileWorkSpace from "@/opensource/pages/beDelightfulMobile/pages/workspace/index"
import pubsub from "@/utils/pubsub"
import { useDeepCompareEffect, useResponsive } from "ahooks"
import { Modal } from "antd"
import { cx } from "antd-style"
import { isEmpty } from "lodash-es"
import { useCallback, useEffect, useMemo, useRef, useState } from "react"
import Detail from "../../components/Detail"
import EmptyWorkspacePanel from "../../components/EmptyWorkspacePanel"
import MessageList from "../../components/MessageList"
import type { MessagePanelProps } from "../../components/MessagePanel/MessagePanel"
import MessagePanel from "../../components/MessagePanel/MessagePanel"
import WorkspaceHeader from "../../components/WorkspaceHeader"
import WorkspacePanel from "../../components/WorkSpacePanel"
import { useTopics } from "../../hooks/useTopics"
import { useWorkspace } from "../../hooks/useWorkspace"
import { userStore } from "@/opensource/models/user"
import { observer } from "mobx-react-lite"
import useStyles from "./style"
import type { TaskData, Workspace } from "./types"
// Workspace component
function MainWorkspaceContent() {
	const { styles } = useStyles()
	const { userInfo } = userStore.user
	const [userSelectDetail, setUserSelectDetail] = useState() as any
	const [autoDetail, setAutoDetail] = useState() as any
	// Task list data
	const [taskData, setTaskData] = useState<TaskData | null>(null)
	const [showLoading, setShowLoading] = useState(false)
	// Mapping of topic_id and page_token
	const topicPageTokenMap = useRef<Record<string, string>>({})
	const topicNotHaveMoreMessageMap = useRef<Record<string, boolean>>({})
	const responsive = useResponsive()
	const [topicModeInfo, setTopicModeInfo] = useState<any>("")
	const isMobile = responsive.md === false
	// Use custom hooks to manage state and logic
	const {
		workspaces,
		setWorkspaces,
		selectedWorkspace,
		setSelectedWorkspace,
		editingWorkspaceId,
		editingName,
		isAddingWorkspace,
		handleInputChange,
		handleInputKeyDown,
		handleInputBlur,
		handleStartEditWorkspace,
		handleStartAddWorkspace,
		handleDeleteWorkspace,
		fetchWorkspaces,
		// resetEditing,
	} = useWorkspace()

	const {
		messages,
		fileList,
		setFileList,
		attachments,
		setAttachments,
		handleSendMessage,
		initializeTopicMessages,
		selectedThreadInfo,
		setTopicMessagesMap,
		setSelectedThreadInfo,
	} = useTopics(userInfo)

	const updateDetail = useCallback(
		({ latestMessageDetail, isLoading }: { latestMessageDetail: any; isLoading: boolean }) => {
			if (isEmpty(latestMessageDetail)) {
				setAutoDetail({
					type: "empty",
					data: {
						text: isLoading ? "Thinking" : "Task completed",
					},
				})
			} else {
				setAutoDetail(latestMessageDetail)
			}
		},
		[setAutoDetail],
	)

	useEffect(() => {
		if (messages.length > 1) {
			const lastMessageWithTaskId = messages
				.slice()
				.reverse()
				.find((message) => message.role === "assistant")
			const lastMessage = messages
				.slice()
				.reverse()
				.find((message) => message)
			const isLoading = lastMessageWithTaskId?.status === "running" || !lastMessage?.seq_id
			setShowLoading(isLoading)

			const lastDetailMessage = messages
				.slice()
				.reverse()
				.find((message) => !isEmpty(message?.tool?.detail))

			updateDetail({ latestMessageDetail: lastDetailMessage?.tool?.detail, isLoading })
		} else if (messages?.length === 1) {
			setShowLoading(true)
		}
	}, [messages, updateDetail])

	useEffect(() => {
		pubsub.subscribe("OrganizationList_close", (data: boolean) => {
			if (data) {
				setWorkspaces([])
				fetchWorkspaces()
			}
		})
		return () => {
			pubsub?.unsubscribe("OrganizationList_close")
		}
	}, [])

	useDeepCompareEffect(() => {
		setAutoDetail(null)
		setUserSelectDetail(null)
		setShowLoading(false)
	}, [selectedThreadInfo])

	const updateAttachments = useCallback((selectedThreadInfo: any) => {
		if (!selectedThreadInfo?.id) {
			setAttachments([])
			return
		}
		try {
			getAttachmentsByThreadId({ id: selectedThreadInfo?.id }).then((res: any) => {
				setAttachments(res?.tree || [])
			})
		} catch (error) {
			console.error("Failed to fetch attachments:", error)
			setAttachments([])
		}
	}, [])

	const updateTopicModeInfo = useCallback((topic_id: string) => {
		if (!topic_id) {
			return setTopicModeInfo("")
		}
		getTopicDetailByTopicId({ id: topic_id }).then((res: any) => {
			setTopicModeInfo(res?.task_mode || "")
		})
	}, [])

	const isEmptyStatus = useMemo(() => {
		return messages?.length === 0
	}, [messages])

	useDeepCompareEffect(() => {
		updateAttachments(selectedThreadInfo)
		updateTopicModeInfo(selectedThreadInfo?.id)
	}, [selectedThreadInfo])

	const disPlayDetail = useMemo(() => {
		return userSelectDetail || autoDetail
	}, [userSelectDetail, autoDetail])

	// Handle delete workspace
	const handleDeleteWorkspaceWithConfirm = useCallback(
		(id: string) => {
			Modal.confirm({
				title: "Confirm Delete",
				content: `Are you sure you want to delete workspace "${workspaces.find((ws) => ws.id === id)?.name}"?`,
				onOk: () => handleDeleteWorkspace(id),
			})
		},
		[workspaces, handleDeleteWorkspace],
	)

	const pullMessage = useCallback(
		({
			conversation_id,
			chat_topic_id,
			page_token,
			order,
			limit = 20,
			updatePageToken = true,
		}: {
			conversation_id: string
			chat_topic_id: string
			page_token: string
			order: "asc" | "desc"
			limit?: number
			updatePageToken?: boolean
		}) => {
			if (
				topicNotHaveMoreMessageMap.current[chat_topic_id] &&
				page_token &&
				updatePageToken
			) {
				console.log("No more messages")
				return
			}
			getMessagesByConversationId({
				conversation_id,
				chat_topic_id,
				page_token,
				limit,
				order,
			}).then((res) => {
				const newMessage = res?.items
					.filter((item: any) => {
						return (
							item?.seq?.message?.general_agent_card ||
							item?.seq?.message?.text?.content
						)
					})
					?.map((item: any) => {
						const data = item?.seq?.message?.general_agent_card
							? item?.seq?.message?.general_agent_card
							: item?.seq?.message
						return {
							...data,
							seq_id: item?.seq?.seq_id,
						}
					})
					.filter((item: any) => !isEmpty(item))

				if (!newMessage?.length) {
					console.log("No need to update message list")
					return
				}
				const hasAttachments = newMessage.some(
					(item: any) =>
						item?.attachments?.length > 0 || item?.tool?.attachments?.length > 0,
				)
				if (hasAttachments) {
					pubsub.publish("update_attachments", {
						conversation_id,
						chat_topic_id,
					})
				}
				if (updatePageToken && res?.page_token) {
					console.log("Update page_token", res?.page_token)
					topicPageTokenMap.current[chat_topic_id] = res?.page_token
				}

				setTopicMessagesMap((pre) => {
					// If pulling latest messages, no need to update page_token

					// Get existing messages and new messages
					const existingMessages = pre?.[chat_topic_id] || []

				// Sort messages by send_timestamp
					const sortMessagesByTimestamp = (msgArray: any[]) => {
						// First filter out duplicate seq_id
						const uniqueMessages = Array.from(
							new Map(msgArray.map((item) => [item.seq_id, item])).values(),
						)

						// Sort by seq_id in ascending order
						return uniqueMessages.sort((a, b) => {
							const seqIdA = BigInt(a.seq_id || "0")
							const seqIdB = BigInt(b.seq_id || "0")
							return Number(seqIdA - seqIdB)
						})
					}

					const combinedMessages = [...existingMessages, ...newMessage].filter(
						(item) => item?.seq_id,
					)
					// Sort first
					const sortedMessages = sortMessagesByTimestamp(combinedMessages)
					// if (updatePageToken && page_token) {
					topicNotHaveMoreMessageMap.current[chat_topic_id] = !res.has_more
					// }
					return {
						...pre,
						[chat_topic_id]: sortedMessages,
					}
				})
			})
		},
		[setTopicMessagesMap, topicNotHaveMoreMessageMap],
	)

	useEffect(() => {
		if (selectedThreadInfo?.id && selectedWorkspace) {
			pullMessage({
				conversation_id: selectedWorkspace?.conversation_id || "",
				chat_topic_id: selectedThreadInfo?.chat_topic_id,
				page_token: "",
				order: "desc",
				limit: 100,
				updatePageToken: true,
			})
		}
	}, [selectedThreadInfo, selectedWorkspace])

	// Handle workspace selection
	const handleWorkspaceSelect = useCallback(
		(workspace: Workspace) => {
			setSelectedWorkspace(workspace)

			// If current workspace has topics list and no topic is selected, select the first topic
			if (workspace.topics.length > 0 && !selectedThreadInfo?.id) {
				// Select the first topic
				setSelectedThreadInfo(workspace.topics[0])
			} else if (workspace.topics.length > 0) {
				// Check if the currently selected topic belongs to this workspace
				const isSelectedTopicInWorkspace = workspace.topics.some(
					(topic) => topic.id === selectedThreadInfo?.id,
				)

				// If selected topic is not in current workspace, select the first topic of this workspace
				if (!isSelectedTopicInWorkspace) {
					setSelectedThreadInfo(workspace.topics[0])
				}
			} else {
				// If no topics, clear selection state
				setSelectedThreadInfo(null)
			}
		},
		[selectedThreadInfo, setSelectedWorkspace, setSelectedThreadInfo],
	)
	// Initialize topic message map when workspace data changes
	useEffect(() => {
		initializeTopicMessages(workspaces)
		if (isEmpty(selectedWorkspace) && !isEmpty(workspaces)) {
			setSelectedWorkspace(workspaces?.[0])
		}
		if (isEmpty(workspaces)) {
			setSelectedWorkspace(null)
			setSelectedThreadInfo(null)
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [workspaces, selectedWorkspace])

	const handlePullMoreMessage = useCallback(
		(threadInfo: any) => {
			if (selectedWorkspace) {
				pullMessage({
					conversation_id: selectedWorkspace?.conversation_id,
					chat_topic_id: threadInfo?.chat_topic_id,
					page_token: topicPageTokenMap.current[threadInfo?.chat_topic_id] || "",
					order: "desc",
					limit: 100,
					updatePageToken: true,
				})
			}
		},
		[selectedWorkspace, pullMessage, topicPageTokenMap],
	)

	// When selected workspace changes, try to select a topic from it
	useEffect(() => {
		if (selectedWorkspace) {
			// Check if the currently selected topic belongs to this workspace
			const isSelectedTopicInWorkspace = selectedThreadInfo?.id
				? selectedWorkspace.topics.some((topic) => topic.id === selectedThreadInfo?.id)
				: false

			if (!isSelectedTopicInWorkspace && selectedWorkspace.topics.length > 0) {
				// If current selected topic is not in this workspace and workspace has topics, select the first topic
				setSelectedThreadInfo(selectedWorkspace.topics[0])
			} else if (!selectedThreadInfo?.id && selectedWorkspace.topics.length > 0) {
				// If no topic is selected and workspace has topics, select the first topic
				setSelectedThreadInfo(selectedWorkspace.topics[0])
			} else if (selectedWorkspace.topics.length === 0) {
				// If workspace has no topics, clear selection state
				setSelectedThreadInfo(null)
			}
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [selectedWorkspace])

	useEffect(() => {
		pubsub.subscribe("update_attachments", (data: any) => {
			if (data?.chat_topic_id === selectedThreadInfo.chat_topic_id) {
				updateAttachments(selectedThreadInfo)
			}
		})
		return () => {
			pubsub?.unsubscribe("update_attachments")
		}
	}, [selectedThreadInfo])

	useEffect(() => {
		pubsub.subscribe("be_delightful_new_message", (data: any) => {
			const { topic_id: chat_topic_id = "" } = data.message || {}
			pullMessage({
				conversation_id: data.conversation_id,
				chat_topic_id,
				page_token: "",
				order: "desc",
				limit: 10,
				updatePageToken: false,
			})
		})
		return () => {
			pubsub?.unsubscribe("be_delightful_new_message")
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	// Clean up when component unmounts
	useEffect(() => {
		return () => {
			// Clear topic_id and page_token mapping
			topicPageTokenMap.current = {}
		}
	}, [])

	// Listen for selectedThreadInfo changes to ensure topicNotHaveMoreMessageMap is reset when switching topics

	// Initial load of workspace data
	useEffect(() => {
		fetchWorkspaces()
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [])

	// When message list changes, find the last message with task and task.process length not 0
	useEffect(() => {
		if (messages && messages.length > 0) {
			// Traverse from back to front to find the first message matching the condition
			let foundTaskData = false
			for (let i = messages.length - 1; i >= 0; i -= 1) {
				const message = messages[i]
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
			// If no matching message is found, clear TaskData
			if (!foundTaskData) {
				setTaskData(null)
			}
		} else {
			// If message list is empty, also clear TaskData
			setTaskData(null)
		}
	}, [messages])

	const handleThreadSelect = useCallback(
		(thread: any) => {
			setSelectedThreadInfo(thread)
		},
		[setSelectedThreadInfo],
	)

	// Wrap message sending handler function
	const handleSendMsg = useCallback(
		(content: string, options: any) => {
			handleSendMessage({
				content: content?.trim(),
				showLoading: messages?.length > 1 && showLoading,
				selectedWorkspace,
				options,
			})
			if (messages.length === 0) {
				setTopicModeInfo(options?.instructs?.[0]?.value || "")
			}
			if (content?.trim() && messages.length === 0) {
				// When message length is 0, update current topic's task_status to running
				setTimeout(() => {
					setWorkspaces((prev: Workspace[]) =>
						prev.map((workspace) => {
							// Check if this is the currently selected workspace
							if (workspace.id === selectedWorkspace?.id) {
								// Update topics array within workspace
								const updatedTopics = workspace.topics.map((topic) => {
									// Find matching topic and update its task_status
									if (topic.id === selectedThreadInfo?.id) {
										return {
											...topic,
											task_status: "running" as const,
										}
									}
									return topic
								})

								return {
									...workspace,
									topics: updatedTopics,
								}
							}
							return workspace
						}),
					)
				}, 2000)
				smartTopicRename({
					id: selectedThreadInfo?.id,
					user_question: content.trim(),
				}).then((res: any) => {
					const { topic_name } = res
					topic_name && fetchWorkspaces()
					setSelectedThreadInfo((pre: any) => {
						return {
							...pre,
							topic_name,
						}
					})
				})
			}
		},
		[
			handleSendMessage,
			showLoading,
			messages,
			selectedWorkspace,
			selectedThreadInfo,
			setWorkspaces,
			fetchWorkspaces,
		],
	)

	const messagePanelProps: MessagePanelProps = {
		onSendMessage: handleSendMsg,
		fileList,
		setFileList,
		taskData,
		showLoading,
		selectedThreadInfo,
		isEmptyStatus,
	}
	const updateTopicListStatus = useCallback(() => {
		if (selectedThreadInfo?.id && selectedWorkspace?.id) {
			getTopicsByWorkspaceId({ id: selectedWorkspace.id, page: 1, page_size: 999 }).then(
				(res: any) => {
					setWorkspaces((pre: any) => {
						return pre?.map((item: any) => {
							if (item.id === selectedWorkspace.id) {
								return {
									...item,
									topics: res?.list,
								}
							}
							return item
						})
					})
				},
			)
		}
	}, [selectedThreadInfo, selectedWorkspace, setWorkspaces])

	useEffect(() => {
		updateTopicListStatus()
	}, [selectedThreadInfo, showLoading, selectedWorkspace, updateTopicListStatus])

	// Add timer to periodically update topic list status
	useEffect(() => {
		const timer = setInterval(() => {
			updateTopicListStatus()
		}, 5000)

		return () => {
			clearInterval(timer)
		}
	}, [updateTopicListStatus])

	console.log("isEmptyStatus-->", isEmptyStatus)
	return isMobile ? (
		<BeDelightfulMobileWorkSpace
			workspaces={workspaces}
			selectedWorkspace={selectedWorkspace}
			fetchWorkspaces={fetchWorkspaces}
			setSelectedWorkspace={setSelectedWorkspace}
			setFileList={setFileList}
			fileList={fileList}
			setSelectedThreadInfo={handleThreadSelect}
			selectedThreadInfo={selectedThreadInfo}
			handlePullMoreMessage={handlePullMoreMessage}
			messages={messages}
			showLoading={showLoading}
			taskData={taskData}
			attachments={attachments}
			setWorkspaces={setWorkspaces}
			isEmptyStatus={isEmptyStatus}
			handleSendMessage={handleSendMsg}
			topicModeInfo={topicModeInfo}
		/>
	) : (
		<div className={styles.container}>
			<WorkspaceHeader
				workspaces={workspaces}
				selectedWorkspace={selectedWorkspace}
				editingWorkspaceId={editingWorkspaceId}
				editingName={editingName}
				isAddingWorkspace={isAddingWorkspace}
				onWorkspaceSelect={handleWorkspaceSelect}
				onInputChange={handleInputChange}
				onInputBlur={handleInputBlur}
				onInputKeyDown={handleInputKeyDown}
				onStartEditWorkspace={handleStartEditWorkspace}
				onStartAddWorkspace={handleStartAddWorkspace}
				onDeleteWorkspace={handleDeleteWorkspaceWithConfirm}
			/>
			<div className={styles.mainContent}>
				<div className={styles.workspacePanel}>
					<WorkspacePanel
						workspaces={workspaces}
						selectedWorkspaceId={selectedWorkspace?.id || null}
						selectedThreadInfo={selectedThreadInfo}
						onSelectThread={handleThreadSelect}
						onWorkspacesChange={setWorkspaces}
						setSelectedThreadInfo={setSelectedThreadInfo}
						attachments={attachments}
						setUserSelectDetail={setUserSelectDetail}
					/>
				</div>
				{!isEmptyStatus && (
					<div className={styles.detailPanel}>
						<Detail
							disPlayDetail={disPlayDetail}
							userSelectDetail={userSelectDetail}
							setUserSelectDetail={setUserSelectDetail}
							attachments={attachments}
						/>
					</div>
				)}
				<div
					className={cx(
						styles.messagePanelWrapper,
						isEmptyStatus && styles.emptyMessagePanel,
					)}
					style={
						isEmptyStatus
							? undefined
							: { paddingBottom: taskData?.process.length ? 224 : 185 }
					}
				>
					{isEmptyStatus ? (
						<EmptyWorkspacePanel messagePanelProps={messagePanelProps} />
					) : (
						<>
							<MessageList
								data={messages}
								setSelectedDetail={setUserSelectDetail}
								selectedThreadInfo={selectedThreadInfo}
								className={cx(isEmptyStatus && styles.emptyMessageWelcome)}
								handlePullMoreMessage={handlePullMoreMessage}
								showLoading={showLoading}
							/>
							<MessagePanel
								{...messagePanelProps}
								className={cx(
									isEmptyStatus && styles.emptyMessageInput,
									!isEmptyStatus && styles.messagePanel,
								)}
								textAreaWrapperClassName={styles.emptyMessageTextAreaWrapper}
								topicModeInfo={topicModeInfo}
							/>
						</>
					)}
				</div>
			</div>
		</div>
	)
}

// Exported workspace component
export default observer(MainWorkspaceContent)

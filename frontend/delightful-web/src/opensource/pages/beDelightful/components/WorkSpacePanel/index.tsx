import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import ShareModal from "@/opensource/pages/beDelightful/components/Share/Modal"
import { ResourceType, ShareType } from "@/opensource/pages/beDelightful/components/Share/types"
import {
	createShareTopic,
	deleteThread,
	editThread,
	getTopicsByWorkspaceId,
} from "@/opensource/pages/beDelightful/utils/api"
import { IconChevronDown, IconChevronRight, IconDots, IconPlus } from "@tabler/icons-react"
import type { InputRef, MenuProps } from "antd"
import { Button, Dropdown, Input, message, Modal, Tooltip, Typography } from "antd"
import { cx } from "antd-style"
import type { ChangeEvent, KeyboardEvent, MouseEvent } from "react"
import { useCallback, useEffect, useMemo, useRef, useState } from "react"
import type { FileItem, Thread, Workspace } from "../../pages/Workspace/types"
import AttachmentList from "../AttachmentList"

import TopicIcon from "../TopicIcon"
import useStyles from "./style"

const { Text } = Typography

interface WorkspacePanelProps {
	workspaces: Workspace[]
	onWorkspacesChange: (workspaces: Workspace[]) => void
	onSelectThread?: (thread: any) => void
	selectedWorkspaceId: string | null
	selectedThreadInfo?: any
	attachments?: FileItem[]
	setUserSelectDetail?: (detail: any) => void
	setSelectedThreadInfo?: (thread: any) => void
}

const WorkspacePanel = ({
	workspaces,
	onWorkspacesChange,
	onSelectThread,
	selectedWorkspaceId,
	selectedThreadInfo,
	attachments,
	setUserSelectDetail,
	setSelectedThreadInfo,
}: WorkspacePanelProps) => {
	const { styles } = useStyles()
	const [editingThreadId, setEditingThreadId] = useState<string | null>(null)
	const [editingWorkspaceParentId, setEditingWorkspaceParentId] = useState<string | null>(null)
	const [editingName, setEditingName] = useState("")
	const [isCollapsed, setIsCollapsed] = useState(false)
	const [threadSearchText, setThreadSearchText] = useState("")
	const [shareModalVisible, setShareModalVisible] = useState(false)
	const [currentThread, setCurrentThread] = useState<Thread | null>(null)

	const inputRef = useRef<InputRef>(null)

	const shareTypes = useMemo(() => [ShareType.OnlySelf, ShareType.Internet], [])
	// By default, select the first workspace and first topic when component mounts
	useEffect(() => {
		if (workspaces.length > 0 && onSelectThread && selectedWorkspaceId) {
			// When workspace is selected but no topic is selected
			const currentWorkspace = workspaces.find((ws) => ws.id === selectedWorkspaceId)

			if (currentWorkspace && !selectedThreadInfo?.id && currentWorkspace.topics.length > 0) {
				// If no topic is selected, select the first topic
				const firstTopic = currentWorkspace.topics[0]
				onSelectThread(firstTopic)
			}
		}
	}, [workspaces, selectedWorkspaceId, selectedThreadInfo, onSelectThread])

	useEffect(() => {
		if (inputRef.current && editingThreadId) {
			inputRef.current.focus()
		}
	}, [editingThreadId])

	const handleRenameThread = (workspaceId: string, threadId: string, name: string) => {
		const trimmedName = name.trim()
		onWorkspacesChange(
			workspaces.map((ws) =>
				ws.id === workspaceId
					? {
							...ws,
							topics: ws.topics.map((thread) =>
								thread.id === threadId
									? { ...thread, topic_name: trimmedName }
									: thread,
							),
					  }
					: ws,
			),
		)
	}

	const handleDeleteThread = useCallback(
		(workspaceId: string, threadId: string) => {
			deleteThread({ id: threadId, workspace_id: workspaceId })
				.then(() => {
					const updatedWorkspaces = workspaces.map((ws) =>
						ws.id === workspaceId
							? {
									...ws,
									topics: ws.topics.filter((thread) => thread.id !== threadId),
							  }
							: ws,
					)

					onWorkspacesChange(updatedWorkspaces)
				// Auto-select the first topic only when deleting the currently selected topic
					if (threadId === selectedThreadInfo?.id && onSelectThread) {
						const updatedWorkspace = updatedWorkspaces.find(
							(ws) => ws.id === workspaceId,
						)
						if (updatedWorkspace && updatedWorkspace.topics.length > 0) {
							onSelectThread(updatedWorkspace.topics[0])
						} else if (updatedWorkspace && updatedWorkspace.topics.length === 0) {
						// Set selected topic to null when workspace has no remaining topics
							onSelectThread(null as any)
						}
					}
				})
				.catch((err) => {
					console.log(err, "err")
				})
		},
		[workspaces, selectedThreadInfo, onSelectThread, onWorkspacesChange],
	)

	const handleSelectThreadInternal = (thread: any) => {
		if (onSelectThread) {
			onSelectThread(thread)
		}
	}

	const handleStartEditThread = (workspaceId: string, thread: Thread, e: MouseEvent) => {
		e.stopPropagation()
		setEditingThreadId(thread.id)
		setEditingWorkspaceParentId(workspaceId)
		setEditingName(thread.topic_name)
	}

	const handleStartAddThread = (workspaceId: string, e: MouseEvent) => {
		e.stopPropagation()

		// Directly create a new topic without user input
		editThread({
			topic_name: "New Topic",
			workspace_id: workspaceId,
		})
			.then((res: any) => {
				console.log("Create new topic result:", res)
				// Get the latest topic list regardless of the result
				getTopicsByWorkspaceId({ id: workspaceId || "", page: 1, page_size: 999 })
					.then((topicsRes: any) => {
						console.log(topicsRes, "topicsResponsetopicsResponse")
						const newTopic = topicsRes?.list.find((topic: any) => topic?.id === res?.id)
						console.log(newTopic, "newTopicnewTopicnewTopic")
						if (setSelectedThreadInfo && newTopic) {
							setSelectedThreadInfo(newTopic)
						}
					console.log("Get topics list result:", topicsRes)

					// First update the workspace's topic list
						const updatedWorkspaces = workspaces.map((ws) =>
							ws.id === workspaceId
								? {
										...ws,
										topics: [...topicsRes.list],
								  }
								: ws,
						)
						onWorkspacesChange(updatedWorkspaces)
					})
					.catch((err) => {
						console.error("Failed to get topic list:", err)
					})
			})
			.catch((err) => {
				console.error("Failed to create topic:", err)
			})
	}

	const resetEditing = () => {
		setEditingThreadId(null)
		setEditingWorkspaceParentId(null)
		setEditingName("")
	}

	const handleSave = () => {
		const trimmedName = editingName.trim()
		if (trimmedName === "") return
		if (editingThreadId && editingWorkspaceParentId) {
			editThread({
				workspace_id: editingWorkspaceParentId,
				topic_name: trimmedName,
				id: editingThreadId,
			}).then(() => {
				handleRenameThread(editingWorkspaceParentId, editingThreadId, trimmedName)
			})
		}
		resetEditing()
	}

	const handleInputChange = (e: ChangeEvent<HTMLInputElement>) => {
		setEditingName(e.target.value)
	}

	const handleInputKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
		if (e.key === "Enter") {
			handleSave()
		} else if (e.key === "Escape") {
			resetEditing()
		}
	}

	const handleInputBlur = () => {
		setTimeout(() => {
			const trimmedName = editingName.trim()
			if (trimmedName !== "") {
				handleSave()
			} else {
				resetEditing()
			}
		}, 100)
	}

	const handleShareThread = (workspaceId: string, thread: Thread) => {
		console.log("handleShareThread", { workspaceId, thread })
		setCurrentThread(thread)
		setShareModalVisible(true)
	}

	const handleShareModalClose = () => {
		console.log("handleShareModalClose")
		setShareModalVisible(false)
		setCurrentThread(null)
	}

	const handleShareSave = ({ type, extraData }: { type: ShareType; extraData: any }) => {
		// Implement logic to save share settings
		// message.success("Share settings saved")
		console.log("handleShareSave")
		const data = {
			resource_id: currentThread?.id || "",
			resource_type: ResourceType.Topic,
			share_type: type,
		} as any
		if (extraData.passwordEnabled) {
			data.pwd = extraData.password
		}
		createShareTopic(data)
			.then((res: any) => {
				message.success("Share settings saved")
				console.log("Create share topic result:", res)
				// setShareModalVisible(false)
				// setCurrentThread(null)
			})
			.catch((err: any) => {
				message.error("Failed to create share topic")
				console.error("Failed to create share topic:", err)
			})
	}

	const getThreadMenu = (workspaceId: string, thread: Thread): MenuProps["items"] => [
		{
			key: "share",
			label: "Share",
			onClick: (info: any) => {
				info.domEvent.stopPropagation()
				handleShareThread(workspaceId, thread)
			},
		},
		{
			key: "rename",
			label: "Rename",
			onClick: (info: any) => {
				handleStartEditThread(workspaceId, thread, info.domEvent as MouseEvent)
			},
		},
		{
			key: "delete",
			label: "Delete",
			danger: true,
			onClick: (info) => {
				info.domEvent.stopPropagation()
				Modal.confirm({
					title: "Confirm Delete",
					content: `Are you sure you want to delete the conversation "${thread.topic_name}"?`,
					onOk: () => handleDeleteThread(workspaceId, thread.id),
				})
			},
		},
	]

	const selectedWorkspace = workspaces.find((ws) => ws.id === selectedWorkspaceId)

	// Filter topics list
	const filteredThreads = useMemo(() => {
		if (!selectedWorkspace?.topics) return []
		if (!threadSearchText.trim()) return selectedWorkspace.topics

		return selectedWorkspace.topics.filter((thread) =>
			(thread.topic_name || "").toLowerCase().includes(threadSearchText.toLowerCase()),
		)
	}, [selectedWorkspace?.topics, threadSearchText])

	return (
		<div className={styles.container}>
			<div className={styles.threadContainer}>
				{/* Topics list area */}
				<div className={cx(styles.section, isCollapsed && styles.collapsed)}>
					<div className={styles.header}>
						<div className={styles.titleContainer}>
							<Button
								type="text"
								size="small"
								icon={
									<DelightfulIcon
										size={18}
										component={(isCollapsed ? IconChevronRight : IconChevronDown) as any}
										stroke={2}
									/>
								}
								onClick={() => {
									setIsCollapsed(!isCollapsed)
								}}
								className={styles.iconButton}
							/>
							<span>Topic List</span>
						</div>
					</div>
					{!isCollapsed && (
						<div className={styles.content}>
							{selectedWorkspace && (
								<>
									<div className={styles.searchContainer}>
										<Input
										placeholder="Search topics"
											value={threadSearchText}
											onChange={(e) => setThreadSearchText(e.target.value)}
											className={styles.searchInput}
										/>
									</div>

									<div
										className={styles.newTopicButton}
										onClick={(e) =>
											handleStartAddThread(selectedWorkspace.id, e)
										}
									>
										<DelightfulIcon
											size={18}
										component={IconPlus as any}
											stroke={2}
											className={styles.newTopicButtonIcon}
										/>
										<span>New Topic</span>
									</div>
									<div className={styles.listContainer}>
										{filteredThreads.map((thread) => {
												return (
													<div
														key={thread.id}
														className={
															thread.id === selectedThreadInfo?.id
																? styles.threadItemSelected
																: styles.threadItem
														}
														onClick={() =>
															handleSelectThreadInternal(thread)
														}
													>
														<div className={styles.threadTitle}>
															<TopicIcon
																status={thread.task_status}
																className={styles.threadTitleImage}
															/>
															{editingThreadId === thread.id &&
															editingWorkspaceParentId ===
																selectedWorkspace.id ? (
																<Input
																	ref={inputRef}
																	className={styles.inlineInput}
																	value={editingName || ""}
																	onChange={handleInputChange}
																	onBlur={handleInputBlur}
																	onKeyDown={handleInputKeyDown}
																	autoFocus
																/>
															) : (
																<Tooltip
																	title={thread.topic_name}
																	placement="right"
																>
																	<Text
																		className={styles.ellipsis}
																	>
																		{thread.topic_name}
																	</Text>
																</Tooltip>
															)}
														</div>

														{!(
															editingThreadId === thread.id &&
															editingWorkspaceParentId ===
																selectedWorkspace.id
														) && (
															<Dropdown
																menu={{
																	items: getThreadMenu(
																		selectedWorkspace.id,
																		thread,
																	),
																}}
																trigger={["click"]}
															>
																<Button
																	className={
																		styles.threadItemMoreButton
																	}
																	type="text"
																	size="small"
																	onClick={(e) =>
																		e.stopPropagation()
																	}
																>
																	<DelightfulIcon
																		size={18}
																	component={IconDots as any}
																		stroke={2}
																		className={
																			styles.threadItemMoreButtonIcon
																		}
																	/>
																</Button>
															</Dropdown>
														)}
													</div>
												)
											})}
										</div>
										{filteredThreads.length === 0 && threadSearchText && (
											<div className={styles.emptyText}>No related topics found</div>
										)}
									</>
								)}
							</div>
						)}
					</div>

				{/* Topic files area */}
				<AttachmentList
					attachments={attachments || []}
					setUserSelectDetail={setUserSelectDetail}
				/>
			</div>
			{/* Share modal */}
			{currentThread && (
				<ShareModal
					open={shareModalVisible}
					types={shareTypes}
					shareContext={{
						resource_id: currentThread.id,
						resource_type: ResourceType.Topic,
					}}
					afterSubmit={handleShareSave}
					onCancel={handleShareModalClose}
				/>
			)}
		</div>
	)
}

export default WorkspacePanel

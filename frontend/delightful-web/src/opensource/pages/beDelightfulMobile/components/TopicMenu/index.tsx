import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import AttachmentList from "@/opensource/pages/beDelightful/components/AttachmentList"
import ShareModal from "@/opensource/pages/beDelightful/components/Share/Modal"
import { ResourceType, ShareType } from "@/opensource/pages/beDelightful/components/Share/types"
import { createShareTopic } from "@/opensource/pages/beDelightful/utils/api"
import { IconEdit, IconFolder, IconShare, IconTrash } from "@tabler/icons-react"
// import UserMenus from "@/opensource/layouts/BaseLayout/components/Sider/components/UserMenus"
import { deleteThread, editThread } from "@/opensource/pages/beDelightful/utils/api"
import { message } from "antd"
import { Input, Popup, SafeArea } from "antd-mobile"
import { memo, useCallback, useMemo, useState } from "react"
import { useStyles } from "./style"

export default memo(function TopicMenu({
	selectedThreadInfo,
	attachments,
	setUserSelectDetail,
	selectedWorkspace,
	fetchWorkspaces,
	setSelectedThreadInfo,
	workspaces,
	setWorkspaces,
}: any) {
	const { styles, cx } = useStyles()
	const [shareModalVisible, setShareModalVisible] = useState(false)
	const [attachmentVisible, setAttachmentVisible] = useState(false)
	const [renameModalVisible, setRenameModalVisible] = useState(false)
	const [deleteModalVisible, setDeleteModalVisible] = useState(false)
	const [newTopicName, setNewTopicName] = useState("")
	const shareTypes = useMemo(() => [ShareType.OnlySelf, ShareType.Internet], [])
	const [currentThread, setCurrentThread] = useState<any | null>(null)
	const handleShareModalClose = () => {
		setShareModalVisible(false)
		setCurrentThread(null)
	}

	const handleShareSave = ({ type, extraData }: { type: ShareType; extraData: any }) => {
		// Implement save share settings logic
		// message.success("Share settings saved")
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
				// setShareModalVisible(false)
				// setCurrentThread(null)
			})
			.catch((err: any) => {
				message.error("Failed to create shared topic")
				console.error("Failed to create shared topic:", err)
			})
	}
	const handleRename = useCallback(() => {
		if (!selectedThreadInfo) return
		setNewTopicName(selectedThreadInfo.topic_name || "")
		setRenameModalVisible(true)
	}, [selectedThreadInfo])

	const handleRenameConfirm = useCallback(() => {
		if (!selectedThreadInfo || !newTopicName.trim()) {
			message.error("Topic name cannot be empty")
			return
		}

		editThread({
			id: selectedThreadInfo.id,
			topic_name: newTopicName.trim(),
			workspace_id: selectedWorkspace.workspace_id,
		})
			.then(() => {
				fetchWorkspaces()
				message.success("Rename successful")
				setRenameModalVisible(false)
				setSelectedThreadInfo((pre: any) => {
					return {
						...pre,
						topic_name: newTopicName.trim(),
					}
				})
			})
			.catch((err) => {
				message.error("Rename failed")
				console.error("Failed to rename topic:", err)
			})
	}, [selectedThreadInfo, newTopicName, selectedWorkspace])

	const handleDelete = useCallback(() => {
		if (!selectedThreadInfo) return
		setDeleteModalVisible(true)
	}, [selectedThreadInfo])

	const handleDeleteConfirm = useCallback(() => {
		if (!selectedThreadInfo) return
		handleDeleteThread(selectedWorkspace.id, selectedThreadInfo.id)
	}, [selectedWorkspace, selectedThreadInfo, fetchWorkspaces, workspaces])

	const handleDeleteThread = useCallback(
		(workspaceId: string, threadId: string) => {
			deleteThread({ id: threadId, workspace_id: workspaceId })
				.then(() => {
					const updatedWorkspaces = workspaces.map((ws: any) =>
						ws.id === workspaceId
							? {
									...ws,
									topics: ws.topics.filter(
										(thread: any) => thread.id !== threadId,
									),
							  }
							: ws,
					)

					setWorkspaces(updatedWorkspaces)
					console.log(
						threadId,
						"deletethreadId",
						selectedThreadInfo,
						"selectedThreadInfoxxxxx",
						updatedWorkspaces,
						"updatedWorkspacesupdatedWorkspaces",
						workspaceId,
						"workspaceId",
					)
					// Only auto-select the first topic when deleting the currently selected topic
					if (threadId === selectedThreadInfo?.id && setSelectedThreadInfo) {
						const updatedWorkspace = updatedWorkspaces.find(
							(ws: any) => ws.id === workspaceId,
						)
						if (updatedWorkspace && updatedWorkspace.topics.length > 0) {
							setSelectedThreadInfo(updatedWorkspace.topics[0])
						} else if (updatedWorkspace && updatedWorkspace.topics.length === 0) {
							// When the workspace has no remaining topics, set the selected topic to null
							setSelectedThreadInfo(null as any)
						}
					}
					message.success("Delete successful")
					setDeleteModalVisible(false)
				})
				.catch((err) => {
					console.log(err, "err")
				})
		},
		[workspaces, selectedThreadInfo, setSelectedThreadInfo, setWorkspaces],
	)

	return (
		<div className={styles.container}>
			<div className={styles.title}>Topic</div>
			<div>
				<div
					className={cx(styles.item)}
					onClick={() => {
						setAttachmentVisible(true)
					}}
				>
					<IconFolder className={styles.icon} /> <span>View topic files</span>
				</div>
				<div
					className={styles.item}
					onClick={() => {
						setCurrentThread(selectedThreadInfo)
						setShareModalVisible(true)
					}}
				>
					<IconShare /> <span>Share topic</span>
				</div>
				<div className={styles.item} onClick={handleRename}>
					<IconEdit className={styles.icon} /> <span>Rename</span>
				</div>
				<div className={styles.item} onClick={handleDelete}>
					<IconTrash className={styles.icon} /> <span>Delete topic</span>
				</div>
			</div>
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
			<Popup
				visible={attachmentVisible}
				onMaskClick={() => {
					setAttachmentVisible(false)
				}}
				bodyStyle={{ height: "90%", backgroundColor: "#fff" }}
			>
				<SafeArea position="top" />
				<div className={styles.attachmentList}>
					<AttachmentList
						attachments={attachments}
						setUserSelectDetail={setUserSelectDetail}
					/>
				</div>
				<SafeArea position="bottom" />
			</Popup>
			<DelightfulModal
				title="Rename topic"
				onCancel={() => {
					setRenameModalVisible(false)
				}}
				onOk={handleRenameConfirm}
				open={renameModalVisible}
			>
				<div style={{ padding: "10px" }}>
					<Input
						placeholder="Please enter topic name"
						value={newTopicName}
						onChange={(val) => setNewTopicName(val)}
						autoFocus
					/>
				</div>
			</DelightfulModal>
			{/* <Modal
				visible={renameModalVisible}
				bodyStyle={{ backgroundColor: "#fff" }}
				content={
					<div style={{ padding: "10px" }}>
						<Input
							placeholder="Please enter topic name"
							value={newTopicName}
							onChange={(val) => setNewTopicName(val)}
							autoFocus
						/>
					</div>
				}
				getContainer={() => {
					return document.querySelector("body") || document.body
				}}
				closeOnAction
				onClose={() => setRenameModalVisible(false)}
				actions={[
					{
						key: "cancel",
						text: (
							<span style={{ backgroundColor: "#fff", color: "#1677ff" }}>Cancel</span>
						),
						onClick: () => setRenameModalVisible(false),
					},
					{
						key: "confirm",
						text: (
							<span style={{ backgroundColor: "#1677ff", color: "#fff" }}>Confirm</span>
						),
						primary: true,
						onClick: handleRenameConfirm,
					},
				]}
			/> */}
			<DelightfulModal
				title={`Delete topic`}
				onCancel={() => {
					setDeleteModalVisible(false)
				}}
				onOk={handleDeleteConfirm}
				open={deleteModalVisible}
				okButtonProps={{ danger: true }}
			>
				{`Are you sure you want to delete topic: ${selectedThreadInfo?.topic_name}?`}
			</DelightfulModal>
			{/* <Modal
				visible={deleteModalVisible}
				bodyStyle={{ backgroundColor: "#fff" }}
				content={
					<div style={{ padding: "10px" }}>
						<div>Are you sure you want to delete topic: {selectedThreadInfo?.topic_name}?</div>
					</div>
				}
				getContainer={() => {
					return document.querySelector("body") || document.body
				}}
				closeOnAction
				onClose={() => setDeleteModalVisible(false)}
				actions={[
					{
						key: "cancel",
						text: <span style={{ color: "#1677ff" }}>Cancel</span>,
						onClick: () => setDeleteModalVisible(false),
					},
					{
						key: "confirm",
						text: <span style={{ color: "#ff4d4f" }}>Confirm Delete</span>,
						danger: true,
						onClick: handleDeleteConfirm,
					},
				]}
			/> */}
		</div>
	)
})

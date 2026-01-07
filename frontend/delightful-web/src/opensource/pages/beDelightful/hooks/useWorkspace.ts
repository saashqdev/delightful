import { useCallback, useState } from "react"
import type { Workspace } from "../pages/Workspace/types"
import { deleteWorkspace, editWorkspace, getWorkspaces } from "../utils/api"

export function useWorkspace() {
	const [workspaces, setWorkspaces] = useState<Workspace[]>([])
	const [selectedWorkspace, setSelectedWorkspace] = useState<Workspace | null>(null)
	const [editingWorkspaceId, setEditingWorkspaceId] = useState<string | null>(null)
	const [editingName, setEditingName] = useState("")
	const [isAddingWorkspace, setIsAddingWorkspace] = useState(false)

	// Reset edit state
	const resetEditing = useCallback(() => {
		setEditingWorkspaceId(null)
		setEditingName("")
		setIsAddingWorkspace(false)
	}, [])

	// Handle workspace name input
	const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
		setEditingName(e.target.value)
	}, [])

	// Workspace related operations
	const handleRenameWorkspace = useCallback((id: string, name: string) => {
		setWorkspaces((prev) => {
			return prev.map((ws) => (ws.id === id ? { ...ws, name } : ws))
		})
	}, [])

	const handleAddWorkspace = useCallback((name: string, id?: string) => {
		const trimmedName = name.trim()
		const newWorkspaceId = id || `workspace-${Date.now()}`
		const newWorkspace: any = {
			id: newWorkspaceId,
			name: trimmedName,
			topics: [
				{
					id: id || `thread-${Date.now() + 1}`,
					topic_name: "Default Topic",
					sort: "1",
					history: [],
					attachments: [],
				},
			],
			expanded: true,
		}
		setWorkspaces((prev) => [...prev, newWorkspace])
		setSelectedWorkspace(newWorkspace)
	}, [])

	// Load workspace data
	const fetchWorkspaces = useCallback(
		(selectedThreadId?: string | null) => {
			return getWorkspaces()
				.then((data: any) => {
					// Save currently selected topic ID
					const currentThreadId = selectedThreadId
					const currentWorkspaceId = selectedWorkspace?.id

					if (currentThreadId && currentWorkspaceId) {
						// If topic and workspace are already selected, keep the selected state
						const updatedWorkspaces = data.list.map((workspace: Workspace) => {
							if (workspace.id === currentWorkspaceId) {
								return {
									...workspace,
									topics: workspace.topics.map((topic) => ({
										...topic,
									})),
								}
							}
							return workspace
						})
						setWorkspaces(updatedWorkspaces)
					} else {
						// If no topic and workspace are selected, use default selection behavior
						setWorkspaces(data?.list)
					}
					return data.list
					// If no workspace exists, auto-create a default workspace
					// return createDefaultWorkspace()
				})
				.catch((err) => {
					console.log(err, "err")
					return []
				})
		},
		[selectedWorkspace],
	)

	// Save edit content
	const handleSave = useCallback(() => {
		console.log(editingName, "editingNameeditingName")
		const trimmedName = (editingName || "").trim()
		if (trimmedName === "") return

		if (editingWorkspaceId) {
			editWorkspace({ id: editingWorkspaceId, workspace_name: trimmedName })
				.then(() => {
					console.log(1111111, editingWorkspaceId, "xxx", trimmedName)
					handleRenameWorkspace(editingWorkspaceId, trimmedName)
				})
				.catch((err) => {
					console.log(err, "err")
				})
		}

		resetEditing()
	}, [editingName, editingWorkspaceId, resetEditing, handleRenameWorkspace])

	// Handle input keyboard events
	const handleInputKeyDown = useCallback(
		(e: React.KeyboardEvent<HTMLInputElement>) => {
			if (e.key === "Enter") {
				handleSave()
			} else if (e.key === "Escape") {
				resetEditing()
			}
		},
		[handleSave, resetEditing],
	)

	// Handle input blur event
	const handleInputBlur = useCallback(() => {
		setTimeout(() => {
			console.log(editingName, "handleInputBlur")
			const trimmedName = (editingName || "").trim()
			if (trimmedName !== "") {
				handleSave()
			} else {
				resetEditing()
			}
		}, 100)
	}, [editingName, handleSave, resetEditing])

	const handleStartEditWorkspace = useCallback((workspace: Workspace, e: React.MouseEvent) => {
		e.stopPropagation()
		setEditingWorkspaceId(workspace.id)
		setEditingName(workspace.name || "")
		setIsAddingWorkspace(false)
	}, [])

	const handleStartAddWorkspace = useCallback(() => {
		const defaultWorkspaceName = "New Workspace"
		editWorkspace({ workspace_name: defaultWorkspaceName })
			.then((data: any) => {
				console.log(data, "add workspace")
				if (data?.id) {
					// After creation is complete, get the latest workspace list directly
					getWorkspaces()
						.then((workspacesData: any) => {
							const workspaceList = workspacesData.list || []
							// Update workspace list
							setWorkspaces(workspaceList)
							// 不再自动选中新建的工作区
						})
						.catch((err) => {
							console.log(err, "Failed to get workspace list")
						})
				}
			})
			.catch((err) => {
				console.log(err, "Failed to create workspace")
			})
	}, [])

	const handleDeleteWorkspace = useCallback(
		(id: string) => {
			deleteWorkspace({ id })
				.then(() => {
					setWorkspaces((prev) => {
						const updatedWorkspaces = prev.filter((ws) => ws.id !== id)
						if (selectedWorkspace?.id === id && updatedWorkspaces.length > 0) {
							setSelectedWorkspace(updatedWorkspaces[0])
						}
						return updatedWorkspaces
					})
				})
				.catch((err) => {
					console.log(err, "err")
				})
		},
		[selectedWorkspace],
	)

	// Create default workspace
	// const createDefaultWorkspace = useCallback(() => {
	// 	const defaultWorkspaceName = "Default Workspace"
	// 	return editWorkspace({ workspace_name: defaultWorkspaceName })
	// 		.then((createData: any) => {
	// 			if (createData?.id) {
	// 				// After creation is successful, re-fetch the workspace list
	// 				return getWorkspaces()
	// 					.then((workspacesData: any) => {
	// 						setWorkspaces(workspacesData.data)
	// 						// Set the selected workspace to the first workspace
	// 						const defaultWorkspace = workspacesData.data[0]
	// 						setSelectedWorkspace(defaultWorkspace)

	// 						// If workspace has topics, set the selected topic ID to the first topic's ID
	// 						if (defaultWorkspace.topics && defaultWorkspace.topics.length > 0) {
	// 							return {
	// 								workspaces: workspacesData.data,
	// 								defaultThreadId: defaultWorkspace.topics[0].id,
	// 							}
	// 						}
	// 						return { workspaces: workspacesData.data, defaultThreadId: null }
	// 					})
	// 					.catch((err) => {
	// 						console.log(err, "Error getting workspace list")
	// 						message.error("Failed to get workspace list")
	// 						return { workspaces: [], defaultThreadId: null }
	// 					})
	// 			}
	// 			message.error("Failed to create default workspace")
	// 			return { workspaces: [], defaultThreadId: null }
	// 		})
	// 		.catch((err) => {
	// 			console.log(err, "Error creating default workspace")
	// 			message.error("Failed to create default workspace")
	// 			return { workspaces: [], defaultThreadId: null }
	// 		})
	// }, [])

	return {
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
		handleAddWorkspace,
		handleRenameWorkspace,
		fetchWorkspaces,
		resetEditing,
	}
}

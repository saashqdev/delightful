import { message } from "antd"
import { useEffect, useState } from "react"

export interface DataItem {
	id: string
	name: string
	size: number
	type: string
	uploadTime: string
	workspaceId: string
	workspaceName: string
	url: string
	topicId?: string
	topicName?: string
}

interface UseDataProps {
	searchKeywords?: string
}

export default ({ searchKeywords }: UseDataProps) => {
	const [data, setData] = useState<DataItem[]>([])

	const [loading, setLoading] = useState(false)

	const fetchData = async () => {
		setLoading(true)
		try {
			// This should call API to get marked file list
			// Using mock data for now
			setTimeout(() => {
				// Basic file types and names
				const fileTypes = ["md", "pdf", "docx", "txt"]
				const fileBaseNames = [
					"Requirements",
					"Project Plan",
					"Development Guide",
					"User Agreement",
					"Privacy Policy",
					"Meeting Minutes",
					"Product Design",
					"Development Plan",
					"Test Report",
					"Release Notes",
					"User Guide",
					"Technical Proposal",
					"System Architecture",
					"Data Analysis",
					"Market Research",
				]

				// Workspace names
				const workspaces = [
					{ id: "ws1", name: "Delightful Project" },
					{ id: "ws2", name: "Team Project" },
					{ id: "ws3", name: "Personal Project" },
					{ id: "ws4", name: "R&D Center" },
				]

				// Topic names
				const topics = [
					{ id: "1", name: "Topic 1" },
					{ id: "2", name: "Topic 2" },
					{ id: "3", name: "Topic 3" },
					{ id: "4", name: "Topic 4" },
					{ id: "5", name: "Topic 5" },
					{ id: "6", name: "Topic 6" },
					{ id: "7", name: "Topic 7" },
					{ id: "8", name: "Topic 8" },
					{ id: "9", name: "Topic 9" },
					{ id: "10", name: "Topic 10" },
				]

				// Generate 60 mock data items
				const mockFiles: DataItem[] = []
				for (let i = 0; i < 60; i++) {
					const fileType = fileTypes[Math.floor(Math.random() * fileTypes.length)]
					const baseName = fileBaseNames[Math.floor(Math.random() * fileBaseNames.length)]
					const workspace = workspaces[Math.floor(Math.random() * workspaces.length)]
					const topic = topics[Math.floor(Math.random() * topics.length)]

					// Generate random size (5KB ~ 5MB)
					const size = Math.floor(Math.random() * (5 * 1024 * 1024 - 5 * 1024) + 5 * 1024)

					// Generate random date (within past year)
					const date = new Date()
					date.setDate(date.getDate() - Math.floor(Math.random() * 365))
					const uploadTime = date.toISOString()

					mockFiles.push({
						id: i.toString(),
						name: `${baseName}_${i}.${fileType}`,
						size,
						type: fileType,
						uploadTime,
						workspaceId: workspace.id,
						workspaceName: workspace.name,
						url: "#",
						topicId: topic.id,
						topicName: topic.name,
					})
				}

				setData(mockFiles)
				setLoading(false)
			}, 500)
		} catch (error) {
			message.error("Failed to get marked file list")
			console.error("Failed to fetch starred files:", error)
			setLoading(false)
		}
	}
	useEffect(() => {
		fetchData()
	}, [])

	return {
		data,
		setData,
		loading,
	}
}

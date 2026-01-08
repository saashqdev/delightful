import { IconCircleCheck, IconCircleX, IconAlertCircle } from "@tabler/icons-react"
import { Flex, Spin } from "antd"
import { useState, useEffect, useRef } from "react"
import { LoadingOutlined } from "@ant-design/icons"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import { RoutePath } from "@/const/routes"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import { documentSyncStatusMap } from "../../constant"
import { useVectorKnowledgeEmbedStyles } from "./styles"
import { KnowledgeApi } from "@/apis"
import type { Knowledge } from "@/types/knowledge"
import KnowledgeContent from "./components/KnowledgeContent"
import LoadingState from "./components/LoadingState"

interface VectorKnowledgeEmbedProps {
	knowledgeBaseCode: string
}

export default function VectorKnowledgeEmbed({ knowledgeBaseCode }: VectorKnowledgeEmbedProps) {
	const { styles } = useVectorKnowledgeEmbedStyles()
	const { t } = useTranslation("flow")
	const navigate = useNavigate()

	/** Knowledge base details */
	const [createdKnowledge, setCreatedKnowledge] = useState<Knowledge.Detail>()
	/** Whether embedding is complete */
	const [isEmbed, setIsEmbed] = useState(false)
	/** Knowledge base document list */
	const [documentList, setDocumentList] = useState<Knowledge.EmbedDocumentDetail[]>([])
	/** Polling timer reference */
	const timerRef = useRef<NodeJS.Timeout | null>(null)
	/** Whether data is loaded */
	const [isLoading, setIsLoading] = useState(true)

	/** View knowledge base - navigate to details page */
	const handleViewKnowledge = useMemoizedFn(() => {
		navigate(`${RoutePath.VectorKnowledgeDetail}?code=${knowledgeBaseCode}`)
	})

	/** Get document sync status icon */
	const getStatusIcon = (syncStatus: documentSyncStatusMap) => {
		switch (syncStatus) {
			case documentSyncStatusMap.Pending:
			case documentSyncStatusMap.Processing:
				return <Spin indicator={<LoadingOutlined spin />} />
			case documentSyncStatusMap.Success:
				return <IconCircleCheck className={styles.icon} color="#32C436" size={24} />
			case documentSyncStatusMap.Failed:
				return <IconCircleX color="#FF4D4F" size={24} />
		}
	}

	/** Update knowledge base document list embedding status */
	const updateKnowledgeDocumentList = useMemoizedFn(async () => {
		try {
			const res = await KnowledgeApi.getKnowledgeDocumentList({
				code: knowledgeBaseCode,
			})
			if (res) {
				setDocumentList(res.list)
				setIsEmbed(
					res.list.length > 0 &&
						res.list.every(
							(item: Knowledge.EmbedDocumentDetail) =>
								![
									documentSyncStatusMap.Pending,
									documentSyncStatusMap.Processing,
								].includes(item.sync_status),
						),
				)
			}
		} catch (error) {
			console.error("Failed to get document list:", error)
		}
	})

	/**
	 * Update knowledge base details
	 */
	const getKnowledgeDetail = useMemoizedFn(async (code: string) => {
		try {
			const res = await KnowledgeApi.getKnowledgeDetail(code)
			if (res) {
				setCreatedKnowledge(res)
				setIsLoading(false) // Data loading complete
			}
		} catch (error) {
			console.error("Failed to get knowledge base details", error)
			setIsLoading(false) // Mark as loading complete even on error
		}
	})

	// Get knowledge base details
	useEffect(() => {
		if (knowledgeBaseCode) {
			getKnowledgeDetail(knowledgeBaseCode)
		}
	}, [knowledgeBaseCode])

	/** Start polling */
	const startPolling = useMemoizedFn(() => {
		// Random 2-5 seconds
		const randomTime = Math.floor(Math.random() * (5000 - 2000 + 1)) + 2000
		timerRef.current = setTimeout(async () => {
			await updateKnowledgeDocumentList()
			// If embedding not complete yet, continue polling
			if (!isEmbed) {
				startPolling()
			}
		}, randomTime)
	})

	/** Stop polling */
	const stopPolling = useMemoizedFn(() => {
		if (timerRef.current) {
			clearTimeout(timerRef.current)
			timerRef.current = null
		}
	})

	// Start polling when component mounts, stop when component unmounts or isEmbed is true
	useEffect(() => {
		// Initial API call
		updateKnowledgeDocumentList()
		// Start polling
		startPolling()

		// Clear timer when component unmounts
		return () => {
			stopPolling()
		}
	}, [])

	// Stop polling when isEmbed status becomes true
	useEffect(() => {
		if (isEmbed) {
			stopPolling()
		}
	}, [isEmbed])

	return (
		<Flex vertical justify="space-between" className={styles.container}>
			{isLoading ? (
				<LoadingState />
			) : createdKnowledge ? (
				<KnowledgeContent
					createdKnowledge={createdKnowledge}
					documentList={documentList}
					isEmbed={isEmbed}
					handleViewKnowledge={handleViewKnowledge}
					getStatusIcon={getStatusIcon}
				/>
			) : (
				<div className={styles.error}>
					<IconAlertCircle color="#FF4D4F" size={36} />
					<div>{t("knowledgeDatabase.loadError")}</div>
				</div>
			)}
		</Flex>
	)
}

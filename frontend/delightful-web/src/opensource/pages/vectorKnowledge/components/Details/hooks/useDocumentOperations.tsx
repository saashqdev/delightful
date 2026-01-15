import { message, Modal, Input, Flex } from "antd"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import { DocumentOperationType } from "../../../constant"
import { KnowledgeApi } from "@/apis"
import type { Knowledge } from "@/types/knowledge"

interface UseDocumentOperationsProps {
	knowledgeBaseCode: string
	documentList: Knowledge.EmbedDocumentDetail[]
	selectedRowKeys: string[]
	setSelectedRowKeys: (keys: string[]) => void
	pageInfo: {
		page: number
		pageSize: number
	}
	searchText: string
	getKnowledgeDocumentList: (
		code: string,
		name: string,
		page: number,
		pageSize: number,
	) => Promise<void> | void
}

export const useDocumentOperations = ({
	knowledgeBaseCode,
	documentList,
	selectedRowKeys,
	setSelectedRowKeys,
	pageInfo,
	searchText,
	getKnowledgeDocumentList,
}: UseDocumentOperationsProps) => {
	const { t } = useTranslation("flow")

	// Handle single document operation
	const handleSingleDocOperation = useMemoizedFn(
		async (record: Knowledge.EmbedDocumentDetail, operationType: DocumentOperationType) => {
			// Get title and content based on operation type
			const getTitleAndContent = () => {
				switch (operationType) {
					case DocumentOperationType.ENABLE:
						return {
							title: t("knowledgeDatabase.enableDocument"),
							content: t("knowledgeDatabase.confirmEnableDocument", {
								name: record.name,
							}),
						}
					case DocumentOperationType.DISABLE:
						return {
							title: t("knowledgeDatabase.disableDocument"),
							content: t("knowledgeDatabase.confirmDisableDocument", {
								name: record.name,
							}),
						}
					case DocumentOperationType.DELETE:
						return {
							title: t("knowledgeDatabase.deleteDocument"),
							content: t("knowledgeDatabase.confirmDeleteDocument", {
								name: record.name,
							}),
						}
				}
			}

			const { title, content } = getTitleAndContent()

			Modal.confirm({
				title,
				content,
				onOk: async () => {
					try {
						let success = false

						// Execute different API calls based on operation type
						if (operationType === DocumentOperationType.DELETE) {
							// TypeScript type assertion to ensure compilation
							await (KnowledgeApi as any).deleteKnowledgeDocument({
								knowledge_code: knowledgeBaseCode,
								document_code: record.code,
							})
							success = true
						} else {
							// Enable or disable operation
							// TypeScript type assertion to ensure compilation
							const data = await (KnowledgeApi as any).updateKnowledgeDocument({
								knowledge_code: knowledgeBaseCode,
								document_code: record.code,
								name: record.name,
								enabled: operationType === DocumentOperationType.ENABLE,
							})
							success = !!data
						}

						// Refresh data list
						await getKnowledgeDocumentList(
							knowledgeBaseCode,
							searchText,
							pageInfo.page,
							pageInfo.pageSize,
						)

						// Display success message
						if (success) {
							let successMessage = ""
							switch (operationType) {
								case DocumentOperationType.ENABLE:
									successMessage = t("knowledgeDatabase.fileEnableSuccess", {
										name: record.name,
									})
									break
								case DocumentOperationType.DISABLE:
									successMessage = t("knowledgeDatabase.fileDisableSuccess", {
										name: record.name,
									})
									break
								case DocumentOperationType.DELETE:
									successMessage = t("knowledgeDatabase.deleteDocumentSuccess", {
										name: record.name,
									})
									break
							}
							message.success(successMessage)
						}
					} catch (error) {
						console.error("Operation failed:", error)
						message.error(t("common.operationFailed"))
					}
				},
			})
		},
	)

	// Batch operation on documents
	const handleBatchOperation = useMemoizedFn(async (operationType: DocumentOperationType) => {
		if (!selectedRowKeys.length) {
			message.warning(t("knowledgeDatabase.selectDocumentTip"))
			return
		}

		// Get title and content based on operation type
		const getTitleAndContent = () => {
			switch (operationType) {
				case DocumentOperationType.ENABLE:
					return {
						title: t("knowledgeDatabase.enableDocument"),
						content: t("knowledgeDatabase.confirmBatchEnable", {
							count: selectedRowKeys.length,
						}),
					}
				case DocumentOperationType.DISABLE:
					return {
						title: t("knowledgeDatabase.disableDocument"),
						content: t("knowledgeDatabase.confirmBatchDisable", {
							count: selectedRowKeys.length,
						}),
					}
				case DocumentOperationType.DELETE:
					return {
						title: t("knowledgeDatabase.deleteDocument"),
						content: t("knowledgeDatabase.confirmBatchDelete", {
							count: selectedRowKeys.length,
						}),
					}
			}
		}

		const { title, content } = getTitleAndContent()

		Modal.confirm({
			title,
			content,
			onOk: async () => {
				try {
					// Execute operations on selected rows and track results
					const operationResults = await Promise.allSettled(
						selectedRowKeys.map(async (code) => {
							try {
								if (operationType === DocumentOperationType.DELETE) {
									await (KnowledgeApi as any).deleteKnowledgeDocument({
										knowledge_code: knowledgeBaseCode,
										document_code: code,
									})
									return { success: true, code }
								} else {
									// Get document name
									const record = documentList.find((item) => item.code === code)
									await (KnowledgeApi as any).updateKnowledgeDocument({
										knowledge_code: knowledgeBaseCode,
										document_code: code,
										name: record?.name || "",
										enabled: operationType === DocumentOperationType.ENABLE,
									})
									return { success: true, code }
								}
							} catch (error) {
								return { success: false, code, error }
							}
						}),
					)

					// Update selected rows
					setSelectedRowKeys([])

					// Refresh data list
					await getKnowledgeDocumentList(
						knowledgeBaseCode,
						searchText,
						pageInfo.page,
						pageInfo.pageSize,
					)

					// Count successful and failed operations
					const successCount = operationResults.filter(
						(result) => result.status === "fulfilled" && result.value.success,
					).length
					const failedCount = selectedRowKeys.length - successCount

					// Get message based on operation type
					let successMessage = ""
					switch (operationType) {
						case DocumentOperationType.ENABLE:
							successMessage = t("knowledgeDatabase.enableDocumentsSuccess", {
								count: successCount,
							})
							break
						case DocumentOperationType.DISABLE:
							successMessage = t("knowledgeDatabase.disableDocumentsSuccess", {
								count: successCount,
							})
							break
						case DocumentOperationType.DELETE:
							successMessage = t("knowledgeDatabase.deleteDocumentsSuccess", {
								count: successCount,
							})
							break
					}

					// Display success message
					if (successCount > 0) {
						message.success(successMessage)
					}

					// Display warning if there are failed operations
					if (failedCount > 0) {
						message.warning(
							t("knowledgeDatabase.batchOperationPartialFailed", {
								count: failedCount,
							}),
						)
					}
				} catch (error) {
					console.error("Batch operation failed:", error)
					message.error(t("common.operationFailed"))
				}
			},
		})
	})

	// Enable document
	const handleEnableSingleFile = useMemoizedFn((record: Knowledge.EmbedDocumentDetail) => {
		handleSingleDocOperation(record, DocumentOperationType.ENABLE)
	})

	// Disable document
	const handleDisableSingleFile = useMemoizedFn((record: Knowledge.EmbedDocumentDetail) => {
		handleSingleDocOperation(record, DocumentOperationType.DISABLE)
	})

	// Delete single document
	const handleDeleteSingleFile = useMemoizedFn((record: Knowledge.EmbedDocumentDetail) => {
		handleSingleDocOperation(record, DocumentOperationType.DELETE)
	})

	// Batch delete documents
	const handleBatchDelete = useMemoizedFn(() => {
		handleBatchOperation(DocumentOperationType.DELETE)
	})

	// Batch enable documents
	const handleBatchEnable = useMemoizedFn(() => {
		handleBatchOperation(DocumentOperationType.ENABLE)
	})

	// Batch disable documents
	const handleBatchDisable = useMemoizedFn(() => {
		handleBatchOperation(DocumentOperationType.DISABLE)
	})

	// Rename document
	const handleRenameFile = useMemoizedFn((record: Knowledge.EmbedDocumentDetail) => {
		let newFileName = record.name

		// Separate filename and extension
		const lastDotIndex = record.name.lastIndexOf(".")
		const fileName = lastDotIndex > 0 ? record.name.substring(0, lastDotIndex) : record.name
		const fileExtension = lastDotIndex > 0 ? record.name.substring(lastDotIndex) : ""

		// Initialize to original filename (without extension)
		let inputFileName = fileName

		Modal.confirm({
			title: t("knowledgeDatabase.rename"),
			icon: null,
			centered: true,
			content: (
				<Flex
					vertical
					gap={10}
					style={{
						padding: "10px 0 20px",
					}}
				>
					<div>{t("knowledgeDatabase.documentName")}</div>
					<Input
						defaultValue={fileName}
						onChange={(e) => {
							inputFileName = e.target.value
						}}
						placeholder={t("knowledgeDatabase.inputDocumentNamePlaceholder")}
						addonAfter={fileExtension}
					/>
				</Flex>
			),
			onOk: async () => {
				try {
					if (!inputFileName || inputFileName === "") {
						message.warning(t("knowledgeDatabase.inputDocumentNamePlaceholder"))
						return Promise.reject() // Prevent modal from closing
					}

					// Merge filename and extension
					newFileName = inputFileName + fileExtension

					if (newFileName === record.name) {
						return
					}

					// Call API to update document name
					const data = await (KnowledgeApi as any).updateKnowledgeDocument({
						knowledge_code: knowledgeBaseCode,
						document_code: record.code,
						name: newFileName,
						enabled: record.enabled,
					})

					if (data) {
						// Refresh data list
						await getKnowledgeDocumentList(
							knowledgeBaseCode,
							searchText,
							pageInfo.page,
							pageInfo.pageSize,
						)

						// Show success message
						message.success(
							t("knowledgeDatabase.renameDocumentSuccess", {
								name: data.name,
							}),
						)
					}
				} catch (error) {
					console.error("Rename failed:", error)
					message.error(t("common.operationFailed"))
					return Promise.reject() // Prevent modal from closing on error
				}
			},
		})
	})

	return {
		handleSingleDocOperation,
		handleBatchOperation,
		handleEnableSingleFile,
		handleDisableSingleFile,
		handleDeleteSingleFile,
		handleBatchDelete,
		handleBatchEnable,
		handleBatchDisable,
		handleRenameFile,
	}
}

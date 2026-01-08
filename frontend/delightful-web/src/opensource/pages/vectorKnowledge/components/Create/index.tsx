import { useState, useMemo, useEffect } from "react"
import { Form, Input, Button, message, Flex, Modal, Spin } from "antd"
import { IconFileUpload, IconTrash, IconCircleCheck, IconChevronLeft } from "@tabler/icons-react"
import { LoadingOutlined } from "@ant-design/icons"
import { useMemoizedFn } from "ahooks"
import { cx } from "antd-style"
import DEFAULT_KNOWLEDGE_ICON from "@/assets/logos/knowledge-avatar.png"
import { useTranslation } from "react-i18next"
import { useUpload } from "@/opensource/hooks/useUploadFiles"
import { genFileData } from "@/opensource/pages/chatNew/components/MessageEditor/components/InputFiles/utils"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import { replaceRouteParams } from "@/utils/route"
import { RoutePath } from "@/const/routes"
import { useSearchParams } from "react-router-dom"
import { FlowRouteType } from "@/types/flow"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { useVectorKnowledgeCreateStyles } from "./styles"
import { getFileIconByExt } from "../../constant"
import VectorKnowledgeEmbed from "../Embed"
import { KnowledgeApi } from "@/apis"
import DocumentUpload from "../Upload/DocumentUpload"
import ImageUpload from "../Upload/ImageUpload"
import VectorKnowledgeConfiguration from "../Configuration"
import type { TemporaryKnowledgeConfig } from "../../types"
import { getFileExtension } from "../../utils"

type DataType = {
	name: string
	description: string
}

type UploadFileStatus = "done" | "error" | "uploading"

type UploadFileItem = {
	uid: string
	name: string
	file: File
	status: UploadFileStatus
	path?: string
}

export default function VectorKnowledgeCreate() {
	const { styles } = useVectorKnowledgeCreateStyles()

	const { t } = useTranslation("flow")

	const navigate = useNavigate()

	// Get route params for jumping to document config page
	const [searchParams] = useSearchParams()
	const queryKnowledgeBaseCode = searchParams.get("knowledgeBaseCode") || ""
	const queryDocumentCode = searchParams.get("documentCode") || ""
	// Whether in document configuration state
	const [isDocumentConfig, setIsDocumentConfig] = useState(false)

	const [form] = Form.useForm<DataType>()

	// Preview icon URL
	const [previewIconUrl, setPreviewIconUrl] = useState(DEFAULT_KNOWLEDGE_ICON)
	// Upload icon URL
	const [uploadIconUrl, setUploadIconUrl] = useState("")
	// Upload file list
	const [fileList, setFileList] = useState<UploadFileItem[]>([])

	const { uploadAndGetFileUrl } = useUpload({
		storageType: "private",
	})

	// Whether submission is allowed
	const [allowSubmit, setAllowSubmit] = useState(false)
	// Temporarily cached knowledge base configuration
	const [temporaryConfig, setTemporaryConfig] = useState<TemporaryKnowledgeConfig>()
	// Successfully created knowledge base code
	const [createdKnowledgeCode, setCreatedKnowledgeCode] = useState("")

	// Whether in pending configuration state
	const [isPendingConfiguration, setIsPendingConfiguration] = useState(false)
	// Whether in pending embed state
	const [isPendingEmbed, setIsPendingEmbed] = useState(false)

	/** Initialize form values */
	const initialValues = useMemo(() => {
		return {
			name: "",
			description: "",
		}
	}, [])

	/** Upload document */
	const handleFileUpload = useMemoizedFn(async (file: File, uid?: string) => {
		// Update the upload file list
		const newUid = uid || `${file.name}-${Date.now()}`
		if (uid) {
			setFileList((prevFileList) =>
				prevFileList.map((item) =>
					item.uid === uid ? { ...item, status: "uploading" } : item,
				),
			)
		} else {
			setFileList((prevFileList) => [
				...prevFileList,
				{ uid: newUid, name: file.name, file, status: "uploading" },
			])
		}
		// Upload file
		const newFile = genFileData(file)
		// Pre-validation passed via beforeFileUpload, so pass () => true to skip method validation
		const { fullfilled } = await uploadAndGetFileUrl([newFile], () => true)
		// Update the upload file list status
		if (fullfilled && fullfilled.length) {
			const { path } = fullfilled[0].value
			setFileList((prevFileList) =>
				prevFileList.map((item) =>
					item.uid === newUid ? { ...item, status: "done", path } : item,
				),
			)
		} else {
			setFileList((prevFileList) =>
				prevFileList.map((item) =>
					item.uid === newUid ? { ...item, status: "error" } : item,
				),
			)
		}
	})

	/** Delete file */
	const handleFileRemove = useMemoizedFn((e: any, uid: string) => {
		e?.domEvent?.stopPropagation?.()
		Modal.confirm({
			centered: true,
			title: t("knowledgeDatabase.deleteFile"),
			content: t("knowledgeDatabase.deleteDesc"),
			okText: t("button.confirm", { ns: "interface" }),
			cancelText: t("button.cancel", { ns: "interface" }),
			onOk: async () => {
				setFileList((prevFileList) => prevFileList.filter((item) => item.uid !== uid))
				message.success(t("common.deleteSuccess"))
			},
		})
	})

	/** Get file status icon */
	const getFileStatusIcon = useMemoizedFn((file: UploadFileItem) => {
		if (file.status === "done") {
			return <IconCircleCheck color="#32C436" size={24} />
		}
		if (file.status === "error") {
			return (
				<div className={styles.uploadRetry}>
					{t("knowledgeDatabase.uploadRetry")}
					<span
						className={styles.uploadRetryText}
						onClick={() => handleFileUpload(file.file, file.uid)}
					>
						{t("knowledgeDatabase.uploadRetryText")}
					</span>
				</div>
			)
		}
		if (file.status === "uploading") {
			return <Spin indicator={<LoadingOutlined spin />} />
		}
		return null
	})

	/** Previous step - Return to previous page */
	const handleBack = useMemoizedFn(() => {
		// If in document configuration state, return to document list page
		if (isDocumentConfig) {
			navigate(`${RoutePath.VectorKnowledgeDetail}?code=${queryKnowledgeBaseCode}`)
		} else {
			navigate(
				replaceRouteParams(RoutePath.Flows, {
					type: FlowRouteType.VectorKnowledge,
				}),
			)
		}
	})

	/** Next step - Submit form */
	const handleSubmit = async () => {
		try {
			const values = await form.validateFields()
			setTemporaryConfig({
				name: values.name,
				icon: uploadIconUrl,
				description: values.description,
				enabled: true,
				document_files: fileList
					.filter((item) => !!item.path)
					.map((item) => ({
						name: item.name,
						key: item.path!,
						code: item.path!,
					})),
			})
			setIsPendingConfiguration(true)
		} catch (error) {
			console.error("Form validation failed:", error)
		}
	}

	/** Required field validation */
	const nameValue = Form.useWatch("name", form)

	/** Configuration page back */
	const handleConfigurationBack = useMemoizedFn(() => {
		// If in document configuration state, return to document list page
		if (isDocumentConfig) {
			navigate(`${RoutePath.VectorKnowledgeDetail}?code=${queryKnowledgeBaseCode}`)
		} else {
			setIsPendingConfiguration(false)
		}
	})

	/** Configuration page submit */
	const handleConfigurationSubmit = useMemoizedFn(async (data: TemporaryKnowledgeConfig) => {
		try {
			// Call API to create knowledge base
			const res = await KnowledgeApi.createKnowledge(data)
			if (res) {
				// Clear form
				form.resetFields()
				setUploadIconUrl("")
				setFileList([])
				setIsPendingConfiguration(false)
				setIsPendingEmbed(true)
				setCreatedKnowledgeCode(res.code)
				message.success(t("common.savedSuccess"))
			}
		} catch (error) {
			console.error("Failed to create knowledge base:", error)
			message.error(t("knowledgeDatabase.saveConfigFailed"))
		}
	})

	// Determine whether submission is allowed
	useEffect(() => {
		setAllowSubmit(!!nameValue && fileList.length > 0)
	}, [nameValue, fileList])

	// On initialization, determine whether in document configuration state via route parameters
	useEffect(() => {
		setIsDocumentConfig(!!queryKnowledgeBaseCode && !!queryDocumentCode)
	}, [])

	const PageContent = useMemo(() => {
		if ((temporaryConfig && isPendingConfiguration) || isDocumentConfig) {
			const documentConfig = isDocumentConfig
				? {
						knowledgeBaseCode: queryKnowledgeBaseCode,
						documentCode: queryDocumentCode,
				  }
				: undefined
			return (
				<VectorKnowledgeConfiguration
					documentConfig={documentConfig}
					knowledgeBase={temporaryConfig}
					saveKnowledgeConfig={handleConfigurationSubmit}
					onBack={handleConfigurationBack}
				/>
			)
		}

		if (createdKnowledgeCode && isPendingEmbed) {
			return <VectorKnowledgeEmbed knowledgeBaseCode={createdKnowledgeCode} />
		}

		return (
			<Flex vertical justify="space-between" className={styles.container}>
				<div className={styles.content}>
					<div className={styles.title}>
						{t("knowledgeDatabase.createVectorKnowledge")}
					</div>
					<Form
						form={form}
						layout="vertical"
						requiredMark={false}
						initialValues={initialValues}
					>
						<Form.Item
							label={
								<div className={styles.label}>{t("knowledgeDatabase.icon")}</div>
							}
							rules={[
								{
									required: true,
									message: t("knowledgeDatabase.iconPlaceholder"),
								},
							]}
						>
							<ImageUpload
								previewIconUrl={previewIconUrl}
								setPreviewIconUrl={setPreviewIconUrl}
								setUploadIconUrl={setUploadIconUrl}
							/>
						</Form.Item>

						<Form.Item
							label={
								<div className={cx(styles.label, styles.required)}>
									{t("knowledgeDatabase.knowledgeName")}
								</div>
							}
							name="name"
							rules={[
								{
									required: true,
									message: t("knowledgeDatabase.namePlaceholder"),
								},
							]}
						>
							<Input placeholder={t("knowledgeDatabase.namePlaceholder")} />
						</Form.Item>

						<Form.Item
							label={
								<div className={styles.label}>
									{t("knowledgeDatabase.description")}
								</div>
							}
							name="description"
						>
							<Input.TextArea
								rows={4}
								placeholder={t("knowledgeDatabase.descriptionPlaceholder")}
							/>
						</Form.Item>

						<Form.Item
							label={
								<div className={cx(styles.label, styles.required)}>
									{t("common.uploadFile")}
								</div>
							}
						>
							<div>
								<DocumentUpload handleFileUpload={handleFileUpload}>
									<div className={styles.uploadIcon}>
										<IconFileUpload size={40} stroke={1} />
									</div>
									<div className={styles.uploadText}>
										{t("common.fileDragTip")}
									</div>
									<div className={styles.uploadDescription}>
										{`${t(
											"common.supported",
										)} TXT、MARKDOWN、PDF、XLSX、XLS、DOCX、CSV、XML`}
										<br />
										{t("common.fileSizeLimit", { size: "15MB" })}
									</div>
								</DocumentUpload>
								{fileList.map((file) => (
									<Flex
										align="center"
										justify="space-between"
										key={file.uid}
										className={styles.fileItem}
									>
										<Flex align="center" gap={8}>
											{getFileIconByExt(getFileExtension(file.name))}
											<div>{file.name}</div>
										</Flex>
										<Flex align="center" gap={8}>
											{getFileStatusIcon(file)}
											<IconTrash
												style={{ cursor: "pointer" }}
												size={24}
												stroke={1.3}
												onClick={(e) => handleFileRemove(e, file.uid)}
											/>
										</Flex>
									</Flex>
								))}
							</div>
						</Form.Item>
					</Form>
				</div>
				<Flex justify="flex-end" align="center" className={styles.footer} gap={16}>
					<Button className={styles.backButton} onClick={handleBack}>
						{t("knowledgeDatabase.previousStep")}
					</Button>
					<Button type="primary" onClick={handleSubmit} disabled={!allowSubmit}>
						{t("knowledgeDatabase.nextStep")}
					</Button>
				</Flex>
			</Flex>
		)
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [
		allowSubmit,
		previewIconUrl,
		uploadIconUrl,
		fileList,
		form,
		temporaryConfig,
		isPendingEmbed,
		isDocumentConfig,
		queryKnowledgeBaseCode,
		queryDocumentCode,
		handleFileRemove,
		handleSubmit,
	])

	return (
		<Flex className={styles.wrapper} vertical>
			<Flex className={styles.header} align="center" gap={14}>
				<DelightfulIcon
					component={IconChevronLeft}
					size={24}
					className={styles.arrow}
					onClick={handleBack}
				/>
				<div>{t("common.knowledgeDatabase")}</div>
			</Flex>
			{PageContent}
		</Flex>
	)
}

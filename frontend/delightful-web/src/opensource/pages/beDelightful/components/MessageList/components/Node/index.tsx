import { messageFilter } from "@/opensource/pages/beDelightful/utils/handleMessage"
import { isEmpty, pick } from "lodash-es"
import { Attachment } from "../MessageAttachment"
import NodeHeader from "../NodeHeader"
import Text from "../Text"
import Tool from "../Tool"
import { useStyles } from "./style"

interface NodeProps {
	node: any
	prevNode?: any
	onSelectDetail?: (detail: any) => void
	isSelected?: boolean
	isShare?: boolean
}

const Node = ({ node, prevNode, onSelectDetail, isSelected, isShare }: NodeProps) => {
	const { styles } = useStyles()
	const parentNodeRole = prevNode?.role
	const isUser = node?.role !== "assistant"
	// Determine whether to show NodeHeader
	// Show header if there's no previous message, or if the current message role differs from the previous one
	const shouldShowHeader = !prevNode || parentNodeRole !== node.role
	// Check if it's a selected node and add isFromNode flag when calling onSelectDetail
	const handleSelectDetail = (detail: any) => {
		// Ensure onSelectDetail exists and node is selected or selection state is undefined
		if (onSelectDetail && (isSelected || isSelected === undefined)) {
			onSelectDetail({
				...detail,
				isFromNode: true,
			})
		}
	}
	const handleClick = () => {
		console.log(node, "handleClickhandleClick")
		if (!isEmpty(node?.tool?.detail)) {
			const toolInfo = pick(node.tool, ["name", "url", "action", "remark"])
			handleSelectDetail({ ...node.tool.detail, ...toolInfo })
		}
	}

	return (
		<>
			{shouldShowHeader && (
				<NodeHeader
					isUser={isUser}
					timestamp={node?.send_timestamp || node?.send_time}
					isShare={isShare}
				/>
			)}
			{messageFilter(node) ? null : (
				<div
					className={`${styles.defaultNode} ${
						isUser ? styles.userNode : styles.agentNode
					}`}
					onClick={handleClick}
					data-has-detail={!isEmpty(node?.tool?.detail)}
				>
					{!isEmpty(node?.tool) && <Tool data={node?.tool} />}
					{node?.status === "finished" && node?.tool?.attachments && (
						<Attachment
							attachments={node?.tool?.attachments}
							onSelectDetail={onSelectDetail}
						/>
					)}
					{node?.event === "before_llm_request" ||
					node.status === "finished" ||
					node.status === "error" ? null : (
						<Text data={node} isUser={isUser} hideHeader />
					)}
					<Attachment attachments={node.attachments} onSelectDetail={onSelectDetail} />

					{node?.text?.attachments?.length > 0 && (
						<Attachment
							attachments={node.text?.attachments}
							onSelectDetail={onSelectDetail}
						/>
					)}
				</div>
			)}
			{node?.status === "error" && (
				<div className={styles.errorTextContainer}>
					<Text
						data={
							isEmpty(node?.content) ? { content: "Task error, terminated." } : node
						}
						isUser={isUser}
						hideHeader
					/>
				</div>
			)}
			{node?.status === "finished" && (
				<div className={styles.finishedTextContainer}>
					<Text
						data={{
							content:
								"Task completed. Ready to receive new requests or modifications.",
						}}
						isUser={isUser}
						hideHeader
						isFinished
					/>
				</div>
			)}
		</>
	)
}

export default Node

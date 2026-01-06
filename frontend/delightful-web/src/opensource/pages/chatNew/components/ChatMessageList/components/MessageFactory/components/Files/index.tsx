import { memo } from "react"
import DelightfulFiles from "../../../../../DelightfulFiles"

interface Props {
	files?: any[]
	messageId: string
}

const Files = memo(
	({ files, messageId }: Props) => {
		return <DelightfulFiles data={files} messageId={messageId} />
	},
	(prev, next) => prev.messageId === next.messageId,
)

export default Files

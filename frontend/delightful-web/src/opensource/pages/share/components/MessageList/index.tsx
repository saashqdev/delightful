import Node from "@/opensource/pages/superMagic/components/MessageList/components/Node"
import { memo } from "react"
function MessageList({
	messageList,
	onSelectDetail,
}: {
	messageList: any[]
	onSelectDetail: (detail: any) => void
}) {
	return messageList.map((item: any, index: number) => {
		return (
			<Node
				node={item}
				key={item.message_id}
				prevNode={index > 0 ? messageList[index - 1] : undefined}
				onSelectDetail={onSelectDetail}
				isSelected
				isShare
			/>
		)
	})
}

export default memo(MessageList)

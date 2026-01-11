import MessageRenderFactory from "./factory"
import { Suspense } from "react"
import ControlMessageApplyService from "@/opensource/services/chat/message/MessageApplyServices/ControlMessageApplyService"
import { observer } from "mobx-react-lite"

const MessageRender = observer(function MessageRender(props: { message: any }) {
	const { message } = props

	// handlecomponentclass型
	let componentType = message.type

	// 不是控制classmessage，默认为defaultclass型
	if (!ControlMessageApplyService.isControlMessage(message)) {
		componentType = "default"
	}

	// 如果是撤回的
	if (message.revoked) {
		componentType = "RevokeTip"
	}

	// getcomponent
	const MessageComponent = MessageRenderFactory.getComponent(componentType)

	if (!MessageComponent) {
		return null
	}

	// 生成componentprops
	const componentProps = MessageRenderFactory.generateProps(componentType, message)

	return (
		<Suspense fallback={<></>}>
			<MessageComponent key={message.id} {...componentProps} />
		</Suspense>
	)
})

export default MessageRender

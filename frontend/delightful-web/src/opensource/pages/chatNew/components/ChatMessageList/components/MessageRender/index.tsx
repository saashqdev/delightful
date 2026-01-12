import MessageRenderFactory from "./factory"
import { Suspense } from "react"
import ControlMessageApplyService from "@/opensource/services/chat/message/MessageApplyServices/ControlMessageApplyService"
import { observer } from "mobx-react-lite"

const MessageRender = observer(function MessageRender(props: { message: any }) {
	const { message } = props

	// Handle component type
	let componentType = message.type

	// If not control type message, default to default type
	if (!ControlMessageApplyService.isControlMessage(message)) {
		componentType = "default"
	}

	// If revoked
	if (message.revoked) {
		componentType = "RevokeTip"
	}

	// Get component
	const MessageComponent = MessageRenderFactory.getComponent(componentType)

	if (!MessageComponent) {
		return null
	}

	// Generate component props
	const componentProps = MessageRenderFactory.generateProps(componentType, message)

	return (
		<Suspense fallback={<></>}>
			<MessageComponent key={message.id} {...componentProps} />
		</Suspense>
	)
})

export default MessageRender

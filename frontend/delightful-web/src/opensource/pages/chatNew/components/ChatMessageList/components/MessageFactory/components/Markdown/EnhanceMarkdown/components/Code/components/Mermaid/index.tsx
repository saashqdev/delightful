import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import type { DelightfulMermaidProps } from "@/opensource/components/base/DelightfulMermaid/types"
import DelightfulMermaid from "@/opensource/components/base/DelightfulMermaid"
import MessageFilePreviewService from "@/opensource/services/chat/message/MessageImagePreview"
import { useMessageRenderContext } from "@/opensource/components/business/MessageRenderProvider/hooks"
import { useConversationMessage } from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageItem/components/ConversationMessageProvider/hooks"
import conversationStore from "@/opensource/stores/chatNew/conversation"
import DelightfulCode from "@/opensource/components/base/DelightfulCode"
import { CodeRenderProps } from "../../types"
import { memo } from "react"

interface MermaidProps extends DelightfulMermaidProps, CodeRenderProps {
	language?: string
}

const Mermaid = memo((props: MermaidProps) => {
	const { hiddenDetail } = useMessageRenderContext()
	const { t } = useTranslation("interface")
	const { messageId } = useConversationMessage()
	const { isStreaming } = props

	const handleClick = useMemoizedFn(async (svgRef) => {
		if (svgRef) {
			const svg = svgRef.innerHTML
			// const base64 = `data:image/svg+xml;base64,${btoa(svg)}`

			MessageFilePreviewService.setPreviewInfo({
				url: svg,
				ext: { ext: "svg", mime: "image/svg+xml" },
				messageId,
				conversationId: conversationStore.currentConversation?.id,
				standalone: true,
			})
		}
	})

	if (hiddenDetail) {
		return t("chat.message.placeholder.mermaid")
	}

	if (isStreaming) {
		return <DelightfulCode data={props.data} />
	}

	return <DelightfulMermaid {...props} onClick={handleClick} />
})

export default Mermaid

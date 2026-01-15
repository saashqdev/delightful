import useSendMessage from "@/opensource/pages/chatNew/hooks/useSendMessage"
import { ConversationMessageType } from "@/types/chat/conversation_message"
import { useMemoizedFn } from "ahooks"
import { memo, type AnchorHTMLAttributes } from "react"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { DelightfulFunctionLinkType } from "./const"

type DelightfulFunctionLinkProps = AnchorHTMLAttributes<HTMLAnchorElement> & {
	href: string
}

const DelightfulFunctionLink = memo(({ href, children }: DelightfulFunctionLinkProps) => {
	const sendMessage = useSendMessage()

	const handleClick = useMemoizedFn((e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault()
		e.stopPropagation()

		const url = new URL(href)
		switch (url.hostname) {
			case DelightfulFunctionLinkType.SendMessage:
				const searchParams = new URLSearchParams(url.search)
				const content = searchParams.get("content")

				if (!content) {
					return
				}

				sendMessage({
					type: ConversationMessageType.Text,
					text: {
						content,
					},
				})
				break
			default:
				break
		}
	})

	return (
		<DelightfulButton
			type="link"
			size="small"
			style={{ display: "inline" }}
			onClick={handleClick}
		>
			{children}
		</DelightfulButton>
	)
})

export default DelightfulFunctionLink

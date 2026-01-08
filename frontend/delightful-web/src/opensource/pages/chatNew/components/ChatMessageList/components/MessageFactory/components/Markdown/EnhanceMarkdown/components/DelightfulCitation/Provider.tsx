import type { AggregateAISearchCardSearch } from "@/types/chat/conversation_message"
import type { ReactNode } from "react"
import { createContext, useMemo } from "react"

export const DelightfulCitationContext = createContext<{
	sources: AggregateAISearchCardSearch[]
}>({ sources: [] })

const DelightfulCitationProvider = ({
	sources,
	children,
}: {
	sources: AggregateAISearchCardSearch[] | undefined
	children: ReactNode
}) => {
	const value = useMemo(() => {
		return {
			sources: sources ?? [],
		}
	}, [sources])

	return <DelightfulCitationContext.Provider value={value}>{children}</DelightfulCitationContext.Provider>
}

export default DelightfulCitationProvider

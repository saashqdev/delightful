import { useMemo, useRef } from "react"
import type {
	AggregateAISearchCardContent,
	AggregateAISearchCardSearch,
} from "@/types/chat/conversation_message"
import {
	AggregateAISearchCardDataType,
	AggregateAISearchCardDeepLevel,
} from "@/types/chat/conversation_message"
import type { TimelineProps } from "antd"
import { ConfigProvider, Flex, Timeline } from "antd"
import DelightfulCollapse from "@/opensource/components/base/DelightfulCollapse"
import { useTranslation } from "react-i18next"
import { resolveToString } from "@bedelightful/es6-template-strings"

import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { IconChevronDown, IconChevronUp } from "@tabler/icons-react"
import { useBoolean, useMemoizedFn, useSize, useUpdateEffect } from "ahooks"
import { isEmpty } from "lodash-es"
import DelightfulMarkdown from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown"
import DelightfulCitationProvider from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown/components/DelightfulCitation/Provider"
import { observer } from "mobx-react-lite"
import ErrorContent from "@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/ErrorContent"
import TimeLineDot from "./components/TimeLineDot"
import useStyles from "./styles"
import { TimeLineDotStatus } from "./const"
import EventTable from "./components/EventTable"
import SearchKeywords from "./components/SearchKeywords"
import SearchingQuestion from "./components/SearchQuestion"
import Searching from "./components/title/Searching"
import Reading from "./components/title/Reading"
import Summarizing from "./components/title/Summarizing"
import Result from "./components/title/Result"
import MindMap from "./components/MindMap"
import Reasoning from "./components/title/Reasoning"
import Markdown from "../Markdown"
import SourceItem from "./components/SourceItem"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import { useTheme } from "antd-style"

const configProviderTheme = {
	components: {
		Collapse: {
			motionDurationMid: "0.25s",
		},
	},
}

interface DelightfulAggregateAISearchCardProps {
	content?: AggregateAISearchCardContent
	reasoningContent?: string
	isStreaming?: boolean
	isReasoningStreaming?: boolean
}

const TimelineCollapsePanelKey = "timeline"

const DelightfulAggregateAISearchCard = observer(
	({
		content,
		reasoningContent,
		isStreaming,
		isReasoningStreaming,
	}: DelightfulAggregateAISearchCardProps) => {
		const { fontSize } = useFontSize()
		const { t } = useTranslation("interface")
		const { delightfulColorUsages } = useTheme()

		const summaryRef = useRef<HTMLDivElement>(null)

		const size = useSize(summaryRef)

		/** Summary content */
		const llmResponse = content?.summary?.content

		/** Whether search ended */
		const isSearchingFinish = useMemo(() => {
			if (content?.finish) return true

			switch (content?.search_deep_level) {
				case AggregateAISearchCardDeepLevel.Simple:
					return Boolean(llmResponse)
				case AggregateAISearchCardDeepLevel.Deep:
					if (isEmpty(content?.associate_questions)) return false
					return (
						Object.values(content?.associate_questions ?? {}).every(
							(item) => item.llm_response,
						) && Boolean(reasoningContent || llmResponse)
					)
				default:
					return false
			}
		}, [
			content?.associate_questions,
			content?.finish,
			content?.search_deep_level,
			llmResponse,
			reasoningContent,
		])

		const { styles } = useStyles({
			fontSize,
			isSearchingFinish,
			hasLlmResponse: content?.finish,
		})

		const isSimpleSearch = useMemo(() => {
			return (
				!content?.search_deep_level ||
				content?.search_deep_level === AggregateAISearchCardDeepLevel.Simple
			)
		}, [content?.search_deep_level])

		const [collapsed, { setFalse, setTrue }] = useBoolean(!(content?.finish || isSimpleSearch))

		/** Whether in deep search */
		const isSearching = useMemo(() => {
			if (content?.finish) return false

			return (
				isEmpty(content?.associate_questions) ||
				Object.keys(content?.associate_questions ?? {}).every(
					(id) => !content?.search?.[id],
				)
			)
		}, [content?.associate_questions, content?.finish, content?.search])

		/** Whether in deep reading */
		const isReading = useMemo(() => {
			if (content?.finish) return false

			return (
				!isSearching &&
				!Object.values(content?.associate_questions ?? {}).every(
					(item) => item.llm_response,
				)
			)
		}, [content?.associate_questions, content?.finish, isSearching])

		/** Whether in summarization */
		// const isSummarizing = useMemo(() => {
		// 	if (content?.finish) return false

		// 	return !isReading && isNil(llmResponse)
		// }, [content?.finish, isReading, llmResponse])

		const allPages = useMemo(
			() =>
				Array.from(Object.values(content?.search ?? {}))
					.flat()
					.reduce((prev, cur) => {
						if (prev.find((item) => item.url === cur.url)) return prev
						prev.push(cur)
						return prev
					}, [] as AggregateAISearchCardSearch[]),
			[content?.search],
		)

		/** Question search area title */
		const titleContent = useMemo(() => {
			if (isSearching)
				return (
					<Searching
						deepLevel={content?.search_deep_level}
						hasAssociateQuestions={!isEmpty(content?.associate_questions)}
					/>
				)
			if (isReading) {
				return <Reading deepLevel={content?.search_deep_level} allPages={allPages} />
			}

			if (isReasoningStreaming) {
				return <Reasoning />
			}

			if (isStreaming) {
				return <Summarizing />
			}

			return (
				<Result
					deepLevel={content?.search_deep_level}
					pageLength={allPages.length}
					associateQuestions={content?.associate_questions}
				/>
			)
		}, [
			allPages,
			content?.associate_questions,
			content?.search_deep_level,
			isReading,
			isReasoningStreaming,
			isSearching,
			isStreaming,
		])

		const collapseItems = useMemo(() => {
			if (!content) return []

			const datas = []

			// If events exist, display events
			if (content.event && content.event.length > 0) {
				datas.push({
					key: AggregateAISearchCardDataType.Event,
					label: (
						<Flex align="center" gap={10}>
							{t("chat.aggregate_ai_search_card.relatedEvents")}
							<span className={styles.labelCount}>{content.event.length}</span>
						</Flex>
					),
					children: <EventTable events={content.event} />,
				})
			}

			const allSearch = content.search?.["0"]

			// If search results exist and search is finished, display search results
			if (allSearch?.length > 0 && content.event && content.mind_map && content.finish) {
				datas.push({
					key: AggregateAISearchCardDataType.Search,
					label: (
						<Flex align="center" gap={10}>
							{t("chat.aggregate_ai_search_card.search")}
							<span className={styles.labelCount}>{allSearch.length}</span>
						</Flex>
					),
					children: (
						<ol className={styles.sourceList} data-testid="sourceList">
							{allSearch?.map((item) => (
								<li key={`${item.id}_${item.index}`}>
									<SourceItem
										name={item.name}
										url={item.url}
										datePublished={item.datePublished}
									/>
								</li>
							))}
						</ol>
					),
				})
			}

			return datas
		}, [content, t, styles.labelCount, styles.sourceList])

		useUpdateEffect(() => {
			if (isSearchingFinish) {
				setFalse()
			}
		}, [isSearchingFinish])

		useUpdateEffect(() => {
			if (content?.search_deep_level === 2 && !content.finish) {
				setTrue()
			}
		}, [content?.search_deep_level])

		const timelineItems = useMemo(() => {
			if (content?.search_deep_level !== 2) {
				const keywords = Array.from(Object.values(content?.associate_questions ?? {})).map(
					(i) => i.title,
				)
				return [
					{
						key: "searching_page",
						dot: (
							<TimeLineDot
								status={
									content?.finish || keywords.length
										? TimeLineDotStatus.SUCCESS
										: TimeLineDotStatus.PENDING
								}
							/>
						),
						children: (
							<Flex vertical gap={6} className={styles.timelineItem}>
								{t("chat.aggregate_ai_search_card.search_page")}
								<SearchKeywords items={keywords} />
							</Flex>
						),
					},
					!isSearching
						? {
								key: "reading_page",
								dot: (
									<TimeLineDot
										status={
											llmResponse
												? TimeLineDotStatus.SUCCESS
												: TimeLineDotStatus.PENDING
										}
									/>
								),
								children: (
									<Flex vertical gap={6} className={styles.timelineItem}>
										<span className={styles.questionReadCount}>
											{t("chat.aggregate_ai_search_card.read_page", {
												number: allPages?.length || 0,
											})}
										</span>
										{allPages?.map((item) => {
											return (
												<Flex key={item.id} gap={8} align="center">
													<SourceItem key={item.id} {...item} />
												</Flex>
											)
										})}
									</Flex>
								),
						  }
						: null,
				].filter(Boolean) as TimelineProps["items"]
			}

			return Object.entries(content?.associate_questions ?? {}).map(([id, item], index) => {
				const status = item.llm_response
					? TimeLineDotStatus.SUCCESS
					: TimeLineDotStatus.PENDING

				const isPending = !content.finish && !isSearchingFinish && !item.llm_response

				// const searchCount = null
				const searchCount = content?.search?.[id]

				return {
					key: id,
					dot: <TimeLineDot status={status} />,
					children: (
						<Flex
							vertical
							gap={6}
							className={styles.timelineItem}
							style={{
								// @ts-ignore
								"--index": index,
							}}
						>
							<span
								className={styles.questionTitle}
								style={{
									fontWeight: isPending ? 600 : 400,
									color: isPending
										? delightfulColorUsages.text[0]
										: delightfulColorUsages.text[2],
								}}
							>
								<DelightfulMarkdown content={item.title} />
							</span>
							<SearchKeywords items={item.search_keywords} />
							<div className={styles.questionReadCount}>
								{isPending || !searchCount ? (
									<SearchingQuestion searchArray={searchCount} />
								) : (
									resolveToString(
										t("chat.aggregate_ai_search_card.questionReadCount"),
										{
											count: item.page_count ?? 0,
										},
									)
								)}
							</div>
						</Flex>
					),
				}
			})
		}, [
			allPages,
			content?.associate_questions,
			content?.finish,
			content?.search,
			content?.search_deep_level,
			isSearching,
			isSearchingFinish,
			llmResponse,
			styles.questionReadCount,
			styles.questionTitle,
			styles.timelineItem,
			t,
		])

		const questionItems = useMemo(
			() => [
				{
					key: TimelineCollapsePanelKey,
					label: titleContent,
					children:
						timelineItems && timelineItems.length > 0 ? (
							<Timeline className={styles.timeline} items={timelineItems} />
						) : null,
				},
			],
			[titleContent, timelineItems, styles.timeline],
		)

		const onTimelineCollapseChange = useMemoizedFn((keys: string[]) => {
			if (keys.includes(TimelineCollapsePanelKey)) {
				setTrue()
			} else {
				setFalse()
			}
		})

		if (!content) return null

		if (content.error) {
			return <ErrorContent />
		}

		return (
			<DelightfulCitationProvider sources={content.search?.["0"]}>
				<ConfigProvider theme={configProviderTheme}>
					<Flex vertical className={styles.container}>
						<Flex vertical className={styles.timelineContainer}>
						{/* Top question search area */}
							<DelightfulCollapse
								className={styles.questionCollapse}
								style={{
									margin: isSearchingFinish ? undefined : "-10px",
								// Prevent stretching width when expanded
									maxWidth: isSearchingFinish ? size?.width : "unset",
								}}
								expandIcon={({ isActive }) => (
									<DelightfulIcon
										color="currentColor"
										component={isActive ? IconChevronUp : IconChevronDown}
									/>
								)}
								activeKey={collapsed ? [TimelineCollapsePanelKey] : []}
								items={questionItems}
								onChange={onTimelineCollapseChange}
							/>
						</Flex>
						{/* Summary area */}
						<div className={styles.summary} ref={summaryRef}>
							{reasoningContent || llmResponse ? (
								<Markdown
									content={llmResponse}
									reasoningContent={reasoningContent}
									isStreaming={isStreaming}
									isReasoningStreaming={isReasoningStreaming}
								/>
							) : null}
							{llmResponse && content?.mind_map ? (
								<MindMap
									data={content.mind_map}
									pptData={content.ppt}
									className={styles.mindmap}
								/>
							) : null}
						</div>
					{/* Mind map, events, search sources */}
						<DelightfulCollapse className={styles.collapse} items={collapseItems} />
					</Flex>
				</ConfigProvider>
			</DelightfulCitationProvider>
		)
	},
)

export default DelightfulAggregateAISearchCard

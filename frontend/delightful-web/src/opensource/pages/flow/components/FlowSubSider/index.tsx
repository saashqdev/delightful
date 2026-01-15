import { IconRouteSquare, IconTools, IconChevronRight, IconFileTextAi } from "@tabler/icons-react"
import { useState } from "react"
import { DelightfulList } from "@/opensource/components/DelightfulList"
import { useLocation } from "react-router"
import { useNavigate } from "@/opensource/hooks/useNavigate"
import { RoutePath } from "@/const/routes"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import SubSiderContainer from "@/opensource/layouts/BaseLayout/components/SubSider"
import { IconDelightfulBots } from "@/enhance/tabler/icons-react"
import { FlowRouteType } from "@/types/flow"
import { createStyles } from "antd-style"
import { useTranslation } from "react-i18next"
import { replaceRouteParams } from "@/utils/route"
import IconMcp from "@/assets/logos/mcp.png"

const useStyles = createStyles(({ css }) => {
	return {
		container: css`
			width: 240px;
			flex-shrink: 0;
		`,
		subSiderItem: css`
			padding: 5px;
		`,
	}
})

function FlowSubSider() {
	const { t } = useTranslation()

	const { pathname } = useLocation()

	const [collapseKey, setCollapseKey] = useState<string>(pathname)

	const { styles } = useStyles()

	const navigate = useNavigate()

	return (
		<SubSiderContainer className={styles.container}>
			<DelightfulList
				itemClassName={styles.subSiderItem}
				active={collapseKey}
				onItemClick={({ id }) => {
					setCollapseKey(id)
					navigate(id)
				}}
				items={[
					{
						id: RoutePath.AgentList,
						title: t("common.agent", { ns: "flow" }),
						avatar: {
							src: (
								<DelightfulIcon
									component={IconDelightfulBots}
									color="currentColor"
								/>
							),
							style: { background: "#315CEC", padding: 6 },
						},
						extra: <DelightfulIcon component={IconChevronRight} />,
					},
					{
						id: replaceRouteParams(RoutePath.Flows, {
							type: FlowRouteType.Sub,
						}),
						title: t("common.flow", { ns: "flow" }),
						avatar: {
							src: (
								<DelightfulIcon component={IconRouteSquare} color="currentColor" />
							),
							style: { background: "#FF7D00", padding: 6 },
						},
						extra: <DelightfulIcon component={IconChevronRight} />,
					},
					{
						id: replaceRouteParams(RoutePath.Flows, {
							type: FlowRouteType.Tools,
						}),
						title: t("common.toolset", { ns: "flow" }),
						avatar: {
							src: <DelightfulIcon component={IconTools} color="currentColor" />,
							style: { background: "#8BD236", padding: 6 },
						},
						extra: <DelightfulIcon component={IconChevronRight} />,
					},

					{
						id: replaceRouteParams(RoutePath.Flows, {
							type: FlowRouteType.VectorKnowledge,
						}),
						title: t("vectorDatabase.name", { ns: "flow" }),
						avatar: {
							src: <DelightfulIcon component={IconFileTextAi} color="currentColor" />,
							style: {
								background: "#32C436",
								padding: 6,
							},
						},
						extra: <DelightfulIcon component={IconChevronRight} />,
					},

					{
						id: replaceRouteParams(RoutePath.Flows, {
							type: FlowRouteType.Mcp,
						}),
						title: "MCP",
						avatar: {
							src: <img src={IconMcp} alt="" />,
						},
						extra: <DelightfulIcon component={IconChevronRight} />,
					},
				]}
			/>
		</SubSiderContainer>
	)
}

export default FlowSubSider

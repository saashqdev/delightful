import { useMemoizedFn } from "ahooks"
import { useMemo, useState } from "react"
import { TabObject } from "../constants"
import PanelMaterial from "../components/PanelMaterial"
import PanelFlow from "../components/PanelFlow"
import PanelTools from "../components/PanelTools"
import PanelAgent from "../components/PanelAgent"
import { useTranslation } from "react-i18next"
import i18next from "i18next"

const TabContentMap = {
	[TabObject.Material]: PanelMaterial,
	[TabObject.Flow]: PanelFlow,
    [TabObject.Tools]: PanelTools,
    [TabObject.Agent]: PanelAgent
}

export default function useTab () {
    const { t } = useTranslation()
	const [ tab, setTab ] = useState(TabObject.Material)

	const changeTab = useMemoizedFn((e) => {
		if (typeof e !== "object") {
			setTab(e)
			return
		}
		setTab(e.target.value)
	})


	//  Front tab list
	const tabList = useMemo(() => {
		return [
			{
				label: i18next.t("flow.node", { ns: "delightfulFlow" }),
				value: TabObject.Material,
				onClick: () => changeTab(TabObject.Material)
			},
			{
				label: i18next.t("flow.flow", { ns: "delightfulFlow" }),
				value: TabObject.Flow,
				onClick: () => changeTab(TabObject.Flow)
			},
			{
				label: i18next.t("flow.tools", { ns: "delightfulFlow" }),
				value: TabObject.Tools,
				onClick: () => changeTab(TabObject.Tools)
			},
			{
				label: i18next.t("flow.agent", { ns: "delightfulFlow" }),
				value: TabObject.Agent,
				onClick: () => changeTab(TabObject.Agent)
			}
		]
	}, [ changeTab ])

	// [[FC_1, show_1], [FC_2, show_2]]
	//  This way of returning is to avoid re-rendering each time clicking tab, control whether to show through display
	const tabContents = useMemo(() => {
		return Object.entries(TabContentMap)
			.map(([ curTab, Comp ]) => ([ Comp, tab === curTab ]))
	}, [tab])

	return {
		tab,
		tabList,
		changeTab,
		tabContents
	}
}


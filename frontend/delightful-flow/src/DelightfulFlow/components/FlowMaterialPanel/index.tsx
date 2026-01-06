import React from "react"
import styles from "./index.module.less"

// import useMaterialPanel from "./hooks/useMaterialPanel"
import { prefix } from "@/DelightfulFlow/constants"
import clsx from "clsx"
import { PanelProvider } from "./context/PanelContext/Provider"
import useMaterialPanel from "./hooks/useMaterialPanel"
import useMaterialSearch from "./hooks/useMaterialSearch"
import useTab from "./hooks/useTab"
import StickyButton from "./components/sections/StickyButton"
import PanelHeader from "./components/sections/PanelHeader"
import SearchBar from "./components/sections/SearchBar"
import MaterialContent from "./components/sections/MaterialContent"
import { useMemo } from "react"

export const MaterialPanelWidth = 330

function FlowMaterialPanel() {
	const { tabContents, tabList, tab } = useTab()
	const { keyword, onSearchChange, agentType, setAgentType } = useMaterialSearch({ tab })
	const { show, setShow, stickyButtonStyle } = useMaterialPanel()

	// Cache Panel Provider value with useMemo to avoid unnecessary rerenders
	const providerValue = useMemo(() => {
		return { agentType, setAgentType }
	}, [agentType, setAgentType])

	// Cache style object with useMemo to avoid recreating it on every render
	const panelStyle = useMemo(() => {
		return { width: show ? "280px" : 0 }
	}, [show])

	return (
		<PanelProvider agentType={agentType} setAgentType={setAgentType}>
			<StickyButton show={show} setShow={setShow} stickyButtonStyle={stickyButtonStyle} />
			<div
				className={clsx(styles.flowMaterialPanel, `${prefix}flow-material-panel`)}
				style={panelStyle}
			>
				<PanelHeader tabList={tabList} tab={tab} />

				<SearchBar keyword={keyword} onSearchChange={onSearchChange} />

				<MaterialContent tabContents={tabContents} tab={tab} keyword={keyword} />
			</div>
		</PanelProvider>
	)
}

export default React.memo(FlowMaterialPanel)


import { useState, useEffect, useMemo } from "react"
import { useFlowUI } from "@/DelightfulFlow/context/FlowContext/useFlow"

export default function useMaterialPanel() {
	//  use专用ofhook替代all量useFlow，decrease不必要ofrerender
	const { showMaterialPanel, setShowMaterialPanel } = useFlowUI()
	const [isEditing, setIsEditing] = useState(false)

	//  useuseMemocache样式object，avoid each timerendercreatenewobject
	const stickyButtonStyle = useMemo(() => {
		return {
			top: "91px",
			left: showMaterialPanel ? "298px" : "4px",
		}
	}, [showMaterialPanel])

	useEffect(() => {
		if (isEditing) {
			setShowMaterialPanel?.(false)
		}
	}, [isEditing, setShowMaterialPanel])

	return {
		show: showMaterialPanel,
		setShow: setShowMaterialPanel,
		isEditing,
		setIsEditing,
		stickyButtonStyle,
	}
} 

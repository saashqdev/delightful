import { useState, useEffect, useMemo } from "react"
import { useFlowUI } from "@/DelightfulFlow/context/FlowContext/useFlow"

export default function useMaterialPanel() {
	//  use dedicated hook instead of using useFlow in all places, decrease unnecessary rerenders
	const { showMaterialPanel, setShowMaterialPanel } = useFlowUI()
	const [isEditing, setIsEditing] = useState(false)

	//  use useMemo to cache style object, avoid creating new object on each render
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

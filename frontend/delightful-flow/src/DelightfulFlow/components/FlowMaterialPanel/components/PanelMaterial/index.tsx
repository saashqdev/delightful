import { prefix } from "@/DelightfulFlow/constants"
import clsx from "clsx"
import React, { useCallback, useMemo, useRef } from "react"
import useMaterial from "./hooks/useMaterial"
import styles from "./index.module.less"
import NodeGroups from "./components/NodeGroups"

export interface PanelMaterialProps {
	keyword: string
	// Whether from endpoint menu
	isHoverMenu?: boolean
	// Item passed from upper layer
	MaterialItemFn: (props: Record<string, any>) => JSX.Element | null
}

// Use React.memo to wrap PanelMaterial component, avoiding unnecessary re-renders
const PanelMaterial = React.memo(
	function PanelMaterial({ keyword, MaterialItemFn }: PanelMaterialProps) {
		const { nodeList, filterNodeGroups, getGroupNodeList } = useMaterial({ keyword })
		const containerRef = useRef<HTMLDivElement>(null)

		//  useuseCallbackoptimizationrenderMaterialItemfunction，避免不必要ofrenewcreate
		const renderMaterialItem = useCallback(
			(n: any, extraProps: Record<string, any> = {}) => {
				//  use解构赋valuegetschemainofproperty
				const { key, headerRight, ...restSchema } = n.schema
				//  createCHSitem固定ofkey，avoid each timerendergeneratenewstring
				const itemKey = key || `item-${restSchema?.id}`

				//  直接returnMaterialItemFncomponent，传递必要ofprops
				return <MaterialItemFn {...restSchema} {...extraProps} key={itemKey} />
			},
			[MaterialItemFn],
		)

		//  useuseMemooptimizationnodelistrender，只在nodeList或MaterialItemFn变化时renewcalculate
		const renderedNodeList = useMemo(() => {
			return nodeList.map((n, i) => {
				const { key, headerRight, ...restSchema } = n.schema
				const itemKey = key || `item-${restSchema?.id}`
				return <MaterialItemFn {...restSchema} key={itemKey} />
			})
		}, [nodeList, MaterialItemFn])

		return (
			<div
				ref={containerRef}
				className={clsx(styles.panelMaterial, `${prefix}panel-material-list`)}
				onClick={(e) => e.stopPropagation()}
			>
				<NodeGroups
					filterNodeGroups={filterNodeGroups}
					renderedNodeList={renderedNodeList}
					getGroupNodeList={getGroupNodeList}
					renderMaterialItem={renderMaterialItem}
					MaterialItemFn={MaterialItemFn}
				/>
			</div>
		)
	},
	(prevProps, nextProps) => {
		// onlykeywordre-render only when changednewrender
		return (
			prevProps.keyword === nextProps.keyword &&
			prevProps.MaterialItemFn === nextProps.MaterialItemFn
		)
	},
)

export default PanelMaterial


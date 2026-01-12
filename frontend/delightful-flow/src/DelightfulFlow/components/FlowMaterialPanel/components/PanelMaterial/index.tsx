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

		//  use useCallback to optimize rendering Material Item function, avoid unnecessary new creation
		const renderMaterialItem = useCallback(
			(n: any, extraProps: Record<string, any> = {}) => {
				//  use destructuring to get schema in property
				const { key, headerRight, ...restSchema } = n.schema
				//  create item fixed key, avoid generating new string every render
				const itemKey = key || `item-${restSchema?.id}`

				//  directly return MaterialItemFn component, pass necessary props
				return <MaterialItemFn {...restSchema} {...extraProps} key={itemKey} />
			},
			[MaterialItemFn],
		)

		//  use useMemo to optimize node list rendering, only recalculate when nodeList or MaterialItemFn changes
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
		// only re-render when keyword changes
		return (
			prevProps.keyword === nextProps.keyword &&
			prevProps.MaterialItemFn === nextProps.MaterialItemFn
		)
	},
)

export default PanelMaterial


import React, { memo, useMemo } from "react"
import clsx from "clsx"
import { prefix } from "@/DelightfulFlow/constants"
import styles from "../index.module.less"
import NodeGroup from "./NodeGroup"
import VirtualNodeList from "./VirtualNodeList"

// define a threshold，whennodeenable virtual scroll when count exceeds threshold
const VIRTUAL_SCROLL_THRESHOLD = 30
const NODE_ITEM_HEIGHT = 40 // estimated pernodeitem height，need based on actualUIadjust

interface NodeGroupsProps {
	filterNodeGroups: any[]
	renderedNodeList: JSX.Element[]
	getGroupNodeList: any
	renderMaterialItem: (n: any, extraProps?: Record<string, any>) => JSX.Element | null
	MaterialItemFn: (props: Record<string, any>) => JSX.Element | null
}

const NodeGroups = memo(
	({
		filterNodeGroups,
		renderedNodeList,
		getGroupNodeList,
		renderMaterialItem,
		MaterialItemFn,
	}: NodeGroupsProps) => {
		// Determinewhether virtual scroll needed
		const shouldUseVirtualScroll = useMemo(() => {
			return renderedNodeList.length > VIRTUAL_SCROLL_THRESHOLD
		}, [renderedNodeList.length])

		if (filterNodeGroups?.length === 0) {
			// Ifnodecount exceeds threshold，use virtual scroll
			if (shouldUseVirtualScroll) {
				return <VirtualNodeList items={renderedNodeList} itemHeight={NODE_ITEM_HEIGHT} />
			}
			return <>{renderedNodeList}</>
		}

		// Rendernodegroup
		const groupElements = filterNodeGroups.map((nodeGroup, i) => (
			<NodeGroup
				key={`group-${i}`}
				nodeGroup={nodeGroup}
				getGroupNodeList={getGroupNodeList}
				renderMaterialItem={renderMaterialItem}
				MaterialItemFn={MaterialItemFn}
			/>
		))

		return (
			<div className={clsx(styles.nodeGroups, `${prefix}node-groups`)}>{groupElements}</div>
		)
	},
	(prevProps, nextProps) => {
		// deep comparison may not be efficient，only compare reference here，may need custom comparison in actual usefunction
		return (
			prevProps.filterNodeGroups === nextProps.filterNodeGroups &&
			prevProps.renderedNodeList === nextProps.renderedNodeList &&
			prevProps.renderMaterialItem === nextProps.renderMaterialItem &&
			prevProps.MaterialItemFn === nextProps.MaterialItemFn
		)
	},
)

export default NodeGroups


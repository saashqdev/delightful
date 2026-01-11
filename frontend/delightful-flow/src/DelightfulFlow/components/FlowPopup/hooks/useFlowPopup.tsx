import { getExecuteNodeGroupList } from "@/DelightfulFlow/constants"
import { NodeSchema } from "@/DelightfulFlow/register/node"
import { useDebounceEffect, useMemoizedFn } from "ahooks"
import i18next from "i18next"
import React, { useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import PanelMaterial from "../../FlowMaterialPanel/components/PanelMaterial"
import PopupNode from "../PopupNode"
import styles from "../index.module.less"

export const TabKey = {
	BaseNode: "1",
	WorkFlow: "2",
}

export default function useFlowPopup() {
	const { t } = useTranslation()
	const defaultGroupList = Object.entries(getExecuteNodeGroupList())

	// Node group list
	const [nodeGroupList, setNodeGroupList] = useState(defaultGroupList)
	const [keyword, setKeyword] = useState("")
	const [debounceKeyword, setDebounceKeyword] = useState(keyword)

	useDebounceEffect(
		() => {
			setDebounceKeyword(keyword)
		},
		[keyword],
		{
			wait: 500,
		},
	)

	// Currently rendered node list elements
	const NodeList = useMemo(() => {
		if (nodeGroupList.length === 0) {
			return (
				<div className={styles.nodeList}>
					<span className={styles.noContent}>
						{i18next.t("flow.withoutMatchResult", { ns: "delightfulFlow" })}
					</span>
				</div>
			)
		}
		return (
			<PanelMaterial
				keyword={debounceKeyword}
				MaterialItemFn={({ showIcon, inGroup, ...node }) => (
					<PopupNode
						node={node as NodeSchema}
						key={node.id}
						showIcon={showIcon}
						inGroup={inGroup}
					/>
				)}
			/>
		)
	}, [nodeGroupList.length, debounceKeyword])

	const onTabChange = useMemoizedFn((key) => {
		switch (key) {
			case TabKey.BaseNode:
				setNodeGroupList(defaultGroupList)
				break
			case TabKey.WorkFlow:
				setNodeGroupList([])
				break
			default:
				break
		}
	})

	return {
		keyword,
		setKeyword,
		NodeList,
		onTabChange,
	}
}


import { Skeleton } from "antd"
import clsx from "clsx"
import React, { memo } from "react"
import { prefix } from "@/DelightfulFlow/constants"
import styles from "../index.module.less"

interface NodeContentProps {
	showParamsComp: boolean
	ParamsComp: React.ComponentType | null
}

const NodeContent = memo(
	({ showParamsComp, ParamsComp }: NodeContentProps) => {
		return (
			<div
				className={clsx(styles.paramsComp, `${prefix}params-comp`, {
					[styles.isEmpty]: !showParamsComp,
					"is-empty": !showParamsComp,
				})}
			>
				{ParamsComp && showParamsComp && <ParamsComp />}
				{!showParamsComp && (
					<>
						<Skeleton />
						<Skeleton />
					</>
				)}
			</div>
		)
	},
	(prevProps, nextProps) => {
		// Custom comparator; rerender only when key props change
		// Compare showParamsComp state changes
		if (prevProps.showParamsComp !== nextProps.showParamsComp) {
			return false // Not equal, rerender needed
		}

		// Compare ParamsComp changes
		// If one is null and the other not, or refs differ, rerender
		if (
			(!prevProps.ParamsComp && nextProps.ParamsComp) ||
			(prevProps.ParamsComp && !nextProps.ParamsComp) ||
			prevProps.ParamsComp !== nextProps.ParamsComp
		) {
			return false // Not equal, rerender needed
		}

		// All key props equal, no rerender needed
		return true
	},
)

export default NodeContent


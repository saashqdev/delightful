import type { HTMLAttributes, ReactNode } from "react"
import { memo, useMemo } from "react"
import type { StructureItem } from "@/types/organization"
import { createStyles } from "antd-style"
import { Flex } from "antd"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"

import { IconChevronRight, IconSitemap } from "@tabler/icons-react"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"

interface DepartmentProps extends HTMLAttributes<HTMLDivElement> {
	data: StructureItem
	/** 是否显示成员数量 */
	showMemberCount?: boolean

	/** 子项箭头区域 - 自定义渲染 */
	itemArrow?: boolean | ((item: StructureItem) => ReactNode)
}

// eslint-disable-next-line react-refresh/only-export-components
export const useStyles = createStyles(({ isDarkMode, css, prefixCls, token }) => {
	return {
		container: css`
			padding: 10px;
			cursor: pointer;
			width: 100%;
		`,
		count: css`
			overflow: hidden;
			color: ${isDarkMode ? token.delightfulColorScales.grey[6] : token.delightfulColorUsages.text[2]};
			text-overflow: ellipsis;
			font-size: 12px;
			font-weight: 400;
			line-height: 16px;
		`,
		arrow: css`
			color: ${isDarkMode
				? token.delightfulColorScales.grey[2]
				: token.delightfulColorUsages.text[2]} !important;
		`,
		departmentIcon: css`
			color: white;
			background: ${token.delightfulColorScales.brand[5]};
			--${prefixCls}-border-radius: 8px;
			width: 32px;
			height: 32px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 8px;
		`,
	}
})

/**
 * 部门节点
 */
const Department = memo(
	({ showMemberCount, itemArrow = true, data: item, ...props }: DepartmentProps) => {
		const { styles } = useStyles()

		const arrow = useMemo(() => {
			if (typeof itemArrow === "function") {
				return itemArrow(item)
			}
			return itemArrow ? (
				<DelightfulIcon component={IconChevronRight} className={styles.arrow} size={24} />
			) : null
		}, [item, itemArrow, styles.arrow])

		return (
			<Flex align="center" className={styles.container} justify="space-between" {...props}>
				<Flex gap={8} align="center">
					<div className={styles.departmentIcon}>
						<DelightfulIcon color="currentColor" size={20} component={IconSitemap} />
					</div>
					{item.name}
					{showMemberCount ? (
						<span className={styles.count}>({item.employee_sum})</span>
					) : null}
				</Flex>
				{arrow}
			</Flex>
		)
	},
)

export default Department

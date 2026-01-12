import { useMemo } from "react"
import { cx } from "antd-style"

/**
 * Generate class name combination for Markdown component
 */
export const useClassName = (props: {
	mdStyles: Record<string, string>
	className?: string
	classNameRef: React.MutableRefObject<string>
}) => {
	const { mdStyles, className, classNameRef } = props

	// Use memoized class name combination to reduce unnecessary recalculation
	return useMemo(() => {
		// 在function内部getclassNameRef.current
		const refClassName = classNameRef.current
		return cx(
			mdStyles.root,
			mdStyles.a,
			mdStyles.blockquote,
			mdStyles.code,
			mdStyles.details,
			mdStyles.header,
			mdStyles.hr,
			mdStyles.img,
			mdStyles.kbd,
			mdStyles.list,
			mdStyles.p,
			mdStyles.pre,
			mdStyles.strong,
			mdStyles.table,
			mdStyles.video,
			mdStyles.math,
			className,
			refClassName,
		)
	}, [
		mdStyles.root,
		mdStyles.a,
		mdStyles.blockquote,
		mdStyles.code,
		mdStyles.details,
		mdStyles.header,
		mdStyles.hr,
		mdStyles.img,
		mdStyles.kbd,
		mdStyles.list,
		mdStyles.p,
		mdStyles.pre,
		mdStyles.strong,
		mdStyles.table,
		mdStyles.video,
		mdStyles.math,
		className,
		classNameRef, // 使用引用本身作为依赖
	])
}

import { useMemo } from "react"
import { cx } from "antd-style"

/**
 * 生成Markdowncomponent使用的样式class名组合
 */
export const useClassName = (props: {
	mdStyles: Record<string, string>
	className?: string
	classNameRef: React.MutableRefObject<string>
}) => {
	const { mdStyles, className, classNameRef } = props

	// 使用记忆化的class名组合，减少不必要的重新计算
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

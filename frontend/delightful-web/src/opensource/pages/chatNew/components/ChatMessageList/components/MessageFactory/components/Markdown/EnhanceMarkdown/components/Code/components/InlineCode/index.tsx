import { useMessageRenderContext } from "@/opensource/components/business/MessageRenderProvider/hooks"
import { useStyles } from "./styles"
import { cx } from "antd-style"
import { memo } from "react"
interface DelightfulInlineCodeProps {
	data?: string
	className?: string
}

const DelightfulInlineCode = memo(function DelightfulInlineCode(props: DelightfulInlineCodeProps) {
	const { styles } = useStyles()
	const { hiddenDetail } = useMessageRenderContext()
	const { data: propsValue, className } = props

	// 处理边界情况
	if (!propsValue) {
		return null
	}

	if (hiddenDetail) return propsValue

	return <code className={cx(styles.default, className)}>{propsValue}</code>
})

export default DelightfulInlineCode

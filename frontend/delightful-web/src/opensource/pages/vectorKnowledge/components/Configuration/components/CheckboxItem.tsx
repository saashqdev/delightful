import { IconCircle, IconCircleCheckFilled } from "@tabler/icons-react"
import { cx } from "antd-style"
import { useVectorKnowledgeConfigurationStyles } from "../styles"

interface CheckboxItemProps {
	checked: boolean
}

/**
 * Custom checkbox component
 */
export default function CheckboxItem({ checked }: CheckboxItemProps) {
	const { styles } = useVectorKnowledgeConfigurationStyles()

	if (checked) {
		return (
			<div className={cx(styles.checkboxItem, styles.checked)}>
				<IconCircleCheckFilled />
			</div>
		)
	}

	return (
		<div className={styles.checkboxItem}>
			<IconCircle />
		</div>
	)
}

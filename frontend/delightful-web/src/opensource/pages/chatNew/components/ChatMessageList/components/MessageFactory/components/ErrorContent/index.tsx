import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { Flex } from "antd"
import { createStyles } from "antd-style"
import type { ComponentProps } from "react"

const useStyles = createStyles(({ css, token }) => ({
	container: css`
		padding: 7px;
		width: 100%;
		min-width: 340px;
	`,
	invalidText: css`
		color: ${token.delightfulColorUsages.text[2]};
		text-align: justify;
		font-size: 14px;
		line-height: 20px;
		white-space: pre-wrap;
	`,
}))

interface ErrorContentProps extends ComponentProps<"div"> {
	onReport?: () => void
	onRetry?: () => void
}

function ErrorContent({ onReport, onRetry, className, ...props }: ErrorContentProps) {
	const { styles, cx } = useStyles()

	return (
		<Flex
			vertical
			className={cx(styles.invalidText, className)}
			justify="center"
			align="center"
			gap={10}
			{...props}
		>
			<div className={styles.invalidText}>
				未知的error原因导致无法正常渲染内容，请联系 Delightful 开发团队。
			</div>
			{onReport && (
				<DelightfulButton type="link" onClick={onReport}>
					上报exceptionerror
				</DelightfulButton>
			)}
			{onRetry && (
				<DelightfulButton type="link" onClick={onRetry}>
					retry
				</DelightfulButton>
			)}
		</Flex>
	)
}

export default ErrorContent

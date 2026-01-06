import { createStyles } from "antd-style"
import { Suspense, type PropsWithChildren } from "react"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"

const useStyles = createStyles(({ token, css, prefixCls }) => {
	return {
		main: {
			padding: "0 18px 24px",
			width: "100%",
			height: "100vh",
			backgroundColor: token.colorBgContainer,
		},

		spin: css`
			.${prefixCls}-spin-blur {
				opacity: 1;
			}

			& > div > .${prefixCls}-spin {
				--${prefixCls}-spin-content-height: unset;
				max-height: unset;
			}
		`,
	}
})

function LoadingFallback({ children }: PropsWithChildren) {
	const { styles } = useStyles()

	return (
		<Suspense
			fallback={
				<DelightfulSpin spinning className={styles.spin}>
					<div style={{ height: "100vh" }} />
				</DelightfulSpin>
			}
		>
			{children}
		</Suspense>
	)
}

export default LoadingFallback

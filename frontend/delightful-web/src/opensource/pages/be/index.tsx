import GlobalErrorBoundary from "@/opensource/components/fallback/GlobalErrorBoundary"
import AuthenticationProvider from "@/opensource/providers/AuthenticationProvider"
import GlobalChatProvider from "@/opensource/providers/ChatProvider"
import BeDelightful from "@/opensource/pages/beDelightful"
import { reportErrorLog } from "@/opensource/pages/beDelightful/utils/api"
import Logger from "@/utils/log/Logger"
import { createStyles } from "antd-style"
import { isEmpty } from "lodash-es"
import { observer } from "mobx-react-lite"
import { useEffect } from "react"
import useDrag from "@/opensource/hooks/electron/useDrag"
import { delightful } from "@/enhance/delightfulElectron"

// const console = new Logger("super")

const useStyles = createStyles(({ token }) => ({
	container: {
		flex: "auto",
		overflow: "hidden",
		height: "100%",
		width: "100%",
	},
	header: {
		width: "100%",
		height: 40,
		borderBottom: `1px solid ${token.colorBorder}`,
	},
}))

// Only the entrance for super delightful
function Super() {
	const { styles } = useStyles()

	const { onMouseDown } = useDrag()
	// const isDevelopment = process.env.NODE_ENV === "development"

	// Add global uncaught error and Promise reject listeners
	// useEffect(() => {
	// 	// Do not catch errors in development mode
	// 	if (isDevelopment) return

	// 	// Handle regular errors
	// 	const handleError = (event: ErrorEvent) => {
	// 		event.preventDefault()
	// 		const errorInfo = {
	// 			message: event.message,
	// 			filename: event.filename,
	// 			lineno: event.lineno,
	// 			colno: event.colno,
	// 			type: event.type,
	// 			stack: event?.error?.stack,
	// 			timestamp: new Date(),
	// 			error_type: "global_error",
	// 		}
	// 		console.log(event, "xxxx")
	// 		console.error("Uncaught global error", errorInfo)
	// 		reportErrorLog({ log: JSON.stringify(errorInfo) })
	// 	}

	// 	// Handle uncaught Promise rejections
	// 	const handleRejection = (event: PromiseRejectionEvent) => {
	// 		event.preventDefault()
	// 		const errorInfo = {
	// 			reason: event.reason,
	// 			promise: event.promise,
	// 			error_type: "global_unhandled_rejection",
	// 		}
	// 		console.error("Uncaught Promise rejection", event)
	// 		if (isEmpty(event.reason) && isEmpty(event.promise)) {
	// 			return
	// 		}
	// 		reportErrorLog({ log: JSON.stringify(errorInfo) })
	// 	}

	// 	// Add event listeners
	// 	window.addEventListener("error", handleError)
	// 	window.addEventListener("unhandledrejection", handleRejection)

	// 	// Cleanup function
	// 	return () => {
	// 		window.removeEventListener("error", handleError)
	// 		window.removeEventListener("unhandledrejection", handleRejection)
	// 	}
	// }, [])

	return (
		<div className={styles.container}>
			{delightful?.env?.isElectron?.() && (
				<div className={styles.header} onMouseDown={onMouseDown} />
			)}
			<GlobalErrorBoundary>
				<AuthenticationProvider>
					<GlobalChatProvider>
						<BeDelightful />
					</GlobalChatProvider>
				</AuthenticationProvider>
			</GlobalErrorBoundary>
		</div>
	)
}

export default observer(Super)

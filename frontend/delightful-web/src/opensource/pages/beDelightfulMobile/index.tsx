import { Outlet } from "react-router"

export default function BeDelightfulMobile() {
	// Add global uncaught error and Promise reject listeners
	// useEffect(() => {
	// 	// Handle regular errors
	// 	const handleError = (event: ErrorEvent) => {
	// 		event.preventDefault()
	// 		const errorInfo = {
	// 			message: event.message,
	// 			filename: event.filename,
	// 			lineno: event.lineno,
	// 			colno: event.colno,
	// 			error: event.error,
	// 		}
	// 		console.error("Uncaught global error", errorInfo)
	// 		reportErrorLog({ log: JSON.stringify(event) })
	// 	}

	// 	// Handle uncaught Promise rejections
	// 	const handleRejection = (event: PromiseRejectionEvent) => {
	// 		event.preventDefault()
	// 		const errorInfo = {
	// 			reason: event.reason,
	// 			promise: event.promise,
	// 		}
	// 		reportErrorLog({ log: JSON.stringify(event) })
	// 		console.error("Uncaught Promise reject", errorInfo)
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
	return <Outlet />
}

import { createStyles } from "antd-style"
import { useEffect, useRef, useState } from "react"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { Flex } from "antd"

interface IsolatedHTMLRendererProps {
	content: string
	sandboxType?: "iframe" | "shadow-dom"
	className?: string
}

const useStyles = createStyles(({ css }) => {
	return {
		rendererContainer: css`
			width: 100%;
			height: 100%;
			overflow: auto;
		`,
		iframe: css`
			width: 100%;
			height: 100%;
			border: none;
			display: block;
		`,
		shadowHost: css`
			width: 100%;
			height: 100%;
			display: block;
			position: relative;
		`,
		loadingContainer: css`
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
		`,
	}
})

// External resource URLs
const TAILWIND_CSS_URL = "https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.0/tailwind.min.css"
const ECHARTS_JS_URL = "https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js"

// Decode HTML entities
function decodeHTMLEntities(html: string): string {
	const textarea = document.createElement("textarea")
	textarea.innerHTML = html
	return textarea.value
}

export default function IsolatedHTMLRenderer({
	content,
	sandboxType = "iframe",
	className,
}: IsolatedHTMLRendererProps) {
	const { styles, cx } = useStyles()
	const containerRef = useRef<HTMLDivElement>(null)
	const iframeRef = useRef<HTMLIFrameElement>(null)
	const [iframeLoaded, setIframeLoaded] = useState(false)
	const currentOrigin = window.location?.origin

	// Handle iframe content updates
	useEffect(() => {
		if (sandboxType === "iframe" && iframeRef.current && content) {
			// Create a message listener
			const handleMessage = (event: MessageEvent) => {
				// Only process messages from this iframe
				if (event.source !== iframeRef.current?.contentWindow) return
				if (event.data && event.data.type === "iframeReady") {
					// Iframe is ready to receive content
					setIframeLoaded(true)
				}
			}

			// Add listener
			window.addEventListener("message", handleMessage)

			// If iframe is loaded, send content
			if (iframeLoaded && content) {
				try {
					// Decode HTML entities
					const decodedContent = decodeHTMLEntities(content)
					// Build complete HTML content
					const fullContent = `
						<html translate="no">
						<head>
							<meta name="google" content="notranslate">
							<link rel="stylesheet" href="${TAILWIND_CSS_URL}">
							<script src="${ECHARTS_JS_URL}"></script>
							<style>
								body {
									translate: no !important;
								}
							</style>
							<script>
								// Inject script directly inside HTML to avoid cross-origin access
								document.addEventListener("DOMContentLoaded", function() {
									// Add target attribute to all links
									document.querySelectorAll("a").forEach(link => {
										const href = link.getAttribute("href");
										// Check whether this is an anchor link (# prefix)
										if (href && href.startsWith("#")) {
											// Add click handler for anchor links
											link.addEventListener("click", function(e) {
												e.preventDefault();
												const targetId = href.substring(1);
												const targetElement = document.getElementById(targetId);
												if (targetElement) {
													targetElement.scrollIntoView({ behavior: "smooth" });
												}
											});
										} else {
											// Non-anchor links open in a new window
											link.setAttribute("target", "_blank");
											link.setAttribute("rel", "noopener noreferrer");
										}
									});

									// Capture and handle script errors
									window.onerror = function(message, source, lineno, colno, error) {
										console.error("HTML render error:", message);
										return true; // Prevent bubbling
									};
								});
							</script>
						</head>
						<body>
							${decodedContent}
						</body>
						</html>
					`
					// Send content to iframe
					try {
						if (iframeRef.current && iframeRef.current.contentWindow) {
							iframeRef.current.contentWindow.postMessage(
								{
									type: "setContent",
									content: fullContent,
								},
								"*",
							)
						} else {
							console.error("iframe or contentWindow unavailable")
						}
					} catch (postError) {
						console.error("Error sending message to iframe:", postError)
					}
				} catch (error) {
					console.error("Error handling iframe content:", error)
				}
			}

			// Cleanup listener
			return () => {
				window.removeEventListener("message", handleMessage)
			}
		}
	}, [content, sandboxType, iframeLoaded])

	// Show loading state when content is empty
	if (!content || content.trim() === "") {
		return (
			<div className={cx(styles.rendererContainer, styles.loadingContainer, className)}>
				<Flex
					vertical
					align="center"
					justify="center"
					style={{ width: "100%", height: "100%" }}
				>
					<DelightfulSpin spinning />
				</Flex>
			</div>
		)
	}

	return (
		<div className={cx(styles.rendererContainer, className)}>
			{sandboxType === "iframe" ? (
				<iframe
					ref={iframeRef}
					className={styles.iframe}
					title="Isolated HTML Content"
					sandbox="allow-scripts allow-modals"
					translate="no"
					src={`${currentOrigin}/html-messenger.html`}
					onLoad={() => setIframeLoaded(true)}
				/>
			) : (
				<div ref={containerRef} className={styles.shadowHost} translate="no" />
			)}
		</div>
	)
}

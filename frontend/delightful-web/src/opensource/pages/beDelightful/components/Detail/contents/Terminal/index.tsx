import ShellIcon from "@/opensource/pages/beDelightful/assets/svg/Frame.svg"
import { memo, useEffect, useRef } from "react"
import CommonFooter from "../../components/CommonFooter"
import CommonHeader from "../../components/CommonHeader"
import { Terminal } from "xterm"
import { FitAddon } from "xterm-addon-fit"
import "xterm/css/xterm.css"
import "./index.css"
import { useStyles } from "./styles"

interface TerminalProps {
	data: any
	totalFiles: number
	type: string
	currentIndex: number
	onPrevious: () => void
	onNext: () => void
	onFullscreen: () => void
	onDownload: () => void
	hasUserSelectDetail: boolean
	setUserSelectDetail: (detail: any) => void
	isFromNode: boolean
	onClose: () => void
	userSelectDetail: any
	isFullscreen?: boolean
}

// Simple string hash function that avoids specific operators to satisfy eslint
const hashString = (str: string): string => {
	let hash = 0
	for (let i = 0; i < str.length; i += 1) {
		const char = str.charCodeAt(i)
		hash = hash * 5 - hash + char
		hash = Math.floor(hash % 2147483647) // Keep within 32-bit int range
	}
	return hash.toString(36)
}

// Lightweight syntax highlighting helper
const highlightSyntax = (code: string): React.ReactNode => {
	// Keyword regex
	const keywordRegex =
		/\b(const|let|var|function|return|if|else|for|while|class|import|export|from|async|await|try|catch|throw|new|this|super|extends|implements|interface|type|enum|public|private|protected|static|get|set)\b/g
	// String regex
	const stringRegex = /(["'`])(.*?)\1/g
	// Comment regex
	const commentRegex = /\/\/(.*?)($|\n)|\/\*(.*?)\*\//gs
	// Function regex
	const functionRegex = /\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/g
	// Number regex
	const numberRegex = /\b\d+\.?\d*\b/g

	// Process comments first to avoid highlighting code inside comments
	let highlighted = code.replace(commentRegex, '<span class="comment">$&</span>')

	// Highlight the rest
	highlighted = highlighted
		.replace(keywordRegex, '<span class="keyword">$&</span>')
		.replace(stringRegex, '<span class="string">$&</span>')
		.replace(functionRegex, '<span class="function">$1</span>(')
		.replace(numberRegex, '<span class="number">$&</span>')

	// Return HTML string wrapped for dangerouslySetInnerHTML
	return <span dangerouslySetInnerHTML={{ __html: highlighted }} />
}

export default memo(function Shell(props: TerminalProps) {
	const {
		data,
		totalFiles,
		type,
		currentIndex,
		onPrevious,
		onNext,
		onFullscreen,
		onDownload,
		hasUserSelectDetail,
		setUserSelectDetail,
		isFromNode,
		onClose,
		userSelectDetail,
		isFullscreen,
	} = props
	const { styles } = useStyles()
	const terminalRef = useRef(null) as any
	const term = useRef(null) as any

	const { command, output, exit_code } = data

	useEffect(() => {
		const termInstance = new Terminal({
			disableStdin: true,
			theme: {
				background: "#000000",
				foreground: "#00ff00",
			},
		})
		const fitAddon = new FitAddon()
		termInstance.loadAddon(fitAddon)
		termInstance.open(terminalRef.current)
		fitAddon.fit()

		const observer = new ResizeObserver(() => fitAddon.fit())
		observer.observe(terminalRef.current)

		term.current = termInstance

		return () => {
			observer.disconnect()
			termInstance.dispose()
		}
	}, [])

	useEffect(() => {
		if (terminalRef.current) {
			console.log(command, output, exit_code, "xxxx")
			term.current.writeln(`$ ${command}`)
			const processedOutput = output.replace(/\n/g, "\r\n")
			term.current.writeln(processedOutput)
			term.current.writeln(`Exit code: ${exit_code}`)
		} else {
			return undefined
		}

		return () => {
			term.current.dispose()
		}
	}, [command, output, exit_code])

	return (
		<div className={styles.terminalContainer}>
			<CommonHeader
				title="Terminal"
				icon={<img src={ShellIcon} alt="" />}
				type={type}
				currentAttachmentIndex={currentIndex}
				totalFiles={totalFiles}
				onPrevious={onPrevious}
				onNext={onNext}
				onFullscreen={onFullscreen}
				onDownload={onDownload}
				hasUserSelectDetail={hasUserSelectDetail}
				setUserSelectDetail={setUserSelectDetail}
				isFromNode={isFromNode}
				onClose={onClose}
				isFullscreen={isFullscreen}
			/>
			<div className={styles.terminalBody} ref={terminalRef}></div>
			{isFromNode && (
				<CommonFooter
					setUserSelectDetail={setUserSelectDetail}
					userSelectDetail={userSelectDetail}
					onClose={onClose}
				/>
			)}
		</div>
	)
})

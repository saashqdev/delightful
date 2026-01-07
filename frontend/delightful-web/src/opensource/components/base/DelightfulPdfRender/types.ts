export interface DelightfulPdfRenderProps {
	/** PDF file source, can be File object or URL string */
	file?: File | string | null
	/** Whether to show toolbar */
	showToolbar?: boolean
	/** Initial scale ratio */
	initialScale?: number
	/** Minimum scale ratio */
	minScale?: number
	/** Maximum scale ratio */
	maxScale?: number
	/** Scale step */
	scaleStep?: number
	/** Container height */
	height?: string | number
	/** Container width */
	width?: string | number
	/** Whether to enable keyboard shortcuts */
	enableKeyboard?: boolean
	/** Load error callback */
	onLoadError?: (error: Error) => void
	/** Load success callback */
	onLoadSuccess?: (pdf: any) => void
}

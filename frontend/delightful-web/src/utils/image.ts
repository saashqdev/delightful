/**
 * Convert SVG to PNG
 * @param svg - SVG string
 * @param width - Width
 * @param height - Optional height; when provided, caps the maximum height while preserving aspect ratio
 * @returns Promise<string> Base64 string of the PNG image, throws on failure
 */
export const convertSvgToPng = (
	svg: string,
	width: number = 600,
	height?: number,
): Promise<string> => {
	return new Promise((resolve, reject) => {
		try {
			const canvas = document.createElement("canvas")
			const ctx = canvas.getContext("2d")
			if (!ctx) {
				throw new Error("Unable to acquire canvas context")
			}

			const img = new Image()
			// Handle load errors
			img.onerror = () => reject(new Error("SVG image failed to load"))

			// Convert after image loads
			img.onload = () => {
				try {
					// Read original SVG dimensions
					const parser = new DOMParser()
					const svgDoc = parser.parseFromString(svg, "image/svg+xml")
					const svgElement = svgDoc.documentElement

					// Try to obtain width/height from SVG
					let originalWidth
					let originalHeight

					if (svgElement.hasAttribute("width") && svgElement.hasAttribute("height")) {
						originalWidth = parseFloat(svgElement.getAttribute("width") || "0")
						originalHeight = parseFloat(svgElement.getAttribute("height") || "0")
					} else if (svgElement.hasAttribute("viewBox")) {
						const viewBox = svgElement.getAttribute("viewBox")?.split(/\s+/)
						if (viewBox && viewBox.length >= 4) {
							originalWidth = parseFloat(viewBox[2])
							originalHeight = parseFloat(viewBox[3])
						}
					}

					// Fallback to intrinsic dimensions when missing in SVG
					if (
						!originalWidth ||
						!originalHeight ||
						originalWidth <= 0 ||
						originalHeight <= 0
					) {
						originalWidth = img.naturalWidth || width
						originalHeight = img.naturalHeight || width * 0.75 // Default 4:3 ratio
					}

					// Calculate aspect ratio
					const aspectRatio = originalHeight / originalWidth

					// Determine target size based on width
					const targetWidth = width
					let targetHeight = Math.round(width * aspectRatio)

					// Only cap height when provided
					if (typeof height === "number" && height > 0) {
						if (targetHeight > height) {
							targetHeight = height
						}
					}

					// Set canvas size
					canvas.width = targetWidth
					canvas.height = targetHeight

					// Draw the image
					ctx.drawImage(img, 0, 0, targetWidth, targetHeight)

					// Convert to PNG
					const pngUrl = canvas.toDataURL("image/png")
					resolve(pngUrl)
				} catch (err) {
					reject(new Error(`PNG conversion failed: ${err}`))
				}
			}

			// Convert SVG to base64 and set as image source
			const svgBase64 = `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`
			img.src = svgBase64
		} catch (err) {
			reject(new Error(`SVG handling failed: ${err}`))
		}
	})
}

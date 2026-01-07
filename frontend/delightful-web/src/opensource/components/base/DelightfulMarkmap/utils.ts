import { Markmap } from "markmap-view"
import { transformer } from "./markmap"

export function exportMarkmapToPng(data: string, width: number = 3840, height: number = 2160) {
	return new Promise<Blob>((resolve) => {
		const tempContainer = document.createElement("div")
		tempContainer.style.cssText = `
			position: fixed;
			top: -9999px;
			left: -9999px;
			width: ${width}px;
			height: ${height}px;
			visibility: hidden;
      background: #fff;
		`
		document.body.appendChild(tempContainer)

		// 克隆SVG到临时容器
		const tempSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg")

		// 清空克隆的SVG内容
		while (tempSvg.firstChild) {
			tempSvg.removeChild(tempSvg.firstChild)
		}

		tempSvg.setAttribute("width", `${width}px`)
		tempSvg.setAttribute("height", `${height}px`)
		tempSvg.style.width = `${width}px`
		tempSvg.style.height = `${height}px`
		tempContainer.appendChild(tempSvg)

		// 创建新的 Markmap 实例并设置数据
		const mm = Markmap.create(tempSvg, {
			autoFit: true,
			pan: false,
			zoom: false,
		})
		const { root } = transformer.transform(data)
		mm.setData(root)

		// Wait for rendering to complete
		setTimeout(() => {
			// Clone processed SVG to handle styles
			const clonedSvg = tempSvg.cloneNode(true) as SVGElement
			clonedSvg.setAttribute("width", `${width}px`)
			clonedSvg.setAttribute("height", `${height}px`)

			// Inline styles
			const styless = document.getElementsByTagName("style")
			const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs")
			Array.from(styless).forEach((style) => {
				defs.appendChild(style.cloneNode(true))
			})
			clonedSvg.insertBefore(defs, clonedSvg.firstChild)

			// Get SVG data and add XML declaration
			const svgData = new XMLSerializer().serializeToString(clonedSvg)
			const svgContent = `<?xml version="1.0" encoding="UTF-8" standalone="no"?>
				${svgData}`

			// Use base64 encoding
			const svgBase64 = `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svgContent)))}`

			// Create Image object
			const img = new Image()
			img.crossOrigin = "anonymous"
			img.onload = () => {
				// Create canvas, ensure 4K dimensions
				const canvas = document.createElement("canvas")
				canvas.width = width // 4K width
				canvas.height = height // 4K height

				const ctx = canvas.getContext("2d", { alpha: false })!
				ctx.imageSmoothingEnabled = true
				ctx.imageSmoothingQuality = "high"

				// Fill white background first
				ctx.fillStyle = "#ffffff"
				ctx.fillRect(0, 0, canvas.width, canvas.height)

				// Then draw the image
				ctx.drawImage(img, 0, 0, canvas.width, canvas.height)

				// Convert to PNG and download
				canvas.toBlob(
					(blob) => {
						if (blob) {
							resolve(blob)
						}
						// Clean up temporary DOM
						document.body.removeChild(tempContainer)
						mm.destroy()
					},
					"image/png",
					1.0,
				)
			}

			img.src = svgBase64
		}, 1000)
	})
}

/**
 * Draw a square group avatar on canvas from avatar URLs, supporting multiple rows/columns, and return a base64 image
 * @param urls Avatar URLs
 * @param options Settings
 * @param options.size Avatar size, default 300
 * @param options.gap Avatar gap, default 10
 * @param options.borderRadius Border radius for each avatar, default 10
 * @param options.col Column count, default 2
 * @returns Base64 image
 */
export function drawGroupAvatar(
	urls: string[],
  options: { size: number; gap: number; borderRadius: number; col: 2 | 3 },
  c?: HTMLCanvasElement
) {
	const { size = 340, gap = 10, col = 3 } = options


	const canvas = c || document.createElement("canvas")
	canvas.width = size * col + gap * (col - 1)
	canvas.height = size * col + gap * (col - 1)

	const ctx = canvas.getContext("2d")
	if (!ctx) return ""

	// Create a Promise array to handle all image loads
	const loadImages = urls.slice(0, col * col + 1).map((url, index) => {
		return new Promise<void>((resolve) => {
      const image = new Image()
      image.crossOrigin="anonymous"
			
      image.onload = () => {

				// Save current drawing state
        ctx.save()
        
        				
				const x = (index % col) * (size + gap)
				const y = Math.floor(index / col) * (size + gap)

				// // Top-left corner radius
				// ctx.beginPath()
				// ctx.arc(x + borderRadius, y + borderRadius, borderRadius, 0, Math.PI / 2)
        // ctx.clip()
        
			// // Top-right corner radius
        // ctx.beginPath()
        // ctx.arc(x + size - borderRadius, y + borderRadius, borderRadius, Math.PI / 2, Math.PI)
        // ctx.clip()

			// // Bottom-left corner radius
        // ctx.beginPath()
        // ctx.arc(x + borderRadius, y + size - borderRadius, borderRadius, Math.PI, Math.PI * 3 / 2)
        // ctx.clip()

			// // Bottom-right corner radius
        // ctx.beginPath()
        // ctx.arc(x + size - borderRadius, y + size - borderRadius, borderRadius, 0, Math.PI / 2)
        // ctx.clip()


				// Calculate image scaling and position to preserve aspect ratio
				let drawWidth = size
				let drawHeight = size
				let offsetX = 0
				let offsetY = 0

				const ratio = image.width / image.height
				if (ratio > 1) {
					// Image is wider
					drawWidth = size * ratio
					offsetX = -(drawWidth - size) / 2
				} else if (ratio < 1) {
					// Image is taller
					drawHeight = size / ratio
					offsetY = -(drawHeight - size) / 2
				}

				// Draw the image
        ctx.drawImage(image, x + offsetX, y + offsetY, drawWidth, drawHeight)

				// Restore drawing state
				ctx.restore()
				resolve()
			}

			image.onerror = () => {
				// Resolve even when image load fails to avoid blocking others
				resolve()
			}

			image.src = url
		})
	})

	// Wait for all images to load before returning result
	return new Promise<string>((resolve) => {
		Promise.all(loadImages).then(() => {
			resolve(canvas?.toDataURL())
		})
	})
}
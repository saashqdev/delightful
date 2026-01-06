import { useMemoizedFn } from "ahooks"
import type { MouseEvent } from "react"
import { delightful } from "@/enhance/delightfulElectron"

const requestAnimationFrame =
	window.requestAnimationFrame ||
	((callback: () => void) => window.setTimeout(callback, 1000 / 60))
const cancelAnimationFrame = window.cancelAnimationFrame || ((id) => clearTimeout(id))

/**
 * @description Global drag event in electron
 */
const useDrag = () => {
	const onMouseDown = useMemoizedFn((event: MouseEvent<HTMLDivElement>) => {
		// Right-click doesn't move, only left-click triggers
		if (event.button === 2) return

		let draggable = true
		let animationId: number = 0

		// Record mouse position
		const mouseX: number = event.clientX
		const mouseY: number = event.clientY

		// Define window movement
		const moveWindow = () => {
			delightful?.view?.setViewPosition({
				x: mouseX,
				y: mouseY,
			})
			if (draggable) {
				animationId = requestAnimationFrame(moveWindow)
			}
		}

		const onMouseUp = () => {
			// Release lock
			draggable = false
			// Remove mouseup event
			document.removeEventListener("mouseup", onMouseUp)
			// Clear timer
			cancelAnimationFrame(animationId)
		}

		// Register mouseup event
		document.addEventListener("mouseup", onMouseUp)
		// Start communication
		animationId = requestAnimationFrame(moveWindow)
	})

	return {
		onMouseDown,
	}
}

export default useDrag

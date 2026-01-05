/**
 * Browser event handling helpers
 */

import { useEffect } from "react"

export default function useMacTouch() {
	useEffect(() => {
		// Add overscroll-behavior-x: none on mount
		document.documentElement.style.overscrollBehaviorX = "none"

		// Remove overscroll-behavior-x on unmount
		return () => {
			document.documentElement.style.overscrollBehaviorX = ""
		}
	}, []) // Empty deps ensure this runs only on mount/unmount
}

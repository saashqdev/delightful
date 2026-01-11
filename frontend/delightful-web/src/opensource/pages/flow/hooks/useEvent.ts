/**
 * Browser event controls
 */

import { useEffect } from "react"

export default function useEvent() {
	useEffect(() => {
		// Add overscroll-behavior-x: none when component mounts
		document.documentElement.style.overscrollBehaviorX = "none"

		// Remove overscroll-behavior-x when component unmounts
		return () => {
			document.documentElement.style.overscrollBehaviorX = ""
		}
	}, []) // Empty dependency array ensures this only executes on component mount and unmount
}






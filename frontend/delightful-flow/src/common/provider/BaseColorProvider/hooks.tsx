import { useContext } from "react"
import { BaseColorContext } from "./context"

/**
 * Get base color variables from the current theme
 * @returns
 */
export const useBaseColor = () => {
	return useContext(BaseColorContext)
}


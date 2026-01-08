import { useContext } from "react"
import { DelightfulCitationContext } from "./Provider"

export const useDelightfulCitationSources = () => {
	return useContext(DelightfulCitationContext)
}

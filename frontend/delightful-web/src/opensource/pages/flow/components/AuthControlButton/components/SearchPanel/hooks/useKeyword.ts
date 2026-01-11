/**
 * Searchkeyword related data state
 */
import { useState } from "react"

export default function useKeyword() {
	const [keyword, setKeyword] = useState("")

	return {
		keyword,
		setKeyword,
	}
}







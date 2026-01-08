import { useEffect, useState } from "react"

// Search debounce
export const useDebounceSearch = (search: string, delay: number) => {
	const [debouncedSearch, setDebouncedSearch] = useState(search)

	useEffect(() => {
		const handler = setTimeout(() => {
			setDebouncedSearch(search)
		}, delay)

		return () => {
			clearTimeout(handler)
		}
	}, [search, delay])

	return debouncedSearch
}

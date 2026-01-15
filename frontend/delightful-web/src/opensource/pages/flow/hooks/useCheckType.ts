/**
 * Check if current is Flow or Agent
 */

import { useMemo } from "react"
import { useParams } from "react-router"
import { DELIGHTFUL_FLOW_ID_PREFIX } from "../constants"

export default function useCheckType() {
	const { id } = useParams()
	const agentId = id as string

	const isAgent = useMemo(() => {
		// Temporarily determine by ID prefix
		return !agentId.startsWith(DELIGHTFUL_FLOW_ID_PREFIX)
	}, [agentId])

	return {
		isAgent,
	}
}

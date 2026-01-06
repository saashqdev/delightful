import { useContext, useState, useEffect } from "react"
import { reaction } from "mobx"
import { ClusterContext } from "../ClusterProvider"

export function useClusterCode() {
	const store = useContext(ClusterContext)
	// Current cluster configuration
	const [clusterCode, setClusterConfig] = useState(store.clusterCode)

	useEffect(() => {
		const disposer = reaction(
			() => store.clusterCode,
			(code) => setClusterConfig(code),
		)

		return () => disposer()
	}, [store])

	return {
		clusterCode,
		setClusterCode: store.setClusterCode,
	}
}

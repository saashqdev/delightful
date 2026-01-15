import { makeAutoObservable } from "mobx"
import type { Common } from "@/types/common"

/**
 * @description Deployment configuration Store, responsible for in-memory state management
 */
export class ClusterStore {
	// Current environment
	clusterCode: string = ""

	// Cached environment
	clusterCodeCache: string = ""

	// Save deploy configuration
	private config: Map<string, Common.PrivateConfig> = new Map()

	constructor() {
		makeAutoObservable(this)
	}

	get clusterConfig() {
		return Object.fromEntries(this.config)
	}

	get cluster() {
		return this.config.get(this.clusterCode)
	}

	setClusterCodeCache(clusterCode: string) {
		this.clusterCodeCache = clusterCode
	}

	setClusterCode(clusterCode: string) {
		this.clusterCode = clusterCode
	}

	setClusterConfig(clusterCode: string, clusterConfig: Common.PrivateConfig) {
		return this.config.set(clusterCode, clusterConfig)
	}

	setClustersConfig(clusterConfig: Array<Common.PrivateConfig>) {
		clusterConfig.map((config) => this.config.set(config.deployCode ?? "", config))
	}

	deleteClusterConfig(clusterCode: string) {
		return this.config.delete(clusterCode)
	}
}

export const clusterStore = new ClusterStore()

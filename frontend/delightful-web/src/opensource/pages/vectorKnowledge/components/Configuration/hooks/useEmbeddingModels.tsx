import { useState, useEffect, useMemo } from "react"
import { Flex } from "antd"
import { useMemoizedFn } from "ahooks"
import { cx } from "antd-style"
import { KnowledgeApi } from "@/apis"
import type { Knowledge } from "@/types/knowledge"
import { useVectorKnowledgeConfigurationStyles } from "../styles"

interface EmbeddingModel {
	id: string
	name: string
	icon: string
	provider: string
}

/**
 * Embedding model hook for fetching and processing embedding model list
 */
export function useEmbeddingModels() {
	const { styles } = useVectorKnowledgeConfigurationStyles()

	// Embedding model group options
	const [embeddingModelGroup, setEmbeddingModelGroup] = useState<Knowledge.ServiceProvider[]>([])
	// Flattened list of all embedding models
	const [embeddingModels, setEmbeddingModels] = useState<EmbeddingModel[]>([])

	// Get embedding model list
	const fetchEmbeddingModelList = useMemoizedFn(async () => {
		const res = await KnowledgeApi.getEmbeddingModelList()
		if (res) {
			setEmbeddingModelGroup(res)
			const arr = []
			for (const item of res) {
				for (const model of item.models) {
					arr.push({
						id: model.id,
						name: model.name,
						icon: model.icon,
						provider: item.name,
					})
				}
			}
			setEmbeddingModels(arr)
		}
	})

	/** Embedding model options */
	const embeddingModelOptions = useMemo(() => {
		return embeddingModelGroup.map((item) => ({
			label: item.name,
			title: item.name,
			options: item.models.map((model) => ({
				label: (
					<Flex align="center" gap={8}>
						<img width={20} src={model.icon} alt="" />
						<div>{model.name}</div>
						<div className={cx(styles.optionProvider, "optionProvider")}>
							{item.name}
						</div>
					</Flex>
				),
				title: model.name,
				value: model.id,
			})),
		}))
	}, [embeddingModelGroup, styles])

	// Get embedding model list
	useEffect(() => {
		fetchEmbeddingModelList()
	}, [])

	return {
		embeddingModels,
		embeddingModelOptions,
		getEmbeddingModelList: fetchEmbeddingModelList,
	}
}

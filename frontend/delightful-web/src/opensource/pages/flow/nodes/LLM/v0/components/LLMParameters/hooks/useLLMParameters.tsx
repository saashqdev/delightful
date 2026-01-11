import { useMemo } from "react"
import { useTranslation } from "react-i18next"

export default function useLLMParameters() {
	const { t } = useTranslation()
	const temperature = useMemo(() => {
		return {
			label: t("common.temperature", { ns: "flow" }),
			key: "temperature",
			tooltips: t("common.temperatureDesc", { ns: "flow" }),
			open: true,
			defaultValue: 0.7,
			extra: {
				step: 0.1,
				max: 1,
				min: 0,
			},
		}
	}, [t])

	const autoMemory = useMemo(() => {
		return {
			label: t("common.autoMemory", { ns: "flow" }),
			key: "auto_memory",
			tooltips: t("common.autoMemoryDesc", { ns: "flow" }),
			defaultValue: true,
		}
	}, [t])

	const maxRecord = useMemo(() => {
		return {
			label: t("common.maxRecord", { ns: "flow" }),
			key: "max_record",
			tooltips: t("common.maxRecordDesc", { ns: "flow" }),
			defaultValue: 10,
		}
	}, [t])

	const parameterList = useMemo(() => {
		return [
			// {
			// 	label: "Temperature",
			// 	key: "temperature",
			// 	tooltips:
			// 		"Controls randomness. Lower values reduce randomness and produce more deterministic, repetitive outputs. Higher values increase randomness.",
			// 	open: true,
			// 	defaultValue: 0.7,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Top P",
			// 	key: "top_p",
			// 	tooltips: "Controls diversity via nucleus sampling: 0.5 considers half of the weighted options.",
			// 	open: false,
			// 	defaultValue: 1,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Presence Penalty",
			// 	key: "exist_penalty",
			// 	tooltips: "Applies a penalty to the log probability of tokens already present in the text.",
			// 	open: false,
			// 	defaultValue: 0,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Frequency Penalty",
			// 	key: "frequency_penalty",
			// 	tooltips: "Applies a penalty to the log probability of tokens that appear frequently in the text.",
			// 	open: false,
			// 	defaultValue: 0,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Max Tokens",
			// 	key: "max_tags",
			// 	tooltips: "Specifies the upper limit for generated result length. Increase this if output is truncated.",
			// 	open: false,
			// 	defaultValue: 512,
			// 	extra: {
			// 		step: 1,
			// 		max: 4096,
			// 		min: 1,
			// 	},
			// },
		]
	}, [])

	return {
		parameterList,
		temperature,
		autoMemory,
		maxRecord,
	}
}

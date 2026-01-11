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
			// 		"Temperature controls randomness. Lower values yield less random completions. As temperature approaches zero, the model becomes more deterministic and repetitive. Higher values increase randomness.",
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
			// 	tooltips: "Controls diversity via nucleus sampling: 0.5 considers half of all weighted options.",
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
			// 	tooltips: "Penalizes the log probability of tokens that already appear in the text.",
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
			// 	tooltips: "Penalizes the log probability of tokens based on their frequency in the text.",
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
			// 	tooltips: "Specify the upper limit for the generated result length. If generation is truncated, increase this parameter.",
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






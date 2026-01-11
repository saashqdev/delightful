import { useMemo } from "react"
import { useTranslation } from "react-i18next"

export default function useLLMParameters() {
	const { t } = useTranslation()
	const maxRecord = useMemo(() => {
		return {
			label: t("common.maxRecord", { ns: "flow" }),
			key: "max_record",
			tooltips: t("common.maxRecordDesc", { ns: "flow" }),
			open: true,
			defaultValue: 10,
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

	const parameterList = useMemo(() => {
		return [
			// {
			// 	label: "Temperature",
			// 	key: "temperature",
			// 	tooltips:
			// 		"Temperature controls randomness. Lower temperature leads to less random completion. As temperature approaches zero, model becomes more deterministic and repetitive. Higher temperature leads to more random completion.",
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
			// 	tooltips: "Control diversity through nucleus sampling：0.5indicates half of all possible weighted options are considered。",
			// 	open: false,
			// 	defaultValue: 1,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Presence penalty",
			// 	key: "exist_penalty",
			// 	tooltips: "Apply penalty to the log probability of already existing tokens in the text.",
			// 	open: false,
			// 	defaultValue: 0,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "Frequency penalty",
			// 	key: "frequency_penalty",
			// 	tooltips: "Apply penalty to the log probability of tokens appearing in the text.",
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
			// 	tooltips: "Specifies the upper limit of the generated result length. If the generated result is truncated, you can increase this parameter.",
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
		maxRecord,
		autoMemory,
	}
}








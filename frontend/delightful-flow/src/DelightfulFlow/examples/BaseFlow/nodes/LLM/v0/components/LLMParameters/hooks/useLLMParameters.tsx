import { useMemo } from "react"

export default function useLLMParameters() {
	const parameterList = useMemo(() => {
		return [
			{
				label: "Temperature",
				key: "temperature",
				tooltips:
					"Temperature controls randomness. Lower temperature leads to less random completions. As temperature approaches zero, the model becomes deterministic and repetitive. Higher temperature leads to more random completions.",
				open: true,
				defaultValue: 0.7,
				extra: {
					step: 0.1,
					max: 1,
					min: 0,
				},
			},

			{
				label: "Top P",
				key: "top_p",
				tooltips: "Control diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.",
				open: false,
				defaultValue: 1,
				extra: {
					step: 0.1,
					max: 1,
					min: 0,
				},
			},

			{
				label: "Presence Penalty",
				key: "exist_penalty",
				tooltips: "Applies a penalty to the log-probability of tokens already present in the text.",
				open: false,
				defaultValue: 0,
				extra: {
					step: 0.1,
					max: 1,
					min: 0,
				},
			},

			{
				label: "Frequency Penalty",
				key: "frequency_penalty",
				tooltips: "Applies a penalty to the log-probability of tokens based on their frequency in the text.",
				open: false,
				defaultValue: 0,
				extra: {
					step: 0.1,
					max: 1,
					min: 0,
				},
			},

			{
				label: "Max Tokens",
				key: "max_tags",
				tooltips: "Specifies the upper limit for the length of generated results. If the result is truncated, increase this parameter.",
				open: false,
				defaultValue: 512,
				extra: {
					step: 1,
					max: 4096,
					min: 1,
				},
			},
		]
	}, [])

	return {
		parameterList,
	}
}


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
			// 		"Temperature控制随机性。较低的Temperature会导致较少的随机完成。随着Temperature接近零，Model becomesConfirmand repeatability。较高的Temperature会导致更多的随机完成。",
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
			// 	tooltips: "对文本中已有的标记的对数概率施加惩罚。",
			// 	open: false,
			// 	defaultValue: 0,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "频率惩罚",
			// 	key: "frequency_penalty",
			// 	tooltips: "对文本中出现的标记的对数概率施加惩罚。",
			// 	open: false,
			// 	defaultValue: 0,
			// 	extra: {
			// 		step: 0.1,
			// 		max: 1,
			// 		min: 0,
			// 	},
			// },
			// {
			// 	label: "最大标记",
			// 	key: "max_tags",
			// 	tooltips: "指定生成结果长度的上限。如果生成结果截断，可以调大该参数。",
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



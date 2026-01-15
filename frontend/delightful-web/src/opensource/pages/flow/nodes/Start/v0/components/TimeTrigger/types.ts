import type { CycleTypeMap, Units } from "./constants"

export type TimeTriggerParams = {
	type: CycleTypeMap
	day: string // 1~31
	time: string
	// Settings for custom repeat
	value: {
		interval: number
		unit: Units
		values: number[]
		deadline: null | string
	}
}
export default {}

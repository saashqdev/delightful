// LLM adjuster
export enum LLMAdjust {
	// Creative
	Creativity = 1,
	// Balanced
	Balanced = 2,
	// Precise
	Precise = 3,

	// Load preset
	default = 4,
}

// LLM adjuster value mapping
export const LLMAdjustMap = {
	[LLMAdjust.Creativity]: {
		max_record: 10,
		auto_memory: false,
	},
	[LLMAdjust.Balanced]: {
		temperature: 0.5,
		auto_memory: true,
		// top_p: {
		// 	open: true,
		// 	value: 0.85,
		// },
		// exist_penalty: {
		// 	open: true,
		// 	value: 0.2,
		// },
		// frequency_penalty: {
		// 	open: true,
		// 	value: 0.3,
		// },
		// max_tags: {
		// 	open: false,
		// 	value: 512,
		// },
	},
	[LLMAdjust.Precise]: {
		temperature: 0.2,
		auto_memory: true,
		// top_p: {
		// 	open: true,
		// 	value: 0.75,
		// },
		// exist_penalty: {
		// 	open: true,
		// 	value: 0.5,
		// },
		// frequency_penalty: {
		// 	open: true,
		// 	value: 0.5,
		// },
		// max_tags: {
		// 	open: false,
		// 	value: 512,
		// },
	},
}






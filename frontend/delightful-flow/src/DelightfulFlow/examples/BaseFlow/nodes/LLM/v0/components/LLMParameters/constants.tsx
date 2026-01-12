// LLM Adjuster
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

// LLM Adjuster value mapping
export const LLMAdjustMap = {
	[LLMAdjust.Creativity]: {
		temperature: {
			open: true,
			value: 0.8,
		},
		top_p: {
			open: true,
			value: 0.9,
		},
		exist_penalty: {
			open: true,
			value: 0.1,
		},
		frequency_penalty: {
			open: true,
			value: 0.1,
		},
		max_tags: {
			open: false,
			value: 512,
		},
	},
	[LLMAdjust.Balanced]: {
		temperature: {
			open: true,
			value: 0.5,
		},
		top_p: {
			open: true,
			value: 0.85,
		},
		exist_penalty: {
			open: true,
			value: 0.2,
		},
		frequency_penalty: {
			open: true,
			value: 0.3,
		},
		max_tags: {
			open: false,
			value: 512,
		},
	},
	[LLMAdjust.Precise]: {
		temperature: {
			open: true,
			value: 0.2,
		},
		top_p: {
			open: true,
			value: 0.75,
		},
		exist_penalty: {
			open: true,
			value: 0.5,
		},
		frequency_penalty: {
			open: true,
			value: 0.5,
		},
		max_tags: {
			open: false,
			value: 512,
		},
	},
}


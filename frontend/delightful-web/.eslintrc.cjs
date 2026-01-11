module.exports = {
	root: true,
	extends: [
		"@delightful/eslint-config",
		"@delightful/eslint-config/typescript",
		"@delightful/eslint-config/react",
		"@delightful/eslint-config/prettier",
	],
	parserOptions: {
		project: ["./tsconfig.json", "./tsconfig.*.json"],
	},
	settings: {
		"import/resolver": {
			typescript: {
				project: ["./tsconfig.json", "./tsconfig.*json"],
			},
		},
	},
	rules: {
		"react/display-name": 0,
	},
	overrides: [
		{
			files: ["*.cjs"],
			rules: {
				"@typescript-eslint/no-var-requires": "off",
			},
		},
	],
}

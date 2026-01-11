module.exports = {
	root: true,
	extends: [
		"@delightful/eslint-config",
		"@delightful/eslint-config/typescript",
		"@delightful/eslint-config/react",
		"@delightful/eslint-config/prettier",
	],
	parserOptions: {
		project: ["./tsconfig.json"],
	},
	settings: {
		"import/resolver": {
			typescript: {
				project: ["./tsconfig.json"],
			},
		},
	},
}

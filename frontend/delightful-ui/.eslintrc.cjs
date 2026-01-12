module.exports = {
	root: true,
	extends: [
		"@bedelightful/eslint-config",
		"@bedelightful/eslint-config/typescript",
		"@bedelightful/eslint-config/react",
		"@bedelightful/eslint-config/prettier",
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

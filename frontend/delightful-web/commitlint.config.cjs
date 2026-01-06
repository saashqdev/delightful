const config = require("@delightful/eslint-config/commitlint/emoji")

module.exports = {
	...config,
	rules: {
		...config.rules,
		"header-max-length": [1, "always", 72], // 1 indicates warning level, 0=disabled, 1=warning, 2=error
	},
}

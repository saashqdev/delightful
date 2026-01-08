const bunyan = require("node-bunyan")
const morgan = require("morgan")

// Create Bunyan logger
const logger = bunyan.createLogger({
	name: "delightful-web",
	streams: [
		// Output to file
		{ level: "info", path: "./error.log" },
		// Output to console
		{ level: "error", stream: process.stdout },
	],
})

// Create Morgan logging middleware
const morganMiddleware = morgan("combined", {
	stream: { write: (message) => logger.error(message) },
})

module.exports = { logger, morganMiddleware }

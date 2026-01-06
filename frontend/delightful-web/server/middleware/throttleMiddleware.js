// Throttle configuration
const THROTTLE_WINDOW = 60 * 1000 // Time window: 1 minute
const THROTTLE_LIMIT = 1000 // Max 1000 requests per minute
let requestCount = 0
let windowStartTime = Date.now()

// Throttle middleware
const throttleMiddleware = (req, res, next) => {
	const currentTime = Date.now()
	// If the time window has passed, reset count and window start time
	if (currentTime - windowStartTime > THROTTLE_WINDOW) {
		requestCount = 0
		windowStartTime = currentTime
	}
	// If request count exceeds the limit, return throttling response
	if (requestCount >= THROTTLE_LIMIT) {
		return res.status(200).json({
			code: 1000,
			message: "Too many requests, please try again later.",
		})
	}
	// Increment request count
	requestCount++
	next()
}

module.exports = throttleMiddleware

const express = require("express")
const path = require("path")

const app = express()
const R = require("ramda")
const cors = require("cors")

app.use(cors())
app.use(express.json())

const history = require("connect-history-api-fallback")
const { logger, morganMiddleware } = require("./logger.cjs")
const throttleMiddleware = require("./middleware/throttleMiddleware")

// Disable default x-powered-by response header only in production environment
if (process.env.NODE_ENV === "production") {
	app.disable("x-powered-by")
}

// Log reporting API endpoint
app.post("/log-report", throttleMiddleware, (req, res) => {
	const logs = req.body
	if (Array.isArray(logs)) {
		logs.forEach((log) => {
			logger.error(log)
		})
	} else {
		logger.error(logs)
	}
	res.status(200).json({
		code: 1000,
		message: "Logs reported successfully.",
	})
})

function setCustomCacheControl(res, p) {
	const excludeReg = [/service-worker\.js$/, /\.html$/]
	// Pages not to cache
	if (excludeReg.some((v) => v.test(p))) {
		// Custom Cache-Control for HTML files
		res.setHeader("Cache-Control", "no-cache, no-store, must-revalidate")
		res.setHeader("Pragma", "no-cache")
		res.setHeader("Expires", "0")
	} else {
		res.setHeader("Cache-Control", "max-age=31536000")
	}
}

app.get("/config.js", (req, res) => {
	const envVarWhitelist = [
		"DELIGHTFUL_APP_ENV",
		"DELIGHTFUL_IS_PRIVATE_DEPLOY",
		"DELIGHTFUL_TEAMSHARE_BASE_URL",
		"DELIGHTFUL_SOCKET_BASE_URL",
		"DELIGHTFUL_SERVICE_BASE_URL",
		"DELIGHTFUL_SERVICE_KEEWOOD_BASE_URL",
		"DELIGHTFUL_SERVICE_TEAMSHARE_BASE_URL",
		"DELIGHTFUL_AMAP_KEY",
		"DELIGHTFUL_GATEWAY_ADDRESS",
		"DELIGHTFUL_TEAMSHARE_WEB_URL",
		"DELIGHTFUL_KEEWOOD_WEB_URL",
		"DELIGHTFUL_APP_VERSION",
		"DELIGHTFUL_APP_SHA",
		"DELIGHTFUL_EDITION",
	]

	const isSafeEnvironmentVariable = (key) => {
		// Only expose environment variables that start with DELIGHTFUL_ and are in the whitelist
		return key.startsWith("DELIGHTFUL_") && envVarWhitelist.includes(key)
	}

	res.set("Content-type", "text/javascript")
	res.setHeader("Cache-Control", "no-cache, no-store, must-revalidate")
	res.setHeader("Pragma", "no-cache")
	res.setHeader("Expires", "0")

	const safeEnvVars = R.pickBy((_, key) => isSafeEnvironmentVariable(key), process.env)

	res.send(`window.CONFIG = ${JSON.stringify(safeEnvVars)}`)
})

app.get("/heartbeat", (req, res) => {
	res.status(200).json({ data: 200 })
})

app.use(
	history({
		index: "/index.html",
	}),
)

app.use(
	express.static(path.join(__dirname, "../dist"), {
		setHeaders: setCustomCacheControl,
	}),
)

// Disable log writing for static resource requests
// app.use(morganMiddleware)

// - Last fallback error handling middleware -
app.use((req, res) => {
	res.setHeader("Content-Type", "text/html")
	res.status(404)
	res.send("")
})

// 5xx
app.use((err, req, res, next) => {
	logger.error({
		url: req.url, // Current URL being accessed
		message: err?.message, // Error message
		memoryUsage: process.memoryUsage(), // Memory usage
		cpuUsage: process.cpuUsage(), // CPU usage
		stack: err?.stack, // Error stack (pid, time and other info already handled in plugin)
	})

	res.setHeader("Content-Type", "text/html")
	res.status(500)
	res.send("")
})

const PORT = 8080
app.listen(PORT, "0.0.0.0", (err) => {
	if (err) {
		console.log(err)
		return
	}
	console.log(`Access link: http://localhost:${PORT} -- Server started`)
})

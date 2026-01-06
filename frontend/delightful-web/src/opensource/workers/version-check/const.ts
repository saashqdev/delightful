// When SharedWorker receives MessageType events, it responds with reflect-prefixed types
export enum ReflectMessageType {
	REFLECT_GET_LATEST_VERSION = "reflectGetLatestVersion",
	REFLECT_REFRESH = "reflectRefresh",
}

// Message types sent to the worker
export enum MessageType {
	START = "start", // Start polling to check Etag version
	STOP = "stop", // Stop polling
	CLOSE = "close", // Close SharedWorker port on page close/refresh
	REFRESH = "refresh",
}

// SharedWorker receives MessageType events and returns reflected events prefixed with "reflect"
export enum ReflectMessageType {
	REFLECT_GET_LATEST_VERSION = "reflectGetLatestVersion",
	REFLECT_REFRESH = "reflectRefresh",
}

// Outgoing message types
export enum MessageType {
	START = "start", // Start polling to detect the Etag version
	STOP = "stop", // Stop polling
	CLOSE = "close", // Close the SharedWorker port when closing or refreshing the page
	REFRESH = "refresh",
}

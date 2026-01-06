export class UrlUtils {
	/**
	 * Determine whether a URL includes a host
	 * @param url - URL string to check
	 */
	static hasHost(url: string) {
		try {
			return !!new URL(url).host
		} catch {
			return url.startsWith("//")
		}
	}

	/**
	 * Safely join URL parts
	 * @param origin - Base URL
	 * @param pathname - Path
	 * @returns Joined URL
	 */
	static join(origin: string, pathname: string): string {
		// Handle absolute URLs
		if (pathname.startsWith("http://") || pathname.startsWith("https://")) {
			return pathname
		}

		if (pathname.startsWith("//")) {
			return pathname
		}

		// Handle invalid URL cases
		try {
			const originUrl = new URL(origin)
			const originPathname = originUrl.pathname

			// Normalize path, remove extra slashes
			const normalizedPathname = originPathname.endsWith("/")
				? originPathname.slice(0, -1)
				: originPathname

			const normalizedPath = pathname.startsWith("/") ? pathname : `/${pathname}`

			const url = new URL(normalizedPathname + normalizedPath, originUrl.origin)

			return url.toString()
		} catch (e) {
			// Simple handling for invalid URL cases
			return origin + (origin.endsWith("/") ? "" : "/") + pathname.replace(/^\.\/|^\/+/, "")
		}
	}

	/**
	 * Get URL parts
	 */
	static parse = (url: string) => {
		try {
			const urlObj = new URL(url)
			return {
				protocol: urlObj.protocol,
				host: urlObj.host,
				pathname: urlObj.pathname,
				search: urlObj.search,
				hash: urlObj.hash,
				isValid: true,
			}
		} catch {
			return {
				protocol: "",
				host: "",
				pathname: url,
				search: "",
				hash: "",
				isValid: false,
			}
		}
	}

	/**
	 * Transform a WebSocket URL to a Socket.io URL
	 * @param url WebSocket connection URL
	 * @returns Socket.io connection URL
	 */
	static transformToSocketIoUrl(url: string) {
		return `${url}/socket.io/?EIO=3&transport=websocket&timestamp=${Date.now()}`
	}

	/**
	 * @description request body parsing
	 * @param response
	 */
	static async responseParse(response: Response) {
		const contentType = response.headers.get("Content-Type") || ""

		if (contentType.includes("application/json")) {
			return { data: await response.json(), type: "json" }
		}
		if (contentType.includes("text/")) {
			return { data: await response.text(), type: "text" }
		}
		if (contentType.includes("image/")) {
			return { data: await response.blob(), type: "blob" }
		}

		return { data: await response.arrayBuffer(), type: "buffer" }
	}
}

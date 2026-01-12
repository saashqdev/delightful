import type { manageCursor } from "../utils"

// Create a global instance reference to ensure all components share the same cursor management
export const globalCursorManager = {
	instance: null as ReturnType<typeof manageCursor> | null,
}

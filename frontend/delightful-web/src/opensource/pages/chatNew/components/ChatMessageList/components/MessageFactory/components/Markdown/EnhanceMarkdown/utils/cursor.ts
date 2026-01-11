import type { manageCursor } from "../utils"

// create一个全局实例引用，确保所有component共享相同的光标管理
export const globalCursorManager = {
	instance: null as ReturnType<typeof manageCursor> | null,
}

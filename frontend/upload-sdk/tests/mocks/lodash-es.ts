/**
 * Lodash ES Mock file
 * Provides common lodash-es methods for test environment
 */

import * as lodash from "lodash-es"

// Define types for throttle and debounce
type ThrottleFunction = <T extends (...args: any[]) => any>(
	func: T,
	wait?: number,
	options?: lodash.ThrottleSettings,
) => lodash.DebouncedFunc<T>

type DebounceFunction = <T extends (...args: any[]) => any>(
	func: T,
	wait?: number,
	options?: lodash.DebounceSettings,
) => lodash.DebouncedFunc<T>

// Create an object containing our custom methods
const customMethods: {
	isFunction: (val: any) => boolean
	isObject: (val: any) => boolean
	isArray: (val: any) => boolean
	isString: (val: any) => boolean
	isNumber: (val: any) => boolean
	isBoolean: (val: any) => boolean
	isNull: (val: any) => boolean
	isUndefined: (val: any) => boolean
	isNil: (val: any) => boolean
	isEmpty: (val: any) => boolean
	get: (obj: any, path: string | string[], defaultValue?: any) => any
	omit: (obj: object, paths: string | string[]) => object
	pick: (obj: object, paths: string | string[]) => object
	merge: typeof lodash.merge
	cloneDeep: typeof lodash.cloneDeep
	throttle: ThrottleFunction
	debounce: DebounceFunction
} = {
	isFunction: (val: any): boolean => typeof val === "function",
	isObject: (val: any): boolean => val !== null && typeof val === "object",
	isArray: Array.isArray,
	isString: (val: any): boolean => typeof val === "string",
	isNumber: (val: any): boolean => typeof val === "number",
	isBoolean: (val: any): boolean => typeof val === "boolean",
	isNull: (val: any): boolean => val === null,
	isUndefined: (val: any): boolean => val === undefined,
	isNil: (val: any): boolean => val === null || val === undefined,
	isEmpty: (val: any): boolean => {
		if (val === null || val === undefined) return true
		if (Array.isArray(val) || typeof val === "string") return val.length === 0
		if (typeof val === "object") return Object.keys(val).length === 0
		return false
	},
	get: (obj: any, path: string | string[], defaultValue?: any): any => {
		if (obj == null) return defaultValue

		const pathArray = Array.isArray(path) ? path : path.split ? path.split(".") : [path]

		let result = obj
		for (const key of pathArray) {
			result = result?.[key]
			if (result === undefined) return defaultValue
		}

		return result
	},
	omit: (obj: object, paths: string | string[]): object => {
		if (!obj) return {}
		const pathArray = Array.isArray(paths) ? paths : [paths]
		const result = { ...obj }
		pathArray.forEach((path) => {
			delete (result as any)[path]
		})
		return result
	},
	pick: (obj: object, paths: string | string[]): object => {
		if (!obj) return {}
		const pathArray = Array.isArray(paths) ? paths : [paths]
		const result: Record<string, any> = {}
		pathArray.forEach((path) => {
			if ((obj as any)[path] !== undefined) {
				result[path] = (obj as any)[path]
			}
		})
		return result
	},
	// Use original lodash methods
	merge: lodash.merge,
	cloneDeep: lodash.cloneDeep,
	throttle: lodash.throttle,
	debounce: lodash.debounce,
}

// Export individual methods from custom methods object
export const {
	isFunction,
	isObject,
	isArray,
	isString,
	isNumber,
	isBoolean,
	isNull,
	isUndefined,
	isNil,
	isEmpty,
	get,
	omit,
	pick,
	merge,
	cloneDeep,
	throttle,
	debounce,
} = customMethods

// Default export our custom methods instead of the entire lodash
export default customMethods





import isVarNameValid from "esniff/is-var-name-valid"
import type { Result } from "./compile"
import type { ResolveOptions } from "./types"

const { stringify } = JSON

function isValue(value: any): any | never {
	if (value === null || value === undefined) throw new TypeError("Cannot use null or undefined")
	return value
}

function process(src: Record<string, any>, obj: Record<string, any>) {
	// eslint-disable-next-line guard-for-in,no-restricted-syntax
	for (const key in src) {
		obj[key] = src[key]
	}
}

function normalize(...args: Array<any>) {
	const result: Record<string, any> = Object.create(null)
	args.forEach(function (options) {
		if (!isValue(options)) return
		process(Object(options), result)
	})
	return result
}

export default function processData(
	data: Result,
	context: Record<string, any>,
	options: ResolveOptions = {},
): Array<Array<string> | string> {
	isValue(data) && isValue(data?.literals) && isValue(data?.substitutions)
	context = normalize(context)
	const names = Object.keys(context).filter(isVarNameValid)
	const argNames = names.join(", ")
	const argValues = names.map((name: string) => context[name])
	const { substitutions = [], literals = [] } = data

	// For failed expressions, return ${} only when options.partial is true; otherwise return undefined
	return [literals].concat(
		substitutions.map((expr: string) => {
			let resolver: Function
			if (!expr) return undefined
			try {
				// eslint-disable-next-line @typescript-eslint/no-implied-eval
				resolver = new Function(argNames, `return (${expr})`)
			} catch (error: any) {
				throw new TypeError(
					`Unable to compile expression:\n\targs: ${stringify(argNames)}\n\tbody: ${stringify(
						expr,
					)}\n\terror: ${error.stack}`,
				)
			}
			try {
				// eslint-disable-next-line prefer-spread
				return resolver.apply(null, argValues)
			} catch (e) {
				if (options.partial) {
					return `\${${expr}}`
				}
				return "undefined"

				// Error handling intentionally suppressed for now; returns undefined instead
				// throw new TypeError(
				// 	`Unable to resolve expression:\n\targs: ${ stringify(argNames) }\n\tbody: ${ stringify(
				// 		expr
				// 	) }\n\terror: ${ e.stack }`
				// )
			}
		}),
	)
}

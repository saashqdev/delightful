import { defineConfig } from "tsup"
import type { Options } from "tsup"

// Use precise file globs instead of folders
const entry = ["./src/**/*.ts"]

// Shared configuration
const commonOptions: Partial<Options> = {
	sourcemap: true,
	keepNames: true,
	external: [],
	skipNodeModulesBundle: true,
	dts: {
		only: false,
		entry: ["./src/index.ts"],
		resolve: true,
	},
}

// Shared module build configuration
const moduleOptions: Partial<Options> = {
	...commonOptions,
	splitting: false,
	outExtension: () => ({ js: ".js", dts: ".d.ts" }),
	treeshake: false,
	bundle: false,
}

export default defineConfig([
	{
		...moduleOptions,
		entry,
		format: "esm",
		outDir: "dist/es",
		clean: true,
		dts: {
			only: false,
			entry: ["./src/index.ts"],
			resolve: true,
		},
	},
	{
		...moduleOptions,
		entry,
		format: "cjs",
		outDir: "dist/lib",
		clean: false,
		dts: {
			only: false,
			entry: ["./src/index.ts"],
			resolve: true,
		},
	},
	{
		...commonOptions,
		entry: ["./src/index.ts"],
		format: "iife",
		outDir: "dist",
		clean: false,
		splitting: true,
		outExtension: () => ({ js: ".js", dts: ".d.ts" }),
		treeshake: false,
	},

	{
		...commonOptions,
		entry: ["./src/index.ts"],
		format: "iife",
		dts: false,
		keepNames: false,
		outDir: "dist",
		clean: false,
		splitting: false,
		minify: true,
		outExtension: () => ({ js: ".min.js" }),
	},
])

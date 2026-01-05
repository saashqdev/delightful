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
		resolve: true,
	},
}

// Shared module build configuration
const moduleOptions: Partial<Options> = {
	...commonOptions,
	entry,
	splitting: false,
	outExtension: ({ format }) => {
		if (format === "esm") {
			return { js: ".mjs", dts: ".d.ts" }
		}
		return { js: ".cjs", dts: ".d.ts" }
	},
	treeshake: false,
	bundle: false,
}

export default defineConfig([
	{
		...moduleOptions,
		format: "esm",
		outDir: "dist/es",
		clean: true,
		bundle: true,
	},
	{
		...moduleOptions,
		format: "cjs",
		outDir: "dist/lib",
		clean: false,
		treeshake: true,
		bundle: true,
	},
])

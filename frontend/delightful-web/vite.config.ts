import { defineConfig } from "vite"
import react from "@vitejs/plugin-react"
import { resolve, join } from "path"
import mkcert from "vite-plugin-mkcert"
import babelPluginAntdStyle from "babel-plugin-antd-style-prod"
import tsConfig from "vite-tsconfig-paths"
// import legacy from "@vitejs/plugin-legacy"
import vitePluginImp from "vite-plugin-imp"

/** Environment variable prefix */
const ENV_PREFIX = "DELIGHTFUL_"

/** Is test environment */
// const isTest = process.env.NODE_ENV === "test"
/** Is development environment */
const isDev = process.env.NODE_ENV === "development"
/** Is production environment */
// const isProd = process.env.NODE_ENV === "production"

export default defineConfig({
	build: {
		target: "es2015",
		rollupOptions: {
			input: {
				main: resolve(__dirname, "index.html"),
			},
			output: {
				manualChunks: {
					dayjs: ["dayjs"],
					"dingtalk-jsapi": ["dingtalk-jsapi"],
				},
			},
		},
	},
	server: {
		proxy: {
			"/server": {
				target: process.env.DELIGHTFUL_DEV_API_HOST ?? "",
				changeOrigin: true,
				rewrite: (p) => p.replace(/^\/server/, ""),
			},
		},
		port: 443,
		host: "0.0.0.0", // Listen on all interfaces
	},
	envPrefix: ENV_PREFIX,
	cacheDir: "node_modules/.vite", // Cache directory
	optimizeDeps: {
		include: [
			"antd",
			"dayjs",
			"lodash-es",
			"react",
			"react-dom",
			"@tabler/icons-react",
			"@ant-design/icons",
			"ahooks",
			"antd",
			"antd-style",
			"zustand",
			"zustand/middleware",
			"i18next",
			"react-i18next",
			"@tiptap/react",
			"@tiptap/pm/state",
			"@tiptap/pm/view",
			"@tiptap/starter-kit",
			"@tiptap/extension-image",
			"@tiptap/extension-text-align",
		],
		exclude: ["antd/locale"],
	},
	assetsInclude: ["**/*.md", "**/*.mdx"],
	resolve: {
		alias: [
			// // Enable when developing delightful-flow in sync
			// {
			// 	find: "@bedelightful/delightful-flow",
			//     replacement: join(__dirname, "../delightful-flow"),
			// },
		],
	},
	plugins: [
		tsConfig(),
		react({
			babel: {
				plugins: [
					// refer: https://github.com/ant-design/babel-plugin-antd-style/pull/2
					[babelPluginAntdStyle, { enableInProduction: true, hashFilenameLevel: 1 }],
				],
			},
		}),
		vitePluginImp({
			libList: [
				{
					libName: "antd",
				},
			],
		}),
		// Generate HTTPS certificates locally
		isDev &&
			mkcert({
				// Configure this host locally to allow private bucket uploads
				hosts: ["delightful.t.teamshare.cn"],
			}),
		// Browser compatibility
		// legacy({
		// 	targets: [
		// 		"last 2 versions and not dead",
		// 		"> 0.3%",
		// 		"chrome 91",
		// 		"chrome 108",
		// 		"safari 16",
		// 	], // Target list requiring compatibility; can set multiple
		// 	additionalLegacyPolyfills: ["regenerator-runtime/runtime"],
		// 	renderLegacyChunks: true,
		// 	polyfills: [
		// 		"es.symbol",
		// 		"es.array.filter",
		// 		"es.promise",
		// 		"es.promise.finally",
		// 		"es/map",
		// 		"es/set",
		// 		"es.array.for-each",
		// 		"es.object.define-properties",
		// 		"es.object.define-property",
		// 		"es.object.get-own-property-descriptor",
		// 		"es.object.get-own-property-descriptors",
		// 		"es.object.keys",
		// 		"es.object.to-string",
		// 		"web.dom-collections.for-each",
		// 		"esnext.global-this",
		// 		"esnext.string.match-all",
		// 	],
		// }),
	],
	css: {
		preprocessorOptions: {
			less: {
				javascriptEnabled: true,
			},
		},
	},
})

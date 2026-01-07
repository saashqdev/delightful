/* eslint-disable import/no-unresolved */
import React, { useEffect, useRef, useState } from "react"
import { Button, Typography, Spin } from "antd"
import "./styles.css"

// Import preset packages
import { Univer, LocaleType, merge } from "@univerjs/core"
import { defaultTheme } from "@univerjs/design"

import { UniverUIPlugin } from "@univerjs/ui"
import { UniverSheetsPlugin } from "@univerjs/sheets"
import { UniverSheetsUIPlugin } from "@univerjs/sheets-ui"
import { UniverDocsPlugin } from "@univerjs/docs"
import { UniverDocsUIPlugin } from "@univerjs/docs-ui"
import { FUniver } from "@univerjs/core/facade"

// Import Chinese language pack

// Import CSS styles
// import "@univerjs/presets/lib/styles/preset-slides-core.css"

import DesignZhCN from "@univerjs/design/locale/zh-CN"
import UIZhCN from "@univerjs/ui/locale/zh-CN"
import SheetsZhCN from "@univerjs/sheets/locale/zh-CN"
import SheetsUIZhCN from "@univerjs/sheets-ui/locale/zh-CN"
import DocsUIZhCN from "@univerjs/docs-ui/locale/zh-CN"

// Tool function imports
import { transformData } from "./utils"
import { useAsyncEffect } from "ahooks"
import overrideLocales from "./locales"

import "@univerjs/sheets/facade"
// Core styles
import "@univerjs/design/lib/index.css"
import "@univerjs/ui/lib/index.css"

// Based on fileType selection for importing
import "@univerjs/sheets-ui/lib/index.css" // Sheet styles
import "@univerjs/docs-ui/lib/index.css" // Document styles
// import "@univerjs/slides-ui/lib/index.css"  // Slide styles (if needed)

const { Text } = Typography

// Export component interface
export interface UniverComponentProps {
	data: any
	fileType: "sheet" | "slide" | "doc"
	fileName: string
	width?: number | string
	height?: number | string
	mode?: "readonly" | "edit"
}

/**
 * UniverComponent - Document, spreadsheet, and slide viewing/editing component based on Univer library
 */
export const UniverComponent: React.FC<UniverComponentProps> = ({
	data,
	fileType,
	fileName = "Untitled file",
	width = "100%",
	height = "100%",
}) => {
	const containerRef = useRef<HTMLDivElement>(null)
	const univerAPIRef = useRef<any>(null)
	const [loading, setLoading] = useState(true)
	const [error, setError] = useState<string | null>(null)

	useEffect(() => {
		// Clean up resources when component unmounts
		return () => {
			if (univerAPIRef.current) {
				univerAPIRef.current.dispose()
				univerAPIRef.current = null
			}
		}
	}, [])

	useAsyncEffect(async () => {
		if (!containerRef.current) return

		setLoading(true)
		setError(null)

		// If instance already exists, destroy it first
		if (univerAPIRef.current) {
			univerAPIRef.current.dispose()
			univerAPIRef.current = null
		}

		try {
			// Convert data to Univer supported format
			const univerData = await transformData(data, fileType, fileName)

			console.log(
				"univerData",
				univerData,
				fileType,
				"fileType",
				fileName,
				"fileNamefileName",
				data,
				"data",
			)

			const univer = new Univer({
				locale: LocaleType.ZH_CN,
				locales: {
					[LocaleType.ZH_CN]: merge(
						{},
						DesignZhCN,
						UIZhCN,
						SheetsZhCN,
						SheetsUIZhCN,
						DocsUIZhCN,
						overrideLocales["zh-CN"],
					),
				},
				theme: defaultTheme,
			})
			const univerAPI = FUniver.newAPI(univer)

			univer.registerPlugin(UniverUIPlugin, {
				container: containerRef.current,
			})
			univer.registerPlugin(UniverDocsPlugin)
			univer.registerPlugin(UniverDocsUIPlugin)

			univer.registerPlugin(UniverSheetsPlugin)
			univer.registerPlugin(UniverSheetsUIPlugin, {
				menu: {
					"sheet.command.set-range-bold": {
						hidden: true,
					},
					"sheet.command.set-range-italic": {
						hidden: true,
					},
					"sheet.menu.paste-special": {
						hidden: true,
					},
					"sheet.command.paste": {
						hidden: true,
					},
					"sheet.menu.clear-selection": {
						hidden: true,
					},
					"sheet.contextMenu.permission": {
						hidden: true,
					},
					"sheet.operation.insert-hyper-link": {
						hidden: true,
					},
					"sheet.menu.sheet-frozen": {
						hidden: true,
					},
				},
			})

			switch (fileType) {
				case "sheet":
					break

				case "doc":
					break

				case "slide":
					// presets.push(
					// 	UniverSlidesCorePreset({
					// 		container: containerRef.current,
					// 	}),
					// )
					// locales = {
					// 	[LocaleType.ZH_CN]: merge({}, UniverPresetSlidesCoreZhCN),
					// }
					break

				default:
				throw new Error(`Unsupported file type: ${fileType}`)
			univerAPIRef.current = univerAPI

		// Create document instance of corresponding type
			if (fileType === "sheet") {
				univerAPI.createWorkbook(univerData)

				if (univerData) {
					const permission = univerAPI.getWorkbook(univerData.id)?.getPermission()
					const workbookId = univerAPI.getWorkbook(univerData.id)?.getId()
					if (workbookId) {
						permission?.setWorkbookPermissionPoint(
							workbookId,
							permission.permissionPointsDefinition.WorkbookEditablePermission,
							false,
						)
					}
				}
			} else if (fileType === "doc") {
				// univerAPI.createDocument(univerData)
			} else if (fileType === "slide") {
				// univerAPI.createPresentation(univerData)
			}

		// After instance creation, set initial readonly state
		setLoading(false)

		// Set global flag indicating Univer library has loaded
			if (typeof window !== "undefined") {
				;(window as any).__UNIVER_LIBRARIES_LOADED__ = true
			}
		} catch (err: any) {
		console.error("Failed to initialize Univer component:", err)
		setError(err.message || "Failed to load file")
			setLoading(false)
		}
	}, [data])

	useEffect(() => {
		// Add overscroll-behavior-x: none when component mounts
		document.documentElement.style.overscrollBehaviorX = "none"

		// Remove overscroll-behavior-x when component unmounts
		return () => {
			document.documentElement.style.overscrollBehaviorX = ""
		}
	}, []) // Empty dependency array ensures execution only on mount and unmount

	// Render error state
	if (error) {
		return (
			<div className="univer-error-container" style={{ width, height }}>
				<Text type="danger">Failed to load: {error}</Text>
				<Button type="primary" onClick={() => window.location.reload()}>
					Reload
				</Button>
			</div>
		)
	}

	// Render loading state or Univer container
	return (
		<div className="univer-component-wrapper" style={{ width, height }}>
			{loading && (
				<div className="univer-loading-container">
					<Spin tip="Loading..." />
				</div>
			)}
			<div
				ref={containerRef}
				className="univer-container"
				style={{
					width: "100%",
					height: "100%",
					visibility: loading ? "hidden" : "visible",
				}}
			/>
		</div>
	)
}

export default UniverComponent

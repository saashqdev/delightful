/**
 * Univer component data conversion utilities
 * Converts various data formats into Univer-compatible structures
 */
import * as XLSX from "xlsx"

// Boolean number enum for boolean value representation in Univer
enum BooleanNumber {
	FALSE = 0,
	TRUE = 1,
}

// Cell data interface
interface ICellData {
	v?: any // Cell value
	f?: string // Formula
	s?: number // Style
	[key: string]: any // Other properties
}

// Cell matrix type
interface ICellMatrix {
	[rowKey: string]: {
		[colKey: string]: ICellData
	}
}

/**
 * Detect whether a string is binary Excel file content
 * @param data The string to check
 * @returns Whether it's Excel binary content
 */
function isBinaryExcel(data: string): boolean {
	// Excel file feature detection
	// 1. File header detection: XLSX/DOCX are ZIP files starting with PK
	const hasPKHeader = data.startsWith("PK") || data.indexOf("PK") < 20

	// 2. Internal path detection: check for Excel-specific paths
	const hasExcelPaths =
		data.includes("xl/worksheets") ||
		data.includes("docProps") ||
		data.includes("sheet.xml") ||
		data.includes("[Content_Types].xml")

	// 3. Office document marker detection
	const hasOfficeMark =
		data.includes("Microsoft Excel") ||
		data.includes("spreadsheetml") ||
		data.includes("workbook")

	// Must satisfy both file header and at least one content feature
	return hasPKHeader && (hasExcelPaths || hasOfficeMark)
}

/**
 * Convert data to Univer format based on file type; supports string content or File objects
 * @param data Raw data: string content or File object
 * @param fileType File type 'doc' | 'sheet' | 'slide'
 * @param fileName File name
 * @returns Converted Univer data
 */
export async function transformData(data: any, fileType: string, fileName: string): Promise<any> {
	// If it's a File object, read file content first
	if (data instanceof File) {
		const fileExtension = data.name.split(".").pop()?.toLowerCase()

		// Choose appropriate reading method based on file type and extension
		if (fileType === "sheet" && (fileExtension === "xlsx" || fileExtension === "xls")) {
			// Use xlsx library to read Excel file
			try {
				const excelData = await readExcelFile(data)
				return transformToWorkbookData(excelData, fileName)
			} catch (error) {
				console.error("Failed to read Excel file:", error)
				throw new Error("Excel file reading failed")
			}
		} else if (fileType === "sheet" && fileExtension === "csv") {
			// Handle CSV files
			const content = await readFileAsText(data)
			return transformCsvToWorkbook(content, fileName)
		} else {
			// For other file types, read as text
			const content = await readFileAsText(data)

			// Call corresponding conversion function based on file type
			switch (fileType) {
				case "doc":
					return transformDataForDoc(content, fileName)
				case "sheet":
					return transformDataForSheet(content, fileName)
				case "slide":
					return transformDataForSlide(content, fileName)
				default:
					throw new Error(`Unsupported file type: ${fileType}`)
			}
		}
	}

	// If not a File object, process with original logic
	switch (fileType) {
		case "doc":
			return transformDataForDoc(data, fileName)
		case "sheet":
			return transformDataForSheet(data, fileName)
		case "slide":
			return transformDataForSlide(data, fileName)
		default:
			throw new Error(`Unsupported file type: ${fileType}`)
	}
}

/**
 * Read an Excel file using xlsx
 * @param file Excel File object
 * @returns 2D array of Excel data (first worksheet by default)
 */
async function readExcelFile(file: File): Promise<any[][]> {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()

		reader.onload = (e) => {
			try {
				const data = e.target?.result
				// Read Excel file
				const workbook = XLSX.read(data, { type: "array" })

				// Get first worksheet
				const firstSheetName = workbook.SheetNames[0]
				const worksheet = workbook.Sheets[firstSheetName]

				// Convert worksheet to 2D array
				const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 }) as any[][]
				resolve(jsonData)
			} catch (error) {
				reject(error)
			}
		}

		reader.onerror = (error) => reject(error)
		reader.readAsArrayBuffer(file)
	})
}

/**
 * Read all worksheets in an Excel file
 * @param file Excel File object
 * @returns An object containing all worksheets keyed by sheet name
 */
export async function readAllExcelSheets(file: File): Promise<Record<string, any[][]>> {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()

		reader.onload = (e) => {
			try {
				const data = e.target?.result
				// Read Excel file
				const workbook = XLSX.read(data, { type: "array" })

				// Read all worksheets
				const result: Record<string, any[][]> = {}

				workbook.SheetNames.forEach((sheetName) => {
					const worksheet = workbook.Sheets[sheetName]
					// Convert worksheet to 2D array
					result[sheetName] = XLSX.utils.sheet_to_json(worksheet, {
						header: 1,
					}) as any[][]
				})

				resolve(result)
			} catch (error) {
				reject(error)
			}
		}

		reader.onerror = (error) => reject(error)
		reader.readAsArrayBuffer(file)
	})
}

/**
 * Convert CSV text content to workbook data
 * @param csvContent CSV text content
 * @param fileName File name
 * @returns Workbook data
 */
function transformCsvToWorkbook(csvContent: string, fileName: string): any {
	// Simple CSV parsing: split by lines, then by comma
	const rows = csvContent.split("\n").map((row) => row.split(",").map((cell) => cell.trim()))
	// Use the existing conversion function to handle the 2D array
	return transformToWorkbookData(rows, fileName)
}

/**
 * Read a File as text
 * @param file File object
 * @returns File content string
 */
function readFileAsText(file: File): Promise<string> {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()
		reader.onload = () => resolve(reader.result as string)
		reader.onerror = reject
		reader.readAsText(file)
	})
}

/**
 * Transform raw data into document format
 * @param data Raw document data
 * @param fileName File name
 * @returns Transformed document data
 */
export function transformDataForDoc(data: any, fileName: string): any {
	// If data is already in the correct format, return as-is
	if (data && typeof data === "object" && data.id) {
		return data
	}

	// Simple document structure; real scenarios may require more complex conversion logic
	return {
		id: `doc-${Date.now()}`,
		name: fileName,
		type: "doc",
		body: {
			dataStream: data?.content || data || "",
			// More document-related configurations...
		},
		config: {
			view: {
				pageSize: {
					width: 794, // A4 width
					height: 1123, // A4 height
				},
			},
		},
	}
}

/**
 * Transform raw data into sheet format
 * @param data Raw sheet data
 * @param fileName File name
 * @returns Transformed sheet data
 */
export function transformDataForSheet(data: any, fileName: string): any {
	// If data is already in the correct format, return as-is
	if (data && typeof data === "object" && data.id && data.sheets) {
		return data
	}

	// Use transformToWorkbookData to generate a standard workbook
	return transformToWorkbookData(data, fileName)
}

/**
 * Transform raw data into slides format
 * @param data Raw slides data
 * @param fileName File name
 * @returns Transformed slides data
 */
export function transformDataForSlide(data: any, fileName: string): any {
	// If data is already in the correct format, return as-is
	if (data && typeof data === "object" && data.id) {
		return data
	}

	// Simple slides structure; real scenarios may require more complex conversion logic
	return {
		id: `slide-${Date.now()}`,
		name: fileName,
		type: "slide",
		slides: data?.slides || [
			{
				id: "slide1",
				title: "Title Slide",
				elements: [],
			},
		],
		// More slide-related configurations...
	}
}

/**
 * Convert general data to Univer workbook format (IWorkbookData)
 * @param data Raw data
 * @param fileName File name
 * @returns Univer-standard workbook data
 */
export function transformToWorkbookData(data: any, fileName?: string): any {
	// If data is already in standard Univer workbook format, return as-is
	if (data && data.id && data.sheets && data.sheetOrder) {
		return data
	}

	const workbookId = `workbook_${Date.now()}`
	const sheetId = `sheet_${Date.now()}`

	// If it's a string, try parsing as JSON
	if (typeof data === "string") {
		try {
			const parsedData = JSON.parse(data)
			if (parsedData && typeof parsedData === "object") {
				return transformToWorkbookData(parsedData, fileName)
			}
		} catch (e) {
			// If not valid JSON, treat it as simple text content
			data = [[data]]
		}
	}

	// Prepare cell data
	const cellData: ICellMatrix = {}
	let rowCount = 30
	let columnCount = 10

	// Handle array format (2D array → cell data)
	if (Array.isArray(data)) {
		rowCount = Math.max(rowCount, data.length)

		data.forEach((row, rowIndex) => {
			if (Array.isArray(row)) {
				// Update max column count
				columnCount = Math.max(columnCount, row.length)

				// Process each cell
				row.forEach((cellValue, colIndex) => {
					const rowKey = rowIndex.toString()
					const colKey = colIndex.toString()

					if (!cellData[rowKey]) {
						cellData[rowKey] = {}
					}

					cellData[rowKey][colKey] = {
						v: cellValue, // Cell value
					}
				})
			}
		})
	} else if (data && typeof data === "object" && data.cellData) {
		// If there is already a cellData structure
		Object.assign(cellData, data.cellData)
		rowCount = data.rowCount || rowCount
		columnCount = data.columnCount || columnCount
	}

	// Build worksheet data conforming to IWorksheetData
	const worksheetData = {
		id: sheetId,
		name: "Sheet1",
		tabColor: "",
		hidden: BooleanNumber.FALSE,
		rowCount: rowCount,
		columnCount: columnCount,
		defaultColumnWidth: 73,
		defaultRowHeight: 23,
		freeze: {
			startRow: -1,
			startColumn: -1,
			ySplit: 0,
			xSplit: 0,
		},
		mergeData: [],
		cellData: cellData,
		rowData: {},
		columnData: {},
		showGridlines: BooleanNumber.TRUE,
		rowHeader: {
			width: 46,
			hidden: BooleanNumber.FALSE,
		},
		columnHeader: {
			height: 20,
			hidden: BooleanNumber.FALSE,
		},
		rightToLeft: BooleanNumber.FALSE,
	}

	// Build workbook data conforming to IWorkbookData
	return {
		id: workbookId,
		name: fileName || "Workbook",
		appVersion: "0.5.0",
		locale: "zh-CN",
		styles: {},
		sheetOrder: [sheetId],
		sheets: {
			[sheetId]: worksheetData,
		},
		resources: [
			{
				name: "SHEET_DEFINED_NAME_PLUGIN",
				data: "",
			},
		],
	}
}

/**
 * Convert general data to Univer document format
 */
export function transformToDocumentData(data: any, fileName?: string): any {
	// If data is already in Univer format, return as-is
	if (data && data.id && data.body) {
		return data
	}

	// If it's a string, treat it as text content
	if (typeof data === "string") {
		try {
			// Try parsing as JSON
			const parsedData = JSON.parse(data)
			if (parsedData && typeof parsedData === "object") {
				return transformToDocumentData(parsedData)
			}
		} catch (e) {
			// If not valid JSON, treat it as text content
			return {
				id: `doc_${Date.now()}`,
				name: fileName || "Document",
				body: {
					dataStream: data,
					textRuns: [],
					paragraphs: [
						{
							startIndex: 0,
							endIndex: data.length,
						},
					],
				},
			}
		}
	}

	// Default: return an empty document
	return {
		id: `doc_${Date.now()}`,
		name: fileName || "Document",
		body: {
			dataStream: "",
			textRuns: [],
			paragraphs: [],
		},
	}
}

/**
 * Convert general data to Univer slides format
 */
export function transformToSlidesData(data: any, fileName?: string): any {
	// If data is already in Univer format, return as-is
	if (data && data.id && data.slides) {
		return data
	}

	// Default: return empty slides
	return {
		id: `slides_${Date.now()}`,
		name: fileName || "Presentation",
		slides: {
			slide1: {
				id: "slide1",
				title: "Title",
				pageElements: [],
			},
		},
		activeSlide: "slide1",
	}
}

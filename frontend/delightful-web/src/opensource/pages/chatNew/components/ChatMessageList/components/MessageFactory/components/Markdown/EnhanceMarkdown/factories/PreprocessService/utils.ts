// Helper function for handling tables

export const parseTable = (header: string, separator: string, rows: string) => {
	// Parse table header
	const headerCells = header
		.trim()
		.replace(/^\||\|$/g, "")
		.split("|")
		.map((cell) => cell.trim())

	// Parse separator row to determine alignment
	const alignments = separator
		.trim()
		.replace(/^\||\|$/g, "")
		.split("|")
		.map((cell) => {
			cell = cell.trim()
			if (cell.startsWith(":") && cell.endsWith(":")) return "center"
			if (cell.endsWith(":")) return "right"
			return "left"
		})

	// Parse data rows
	const dataRows = rows
		.trim()
		.split("\n")
		.map((row) =>
			row
				.replace(/^\||\|$/g, "")
				.split("|")
				.map((cell) => cell.trim()),
		)

	// Build HTML table
	let tableHtml = "<table><thead><tr>"

	// Add table header
	headerCells.forEach((cell, i) => {
		const align = alignments[i] || "left"
		tableHtml += `<th style="text-align:${align}">${cell}</th>`
	})

	tableHtml += "</tr></thead><tbody>"

	// Add data rows
	dataRows.forEach((row) => {
		tableHtml += "<tr>"
		row.forEach((cell, i) => {
			const align = alignments[i] || "left"
			tableHtml += `<td style="text-align:${align}">${cell}</td>`
		})
		tableHtml += "</tr>"
	})

	tableHtml += "</tbody></table>"

	return tableHtml
}

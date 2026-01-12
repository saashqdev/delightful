import { describe, it, expect, beforeEach } from "vitest"
	describe("parseTable", () => {
		it("TABLE_REGEX should match markdown tables", () => {
			const tableMarkdown = `| Name | Age | City |
| --- | --- | --- |
| Alice | 25 | London |
| Bob | 30 | Paris |`

			const matches = Array.from(tableMarkdown.matchAll(TABLE_REGEX))
			expect(matches.length).toBe(1)

			const match = matches[0]
			expect(match[1]).toBe("| Name | Age | City |") // header
			expect(match[2]).toBe("| --- | --- | --- |") // separator
			expect(match[3]).toBe("| Alice | 25 | London |\n| Bob | 30 | Paris |") // data rows
		})

		it("should parse a basic table", () => {
			const header = "| Name | Age | City |"
			const separator = "| --- | --- | --- |"
			const rows = "| Alice | 25 | London |\n| Bob | 30 | Paris |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("<table>")
			expect(result).toContain("<thead>")
			expect(result).toContain("<tbody>")
			expect(result).toContain("Name")
			expect(result).toContain("Alice")
			expect(result).toContain("Bob")
			expect(result).toContain("25")
			expect(result).toContain("30")
		})

		it("should handle left alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| --- | --- |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:left"')
		})

		it("should handle right alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| ---: | ---: |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:right"')
		})

		it("should handle center alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| :---: | :---: |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:center"')
		})

		it("should handle mixed alignment styles", () => {
			const header = "| Left Align | Center | Right Align |"
			const separator = "| --- | :---: | ---: |"
			const rows = "| left | center | right |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:left"')
			expect(result).toContain('style="text-align:center"')
			expect(result).toContain('style="text-align:right"')
		})

		it("should handle tables without leading and trailing pipes", () => {
			const header = "Name | Age"
			const separator = "--- | ---"
			const rows = "Alice | 25"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Name")
			expect(result).toContain("Age")
			expect(result).toContain("Alice")
			expect(result).toContain("25")
		})

		it("should handle a single-row table", () => {
			const header = "| Title |"
			const separator = "| --- |"
			const rows = "| Content |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("<th")
			expect(result).toContain("Title")
			expect(result).toContain("<td")
			expect(result).toContain("Content")
		})

		it("should handle multiple data rows", () => {
			const header = "| ID | Name |"
			const separator = "| --- | --- |"
			const rows = "| 1 | Project A |\n| 2 | Project B |\n| 3 | Project C |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Project A")
			expect(result).toContain("Project B")
			expect(result).toContain("Project C")
			// Should have three data rows plus the header row
			const matches = result.match(/<tr>/g)
			expect(matches?.length).toBe(4) // one header row + three data rows
		})

		it("should handle empty cells", () => {
			const header = "| Col1 | Col2 | Col3 |"
			const separator = "| --- | --- | --- |"
			const rows = "| Data |  | More data |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Data")
			expect(result).toContain("More data")
			// Check for empty table cells
			expect(result).toContain("<td")
		})

		it("should handle tables with special characters", () => {
			const header = "| Name | Description |"
			const separator = "| --- | --- |"
			const rows = "| Test & Demo | <script>alert('xss')</script> |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Test & Demo")
			expect(result).toContain("<script>alert('xss')</script>")
		})

		it("should handle irregular tables with mismatched columns", () => {
			const header = "| Col1 | Col2 | Col3 |"
			const separator = "| --- | --- | --- |"
			const rows = "| Data1 | Data2 |\n| A | B | C | D |" // first row missing a column, second row has an extra column

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Data1")
			expect(result).toContain("Data2")
			expect(result).toContain("A")
			expect(result).toContain("B")
			expect(result).toContain("C")
			expect(result).toContain("D")
		})

		it("should generate correct HTML structure", () => {
			const header = "| Title |"
			const separator = "| --- |"
			const rows = "| Content |"

			const result = parseTable(header, separator, rows)

			expect(result).toMatch(/^<table>/)
			expect(result).toMatch(/<\/table>$/)
			expect(result).toContain("<thead><tr>")
			expect(result).toContain("</tr></thead>")
			expect(result).toContain("<tbody>")
			expect(result).toContain("</tbody>")
		})

		it("should handle tables with extra whitespace and tabs", () => {
			const header = "|   Name   |  Age  |"
			const separator = "|   ---   | ---  |"
			const rows = "|  Alice  |   25   |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Name")
			expect(result).toContain("Age")
			expect(result).toContain("Alice")
			expect(result).toContain("25")
			// Ensure surrounding whitespace was trimmed
			expect(result).not.toContain("   Name   ")
		})

		it("should handle Unicode characters", () => {
			const header = "| 🎯 Target | 📊 Data |"
			const separator = "| --- | --- |"
			const rows = "| test | 100% |"

			const result = parseTable(header, separator, rows)


		it("should handle Unicode characters", () => {
			const header = "| 🎯 Target | 📊 Data |"
			const separator = "| --- | --- |"
			const rows = "| test | 100% |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("🎯 Target")
			expect(result).toContain("📊 Data")
			expect(result).toContain("test")
			expect(result).toContain("100%")
		})

		it("should default to left alignment when alignment is missing", () => {
			const header = "| Col1 | Col2 |"
			const separator = "| | |" // empty separator
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			// Should default to left alignment
			expect(result).toContain('style="text-align:left"')
			expect(result).toContain("Data1")
			expect(result).toContain("Data2")
		})
	})

	describe("blockquote with code blocks", () => {
		it("should not split code blocks inside blockquotes", () => {
			const markdown = `> ### Heading inside quote
> 
> The quote can include headings and other formatting.
> 
> \`\`\`javascript
> // Code inside quote
> console.log('Hello from quote');
> \`\`\``

			const result = PreprocessService.preprocess(markdown)

			// Should remain a single block without splitting
			expect(result).toHaveLength(1)
			expect(result[0]).toContain("Heading inside quote")
			expect(result[0]).toContain("console.log")
		})

		it("should split regular code blocks outside blockquotes", () => {
			const markdown = `# Regular heading

\`\`\`javascript
console.log('Outside quote');
\`\`\`

Another paragraph`

			const result = PreprocessService.preprocess(markdown)

			// Should be split into three blocks: heading, code block, and text
			expect(result).toHaveLength(3)
			expect(result[0]).toContain("Regular heading")
			expect(result[1]).toContain("console.log('Outside quote')")
			expect(result[2]).toContain("Another paragraph")
		})

		it("should handle mixed blockquotes and regular content", () => {
			const markdown = `> Quote start
> 
> \`\`\`javascript
> const inQuote = true;
> \`\`\`

\`\`\`javascript
const outsideQuote = true;
\`\`\`

More text`

			const result = PreprocessService.preprocess(markdown)

			// Should split into three blocks: quote (with code), external code block, and text
			expect(result).toHaveLength(3)
			expect(result[0]).toContain("Quote start")
			expect(result[0]).toContain("inQuote")
			expect(result[1]).toContain("outsideQuote")
			expect(result[2]).toContain("More text")
		})

		it("should not split images inside blockquotes", () => {
			const markdown = `> Image inside quote
> 
> ![alt text](image.jpg)
> 
> More quoted content`

			const result = PreprocessService.preprocess(markdown)

			// Should remain a single block
			expect(result).toHaveLength(1)
			expect(result[0]).toContain("Image inside quote")
			expect(result[0]).toContain("![alt text](image.jpg)")
			expect(result[0]).toContain("More quoted content")
		})
	})
			expect(result[0]).toContain("More quoted content")
		})
	})
			const markdown = "```js\nfunction test() {\n  return 'hello';\n}\n```\n![image](url)"
			const result = PreprocessService.splitBlockCode(markdown)
			expect(result).toEqual([
				"```js\nfunction test() {\n  return 'hello';\n}\n```",
				expect(match[1]).toBe("| Name | Age | City |") // header
				expect(match[2]).toBe("| --- | --- | --- |") // separator
				expect(match[3]).toBe("| Alice | 25 | London |\n| Bob | 30 | Paris |") // data rows

		it("should handle a code block immediately after an image", () => {
			it("should parse a basic table", () => {
				const header = "| Name | Age | City |"
				const separator = "| --- | --- | --- |"
				const rows = "| Alice | 25 | London |\n| Bob | 30 | Paris |"

		it("should handle code blocks that contain image syntax", () => {
			const markdown = '```js\nconst markdown = "![image](url)";\nconsole.log(markdown);\n```'
			const result = PreprocessService.splitBlockCode(markdown)
			expect(result).toEqual([markdown])
		})
				expect(result).toContain("Name")
				expect(result).toContain("Alice")
				expect(result).toContain("Bob")
				expect(result).toContain("25")
				expect(result).toContain("30")
		})

			it("should handle left alignment", () => {
				const header = "| Column1 | Column2 |"
				const separator = "| --- | --- |"
				const rows = "| Data1 | Data2 |"
		})
	})

	describe("preprocess", () => {
		it("should process markdown with default rules", () => {
			const result = service.preprocess("This is ~~strikethrough~~ text")
			it("should handle right alignment", () => {
				const header = "| Column1 | Column2 |"
				const separator = "| ---: | ---: |"
				const rows = "| Data1 | Data2 |"
		it("should process markdown with latex enabled", () => {
			const markdown = "This is a formula: $E=mc^2$ and some text"
			const result = service.preprocess(markdown, { enableLatex: true })
			expect(result.join("")).toContain('<DelightfulLatexInline math="E=mc^2" />')
		})

			it("should handle center alignment", () => {
				const header = "| Column1 | Column2 |"
				const separator = "| :---: | :---: |"
				const rows = "| Data1 | Data2 |"
		})

		it("should process citations", () => {
			const markdown = "This is a citation [[citation:1]]"
			const result = service.preprocess(markdown)
			expect(result.join("")).toContain('<DelightfulCitation index="1" />')
			it("should handle mixed alignment styles", () => {
				const header = "| Left Align | Center | Right Align |"
				const separator = "| --- | :---: | ---: |"
				const rows = "| left | center | right |"
			const result = service.preprocess(markdown)
			const joinedResult = result.join("")
			expect(joinedResult).toContain('<input type="checkbox" checked readonly')
			expect(joinedResult).toContain('<input type="checkbox"  readonly')
			expect(joinedResult).toContain("completed task")
			expect(joinedResult).toContain("incomplete task")
		})

			it("should handle tables without leading and trailing pipes", () => {
				const header = "Name | Age"
				const separator = "--- | ---"
				const rows = "Alice | 25"

		it("should handle markdown with only whitespace", () => {
			const result = service.preprocess("   \n   \t   ")
				expect(result).toContain("Name")
				expect(result).toContain("Age")
				expect(result).toContain("Alice")
				expect(result).toContain("25")
			const markdown =
				"Text\n\n```js\ncode\n```\n\n![image](url)\n\nMore ~~strikethrough~~ text"
			it("should handle a single-row table", () => {
				const header = "| Title |"
		})
				const rows = "| Content |"
		it("should protect URLs in code blocks from being converted to links", () => {
			const markdown = `Links in plain text should be converted: https://example.com

		it("should handle Unicode characters", () => {
			const header = "| 🎯 Target | 📊 Data |"
			const separator = "| --- | --- |"
			const rows = "| test | 100% |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("🎯 Target")
			expect(result).toContain("📊 Data")
			expect(result).toContain("test")
			expect(result).toContain("100%")
		})

		it("should default to left alignment when alignment is missing", () => {
			const header = "| Col1 | Col2 |"
			const separator = "| | |" // empty separator
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			// Should default to left alignment
			expect(result).toContain('style="text-align:left"')
			expect(result).toContain("Data1")
			expect(result).toContain("Data2")
		})
			it("should handle irregular tables with mismatched columns", () => {
				const header = "| Col1 | Col2 | Col3 |"
			const content = result.join(" ")
				const rows = "| Data1 | Data2 |\n| A | B | C | D |" // first row missing a column, second row has an extra column
			// URLs in plain text should be converted to links
			expect(content).toContain(
				'<a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a>',
				expect(result).toContain("Data1")
				expect(result).toContain("Data2")
				'<a href="https://final.com" target="_blank" rel="noopener noreferrer">https://final.com</a>',
			)

			// URLs inside code blocks should remain untouched
			expect(content).toContain('"api_url": "https://api.example.com/v1"')
			expect(content).toContain('"webhook": "https://webhook.example.com"')
			it("should generate correct HTML structure", () => {
				const header = "| Title |"
			// Ensure URLs inside code blocks are not converted to links
				const rows = "| Content |"
			expect(content).not.toContain('<a href="https://webhook.example.com"')
			expect(content).not.toContain('<a href="https://redirect.example.com"')
		})

		it("should process markdown tables", () => {
			const markdown = `| Name | Age | City |
| --- | --- | --- |
| Alice | 25 | Beijing |
| Bob | 30 | Shanghai |`

			const result = service.preprocess(markdown)
			const joinedResult = result.join("")

			expect(joinedResult).toContain("<table>")
			expect(joinedResult).toContain("<thead>")
			expect(joinedResult).toContain("<tbody>")
			expect(joinedResult).toContain("Name")
			expect(joinedResult).toContain("Alice")
			expect(joinedResult).toContain("Bob")
		})

		it("should process tables with different alignments", () => {
			const markdown = `| Left Align | Center | Right Align |
| --- | :---: | ---: |
| left | center | right |`

			const result = service.preprocess(markdown)
			const joinedResult = result.join("")

			expect(joinedResult).toContain("<table>")
			expect(joinedResult).toContain('style="text-align:left"')
			expect(joinedResult).toContain('style="text-align:center"')
			expect(joinedResult).toContain('style="text-align:right"')
		})

		it("should process complex markdown with tables, citations, and latex", () => {
			const markdown = `# testdocumentation

This documentation includes multiple elements:

## Table example
| Name | Formula | Reference |
| --- | :---: | ---: |
| Newton's second law | $F = ma$ | [[citation:1]] |
| Conservation of energy | $E = mc^2$ | [[citation:2]] |

## Task list
- [x] complete table feature
- [ ] add more tests
- [x] ~~optimize performance~~

Reference information: [[citation:3]]`

			const result = service.preprocess(markdown, { enableLatex: true })
			const joinedResult = result.join("")

			// validate table handling
			expect(joinedResult).toContain("<table>")
			expect(joinedResult).toContain("Newton's second law")

			// validate LaTeX handling
			expect(joinedResult).toContain('<DelightfulLatexInline math="F = ma" />')
			expect(joinedResult).toContain('<DelightfulLatexInline math="E = mc^2" />')

			// validate citation handling
			expect(joinedResult).toContain('<DelightfulCitation index="1" />')
			expect(joinedResult).toContain('<DelightfulCitation index="2" />')
			expect(joinedResult).toContain('<DelightfulCitation index="3" />')

			// validate task list
			expect(joinedResult).toContain('<input type="checkbox" checked readonly')
			expect(joinedResult).toContain('<input type="checkbox"  readonly')

			// validate strikethrough content
			expect(joinedResult).toContain('<span class="strikethrough">optimizationperformance</span>')
		})
	})

	describe("parseTable", () => {
		it("TABLE_REGEX should match markdown tables", () => {
			const tableMarkdown = `| Name | Age | City |
| --- | --- | --- |
| Alice | 25 | London |
| Bob | 30 | Paris |`

			const matches = Array.from(tableMarkdown.matchAll(TABLE_REGEX))
			expect(matches.length).toBe(1)

			const match = matches[0]
			expect(match[1]).toBe("| Name | Age | City |") // header
			expect(match[2]).toBe("| --- | --- | --- |") // separator
			expect(match[3]).toBe("| Alice | 25 | London |\n| Bob | 30 | Paris |") // data rows
		})

		it("should parse a basic table", () => {
			const header = "| Name | Age | City |"
			const separator = "| --- | --- | --- |"
			const rows = "| Alice | 25 | London |\n| Bob | 30 | Paris |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("<table>")
			expect(result).toContain("<thead>")
			expect(result).toContain("<tbody>")
			expect(result).toContain("Name")
			expect(result).toContain("Alice")
			expect(result).toContain("Bob")
			expect(result).toContain("25")
			expect(result).toContain("30")
		})

		it("should handle left alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| --- | --- |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:left"')
		})

		it("should handle right alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| ---: | ---: |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:right"')
		})

		it("should handle center alignment", () => {
			const header = "| Column1 | Column2 |"
			const separator = "| :---: | :---: |"
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:center"')
		})

		it("should handle mixed alignment styles", () => {
			const header = "| Left Align | Center | Right Align |"
			const separator = "| --- | :---: | ---: |"
			const rows = "| left | center | right |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain('style="text-align:left"')
			expect(result).toContain('style="text-align:center"')
			expect(result).toContain('style="text-align:right"')
		})

		it("should handle tables without leading and trailing pipes", () => {
			const header = "Name | Age"
			const separator = "--- | ---"
			const rows = "Alice | 25"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Name")
			expect(result).toContain("Age")
			expect(result).toContain("Alice")
			expect(result).toContain("25")
		})

		it("should handle a single-row table", () => {
			const header = "| Title |"
			const separator = "| --- |"
			const rows = "| Content |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("<th")
			expect(result).toContain("Title")
			expect(result).toContain("<td")
			expect(result).toContain("Content")
		})

		it("should handle multiple data rows", () => {
			const header = "| ID | Name |"
			const separator = "| --- | --- |"
			const rows = "| 1 | Project A |\n| 2 | Project B |\n| 3 | Project C |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Project A")
			expect(result).toContain("Project B")
			expect(result).toContain("Project C")
			// Should have three data rows plus the header row
			const matches = result.match(/<tr>/g)
			expect(matches?.length).toBe(4) // one header row + three data rows
		})

		it("should handle empty cells", () => {
			const header = "| Col1 | Col2 | Col3 |"
			const separator = "| --- | --- | --- |"
			const rows = "| Data |  | More data |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Data")
			expect(result).toContain("More data")
			// Check for empty table cells
			expect(result).toContain("<td")
		})

		it("should handle tables with special characters", () => {
			const header = "| Name | Description |"
			const separator = "| --- | --- |"
			const rows = "| Test & Demo | <script>alert('xss')</script> |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Test & Demo")
			expect(result).toContain("<script>alert('xss')</script>")
		})

		it("should handle irregular tables with mismatched columns", () => {
			const header = "| Col1 | Col2 | Col3 |"
			const separator = "| --- | --- | --- |"
			const rows = "| Data1 | Data2 |\n| A | B | C | D |" // first row missing a column, second row has an extra column

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Data1")
			expect(result).toContain("Data2")
			expect(result).toContain("A")
			expect(result).toContain("B")
			expect(result).toContain("C")
			expect(result).toContain("D")
		})

		it("should generate correct HTML structure", () => {
			const header = "| Title |"
			const separator = "| --- |"
			const rows = "| Content |"

			const result = parseTable(header, separator, rows)

			expect(result).toMatch(/^<table>/)
			expect(result).toMatch(/<\/table>$/)
			expect(result).toContain("<thead><tr>")
			expect(result).toContain("</tr></thead>")
			expect(result).toContain("<tbody>")
			expect(result).toContain("</tbody>")
		})

		it("should handle tables with extra whitespace and tabs", () => {
			const header = "|   Name   |  Age  |"
			const separator = "|   ---   | ---  |"
			const rows = "|  Alice  |   25   |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Name")
			expect(result).toContain("Age")
			expect(result).toContain("Alice")
			expect(result).toContain("25")
			// Ensure surrounding whitespace was trimmed
			expect(result).not.toContain("   Name   ")
		})

		it("should handle Unicode characters", () => {
		it("should handle tables with extra whitespace and tabs", () => {
			const header = "|   Name   |  Age  |"
			const separator = "|   ---   | ---  |"
			const rows = "|  Alice  |   25   |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("Name")
			expect(result).toContain("Age")
			expect(result).toContain("Alice")
			expect(result).toContain("25")
			// Ensure surrounding whitespace was trimmed
			expect(result).not.toContain("   Name   ")
		})

		it("should handle Unicode characters", () => {
			const header = "| 🎯 Target | 📊 Data |"
			const separator = "| --- | --- |"
			const rows = "| test | 100% |"

			const result = parseTable(header, separator, rows)

			expect(result).toContain("🎯 Target")
			expect(result).toContain("📊 Data")
			expect(result).toContain("test")
			expect(result).toContain("100%")
		})

		it("should default to left alignment when alignment is missing", () => {
			const header = "| Col1 | Col2 |"
			const separator = "| | |" // empty separator
			const rows = "| Data1 | Data2 |"

			const result = parseTable(header, separator, rows)

			// Should default to left alignment
			expect(result).toContain('style="text-align:left"')
			expect(result).toContain("Data1")
			expect(result).toContain("Data2")
		})
	})

	describe("blockquote with code blocks", () => {
		it("should not split code blocks inside blockquotes", () => {
			const markdown = `> ### Heading inside quote
> 
> The quote can include headings and other formatting.
> 
> \`\`\`javascript
> // Code inside quote
> console.log('Hello from quote');
> \`\`\``

			const result = PreprocessService.preprocess(markdown)

			// Should remain a single block without splitting
			expect(result).toHaveLength(1)
			expect(result[0]).toContain("Heading inside quote")
			expect(result[0]).toContain("console.log")
		})

		it("should split regular code blocks outside blockquotes", () => {
			const markdown = `# Regular heading

\`\`\`javascript
console.log('Outside quote');
\`\`\`

Another paragraph`

			const result = PreprocessService.preprocess(markdown)

			// Should be split into three blocks: heading, code block, and text
			expect(result).toHaveLength(3)
			expect(result[0]).toContain("Regular heading")
			expect(result[1]).toContain("console.log('Outside quote')")
			expect(result[2]).toContain("Another paragraph")
		})

		it("should handle mixed blockquotes and regular content", () => {
			const markdown = `> Quote start
> 
> \`\`\`javascript
> const inQuote = true;
> \`\`\`

\`\`\`javascript
const outsideQuote = true;
\`\`\`

More text`

			const result = PreprocessService.preprocess(markdown)

			// Should split into three blocks: quote (with code), external code block, and text
			expect(result).toHaveLength(3)
			expect(result[0]).toContain("Quote start")
			expect(result[0]).toContain("inQuote")
			expect(result[1]).toContain("outsideQuote")
			expect(result[2]).toContain("More text")
		})

		it("should not split images inside blockquotes", () => {
			const markdown = `> Image inside quote
> 
> ![alt text](image.jpg)
> 
> More quoted content`

			const result = PreprocessService.preprocess(markdown)

			// Should remain a single block
			expect(result).toHaveLength(1)
			expect(result[0]).toContain("Image inside quote")
			expect(result[0]).toContain("![alt text](image.jpg)")
			expect(result[0]).toContain("More quoted content")
		})
	})

	describe("isInsideBlockquote method", () => {
		it("should detect content inside blockquote", () => {
			const markdown = `> This is a quote
> with multiple lines
> 
> \`\`\`javascript
> console.log('test');
> \`\`\``

			const codeStart = markdown.indexOf("```javascript")
			const codeEnd = markdown.lastIndexOf("```") + 3

			// Use the private method for testing via class assertion
			const service = PreprocessService as any
			const result = service.isInsideBlockquote(markdown, codeStart, codeEnd)

			expect(result).toBe(true)
		})

		it("should detect content outside blockquote", () => {
			const markdown = `# Regular content

\`\`\`javascript
console.log('test');
\`\`\``

			const codeStart = markdown.indexOf("```javascript")
			const codeEnd = markdown.lastIndexOf("```") + 3

			// Use the private method for testing
			const service = PreprocessService as any
			const result = service.isInsideBlockquote(markdown, codeStart, codeEnd)

			expect(result).toBe(false)
		})
	})
})

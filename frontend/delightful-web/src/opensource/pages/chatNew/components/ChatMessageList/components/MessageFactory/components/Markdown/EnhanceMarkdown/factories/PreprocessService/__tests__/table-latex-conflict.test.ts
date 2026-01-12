import { describe, it, expect } from "vitest"
import PreprocessService from "../index"

describe("PreprocessService - Table and LaTeX Conflict", () => {
	it("should not interfere with table cells containing dollar signs", () => {
		const markdown = `
| Product | Price | Stock |
|---------|-------|-------|
| Product A | $15  | 100  |
| Product B | $25  | 50   |
| Product C | $35  | 20   |
		`.trim()

		const result = PreprocessService.preprocess(markdown, { enableLatex: true })
		const content = result.join("")

		// Should generate table HTML
		expect(content).toContain("<table>")
		expect(content).toContain('<th style="text-align:left">Product</th>')
		expect(content).toContain('<th style="text-align:left">Price</th>')
		expect(content).toContain('<th style="text-align:left">Stock</th>')

		// Should display dollar signs correctly in table cells
		expect(content).toContain("$15")
		expect(content).toContain("$25")
		expect(content).toContain("$35")

		// Should NOT contain LaTeX components for table prices
		expect(content).not.toContain('<DelightfulLatexInline math="15')
		expect(content).not.toContain('<DelightfulLatexInline math="25')
		expect(content).not.toContain('<DelightfulLatexInline math="35')

		// Should NOT contain any placeholder remnants
		expect(content).not.toContain("__DOLLAR_PLACEHOLDER__")
	})

	it("should still process actual LaTeX formulas outside of tables", () => {
		const markdown = `
| Product | Price | Stock |
|---------|-------|-------|
| Product A | $15  | 100  |

This is a math formula: $E = mc^2$
		`.trim()

		const result = PreprocessService.preprocess(markdown, { enableLatex: true })
		const content = result.join("")

		// Should display the table with correct dollar signs
		expect(content).toContain("$15")

		// Should still process real LaTeX formulas
		expect(content).toContain('<DelightfulLatexInline math="E = mc^2" />')

		// Should NOT contain any placeholder remnants
		expect(content).not.toContain("__DOLLAR_PLACEHOLDER__")
	})

	it("should handle complex table with various content types", () => {
		const markdown = `
| Type | Value | Note |
|------|-------|----- |
| Price | $100.50 | Tax included |
| Formula | This is not a formula: $x + y$ | Just text |
| Quantity | 50 | Units |

Actual math formula: $\\sum_{i=1}^{n} x_i = total$
		`.trim()

		const result = PreprocessService.preprocess(markdown, { enableLatex: true })
		const content = result.join("")

		// Table content should display dollar signs correctly
		expect(content).toContain("$100.50")
		expect(content).toContain("This is not a formula: $x + y$")

		// Real LaTeX outside table should work (note: single backslash in output)
		expect(content).toContain('<DelightfulLatexInline math="\\sum_{i=1}^{n} x_i = total" />')

		// Should not create LaTeX components for table content
		expect(content).not.toContain('<DelightfulLatexInline math="100.50')
		expect(content).not.toContain('<DelightfulLatexInline math="x + y')

		// Should NOT contain any placeholder remnants
		expect(content).not.toContain("__DOLLAR_PLACEHOLDER__")
	})

	it("should handle empty table cells and edge cases", () => {
		const markdown = `
| Product | Price | Status |
|---------|-------|--------|
| A    | $0   | Valid |
| B    |      | No price |
| C    | $$$  | Multiple dollar signs |
		`.trim()

		const result = PreprocessService.preprocess(markdown, { enableLatex: true })
		const content = result.join("")

		// Should display all dollar signs correctly
		expect(content).toContain("$0")
		expect(content).toContain("$$$")

		// Should handle empty cells (with style attribute)
		expect(content).toContain('<td style="text-align:left"></td>')

		// Should NOT contain any placeholder remnants
		expect(content).not.toContain("__DOLLAR_PLACEHOLDER__")
	})
})

export const fixPrompt = (code: string, error: string) => {
	return `
Here is the mermaid code:

\`\`\`text
${code}
\`\`\`

Error message: ${error}

Please help me fix this error.
  `
}

export const FALLBACK_LANG = "txt"

/**
 * Generate a code block
 * @param lang Language
 * @param code Code content
 * @returns
 */
export const genCodeSection = (lang: string | undefined, code: string) => {
	return `
\`\`\`${lang ?? FALLBACK_LANG}
${code}
\`\`\`
`
}

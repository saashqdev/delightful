/** Parser options */
export interface CompileOptions {
	/** Prefix for template syntax */
	notation?: string
	/** Opening delimiter for template syntax */
	notationStart?: string
	/** Closing delimiter for template syntax */
	notationEnd?: string
}

export interface ResolveOptions {
	/** Skip failed expressions; otherwise return undefined for failures */
	partial?: boolean
}

/** Template parsing options */
export interface TemplateOptions extends CompileOptions, ResolveOptions {}

/** Detail types */
export enum DetailType {
	Terminal = "terminal",
	Browser = "browser",
	Search = "search",
	Html = "html",
	Text = "text",
	Md = "md",
	Pdf = "pdf",
	Code = "code",
	Empty = "empty",
	Excel = "excel",
	PowerPoint = "powerpoint",
	Image = "image",
}

/** Detail map */
export interface DetailData {
	[DetailType.Terminal]: DetailTerminalData
	[DetailType.Browser]: DetailBrowserData
	[DetailType.Search]: DetailSearchData
	[DetailType.Html]: DetailHTMLData
	[DetailType.Text]: DetailTextData
	[DetailType.Md]: DetailMDData
	[DetailType.Empty]: any
	[DetailType.Excel]: DetailUniverData
	[DetailType.PowerPoint]: DetailUniverData
}

/** Terminal */
export interface DetailTerminalData {
	action: "execute" | string
	finished: boolean
	shellId: string
	command: string
	outputType?: "append" | string
	output: string[]
	code: string
}

/** Browser */
export interface DetailBrowserData {
	url: string
	screenshot: string
	preview: string
}

/** MD file */
export interface DetailMdData {
	action: "write" | "update" | string
	path: string
	content: string
	oldContent?: string
}

/** Search */
export interface DetailSearchData {
	data: {
		favicon?: string
		title: string
		link: string
		snippet: string
	}[]
}

/** Arguments */
export interface DetailArgumentsData {
	command: string
	file: string
	path: string
	content?: string
}

/** HTML */
export interface DetailHTMLData {
	content: string
	title: string
}

/** Tool */
export interface DetailToolData {
	name: string
	action: string
}

/** Text */
export interface DetailTextData {
	content: string
}

/** Rich text (MD) */
export interface DetailMDData {
	content: string
}

export interface DetailTodoData {
	content: string
}

/** Univer data */
export interface DetailUniverData {
	content: any
	file_name: string
	file_extension?: string
}

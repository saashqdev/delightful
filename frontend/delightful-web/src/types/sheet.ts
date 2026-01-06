export enum Schema {
	TEXT = "TEXT", // Multi-line text
	SELECT = "SELECT", // Single select dropdown
	MULTIPLE = "MULTIPLE", // Multi-select dropdown
	DATE = "DATETIME", // Date
	NUMBER = "NUMBER", // Number
	CHECKBOX = "CHECKBOX", // Checkbox
	MEMBER = "MEMBER", // Member (single/multi-select)
	LINK = "LINK", // Hyperlink
	ATTACHMENT = "ATTACHMENT", // Attachment
	CREATE_AT = "CREATE_AT", // Creation time
	UPDATE_AT = "UPDATE_AT", // Update time
	CREATED = "CREATED", // Creator
	UPDATED = "UPDATED", // Modifier
	TODO_STATUS = "TODO_STATUS", // Todo completion status
	TODO_FINISHED_AT = "TODO_FINISHED_AT", // Todo completion time
	LOOKUP = "LOOKUP", // Lookup reference
	QUOTE_RELATION = "QUOTE_RELATION", // One-way relation
	MUTUAL_RELATION = "MUTUAL_RELATION", // Two-way relation
	ROW_ID = "ROW_ID", // Unique ID
	FORMULA = "FORMULA",
	BUTTON = "BUTTON", // Button
}

export namespace Sheet {
	export interface User {
		id: string
		real_name: string
		avatar: string
		description: string
		position: string
		department: string | null
	}

	export interface ColumnProps {
		format?: string
		renderType?: string
		prefix?: string
		suffix?: string
		options?: any[]
		[key: string]: any
	}

	export interface Column {
		id: string
		label: string
		columnType: Schema
		columnProps: ColumnProps
		columnId?: string
	}

	export interface ViewConfig {
		rowHeight: number
	}

	export interface ColumnConfig {
		width: number
		visible: boolean
		statisticsType: string
	}

	export interface Searches {
		groups: any[]
		conjunction: string
	}

	export interface View {
		viewId: string
		viewName: string
		viewType: string
		viewConfig: ViewConfig
		groups: any[]
		sorts: any[]
		searches: Searches
		columnsConfig: { [key: string]: ColumnConfig }
		rowIndexes: string[]
		columnIndices: string[]
		viewProtectedType: string
		creator: User
		modifier: User
		frozenColumnId: string
	}

	export interface Content {
		primaryKey: string
		columns: { [key: string]: Column }
		views: { [key: string]: View }
		viewIndices: string[]
	}

	export interface Detail {
		id: string
		name: string
		content: Content
		draft_content: any[]
		creator: User
		modifier: User
		created_at: string
		updated_at: string
		type: number
		unread_announcement_count: number
	}
}

export namespace File {
	export enum SpaceType {
		Personal = 1, // Personal drive
		Official = 2, // Enterprise drive
	}
	// 0 directory; 1 multi-dimensional table; 2 word; 3 excel; 4 mind notes; 5 ppt; 6 pdf; 7 old cloud document; 16 new cloud document; 22 whiteboard; 24 magic app.
	export enum FileType {
		Category = 0,
		MultiTable = 1,
		Document = 16,
	}
	export type RequestParams = {
		name: string
		space_type: SpaceType
		file_type: FileType
	}

	export interface Detail {
		attributes?: Attributes
		child_number?: number
		created_at?: string
		creator?: Creator
		extension?: string
		favorite_id?: null
		file_id?: string
		file_type?: number
		id?: string
		is_favorite?: number
		is_quick_file?: number
		is_subscribe?: boolean
		modified_at?: string
		modifier?: Modifier
		name?: string
		operation?: string
		path?: Path[]
		recent_opened_at?: string
		shared_at?: string
		space_type?: number
		[property: string]: any
	}

	export interface Attributes {
		advanced_permission_status: number
		[property: string]: any
	}

	export interface Creator {
		avatar: string
		description: string
		id: string
		position: string
		real_name: string
		[property: string]: any
	}

	export interface Modifier {
		avatar: string
		description: string
		id: string
		position: string
		real_name: string
		[property: string]: any
	}

	export interface Path {
		id: number | string
		name: string
		operation: string
		space_type: number
		type: string
		[property: string]: any
	}
}

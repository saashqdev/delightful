import type { DelightfulModalProps } from "@/opensource/components/base/DelightfulModal"

/** Share type */
export enum ShareType {
	OnlySelf = 1,
	Organization = 2,
	DepartmentOrMember = 3,
	Internet = 4,
}

/** Share context type */
export enum ShareContextType {
	/** Topic */
	Topic = 1,
}
export enum ResourceType {
	/** Topic */
	Topic = 5,
}

/** Share context */
export type ShareContext = TopicShareContext

/** Share context - Topic */
export interface TopicShareContext {
	resource_type: ResourceType.Topic
	resource_id: string
}

export interface ShareProps {
	shareContext: ShareContext
	types: ShareType[]
	type: ShareType
	onChangeType?: (type: ShareType) => void
	extraData?: any
	setExtraData?: (extraData: any) => void
	/** Get share settings validation function */
	getValidateShareSettings?: (validate: () => boolean) => void
	handleOk?: (type: ShareType, extraData?: any) => void
	shareUrl?: string
	handleCopyLink?: () => void
}

export interface ShareModalProps extends Omit<ShareProps, "type">, DelightfulModalProps {
	afterSubmit?: ({ type, extraData }: { type: ShareType; extraData: any }) => void
}

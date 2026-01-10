/**
 * Custom props passed from business components
 */
import React from "react"
import { DelightfulFlow } from "@/DelightfulFlow/types/flow"

// Split large Context into multiple smaller Contexts
// 1. UI Context - most frequently changing part
export type ExternalUICtx = {
    header?: {
        buttons?: React.ReactElement
        backIcon?: React.ReactElement
        showImage?: boolean
        editEvent?: () => void
        defaultImage?: string
        customTags?: React.ReactElement
    }
    materialHeader?: React.ReactElement
    nodeToolbar?: {
        list: Array<{
            icon: () => React.ReactElement
            tooltip?: string
        }>
        mode?: "append" | "replaceAll"
    }
}

// 2. Config Context - infrequently changing part
export type ExternalConfigCtx = {
    paramsName?: DelightfulFlow.ParamsName
    onlyRenderVisibleElements?: boolean
    layoutOnMount?: boolean
    allowDebug?: boolean
    showExtraFlowInfo?: boolean
    omitNodeKeys?: string[]
}

// 3. Ref/Callback Context - reference types, rarely change
export type ExternalRefCtx = {
    flowInteractionRef?: React.MutableRefObject<any>
}

// Complete Context for backward compatibility
export type ExternalCtx = React.PropsWithChildren<
    ExternalUICtx & ExternalConfigCtx & ExternalRefCtx
>

// Create individual Contexts
export const ExternalUIContext = React.createContext<ExternalUICtx>({
    header: undefined,
    materialHeader: undefined,
    nodeToolbar: undefined,
})

export const ExternalConfigContext = React.createContext<ExternalConfigCtx>({
    paramsName: undefined,
    onlyRenderVisibleElements: false,
    layoutOnMount: false,
    allowDebug: false,
    showExtraFlowInfo: false,
    omitNodeKeys: [],
})

export const ExternalRefContext = React.createContext<ExternalRefCtx>({
    flowInteractionRef: undefined,
})

// Preserve original Context to maintain backward compatibility
export const ExternalContext = React.createContext<ExternalCtx>({
    header: {},
    nodeToolbar: { list: [] },
    materialHeader: undefined,
    paramsName: undefined,
    onlyRenderVisibleElements: false,
    layoutOnMount: false,
    allowDebug: false,
    showExtraFlowInfo: false,
    flowInteractionRef: undefined,
    omitNodeKeys: [],
})

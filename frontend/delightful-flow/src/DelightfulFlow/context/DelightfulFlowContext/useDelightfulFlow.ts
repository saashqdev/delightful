import React from "react"
import { DelightfulFlowContext } from "./Context"
import { useStore as useZustandStore } from 'zustand';
import { BaseNodeType } from "@/DelightfulFlow/register/node";

export const useDelightfulFlow = () => {
	const store = React.useContext(DelightfulFlowContext)

    if (!store) {
        throw new Error('useDelightfulFlow must be used within a DelightfulFlowProvider');
    }

    const displayMaterialTypes = useZustandStore(store, state => state.displayMaterialTypes) as BaseNodeType[];

    return {
        displayMaterialTypes,
        updateDisplayMaterialType: store.getState().updateDisplayMaterialType,
    };
}

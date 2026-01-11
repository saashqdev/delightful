/* eslint-disable @typescript-eslint/no-shadow */
/* eslint-disable @typescript-eslint/naming-convention */

import { IconArrowLeftFromArc } from "@tabler/icons-react"
import React from "react"
import Start from "./nodes/Start/Start"

export enum customNodeType {
	Start = 1,
}

export const nodeSchemaMap = {
	[customNodeType.Start]: {
		schema: {
			label: "Start Node",
			icon: <IconArrowLeftFromArc color="#fff" stroke={2} size={18} />,
			color: "#315CEC",
			id: customNodeType.Start,
			desc: "When the following events are triggered, the flow will start executing from this module",
			handle: {
				withSourceHandle: false,
				withTargetHandle: false,
			},
			style: {
				width: "480px",
			},
		},
		component: () => <Start />,
	},
}

export default nodeSchemaMap


import type { QuickInstruction } from "@/types/bot"
import { ExtensionName } from "./constants"

/** Generate template instruction node */
export const genTemplateInstructionNode = (instruction?: QuickInstruction, value?: string) => {
	return {
		type: ExtensionName,
		attrs: {
			instruction,
			value: value ?? "",
		},
	}
}

import DelightfulMenu from "@/opensource/components/base/DelightfulMenu"
import { InstructionMode, type SelectorQuickInstruction } from "@/types/bot"
import { Popover } from "antd"
import type { HTMLAttributes } from "react"
import { useState, useMemo } from "react"
import { useMemoizedFn } from "ahooks"
import { observer } from "mobx-react-lite"
import ConversationBotDataService from "@/opensource/services/chat/conversation/ConversationBotDataService"
import ActionWrapper from "../ActionWrapper"
import { useStyles } from "./styles"
import InstructionExplanation from "../InstructionExplanation/Index"
import { useNonResidencyConfigCleanup } from "../../hooks/useNonResidencyConfigCleanup"

interface InstructionItemProps extends HTMLAttributes<HTMLDivElement> {
	instruction: SelectorQuickInstruction
}

/**
 * Selector instruction
 */
const SelectorAction = observer(({ instruction, ...rest }: InstructionItemProps) => {
	const { styles } = useStyles()

	const [open, setOpen] = useState(false)

	const { residency = false, id } = instruction

	const {
		instructConfig: { [id]: configValue },
		innerInstructConfig: { [id]: innerConfigValue } = {},
		handleSelectorQuickInstructionClick,
		removeSelectorValueFromConfig,
		updateRemoteInstructionConfig,
	} = ConversationBotDataService

	// Check if residency feature is disabled, if so clear historical configuration values
	useNonResidencyConfigCleanup(id, innerConfigValue, residency, updateRemoteInstructionConfig)

	/**
	 * Target option
	 */
	const targetValue = useMemo(() => {
		if (!residency && instruction.instruction_type !== InstructionMode.Flow) return undefined

		if (configValue) {
			return instruction.values.find((i) => i.id === configValue)
		}
		return undefined
	}, [residency, instruction.instruction_type, instruction.values, configValue])

	/**
	 * optionconfiguration
	 */
	const menuItems = useMemo(() => {
		return (
			instruction?.values?.map((i) => ({
				label: (
					<InstructionExplanation data={i.instruction_explanation}>
						{i.name}
					</InstructionExplanation>
				),
				key: i.id,
			})) ?? []
		)
	}, [instruction])

	/**
	 * Selected option
	 */
	const selectedKeys = useMemo(() => {
		if (!targetValue) return []
		return [targetValue.id]
	}, [targetValue])

	/**
	 * Click option
	 */
	const handleClick = useMemoizedFn((v) => {
		// If residency or flow instruction
		if (
			(instruction.residency || instruction.instruction_type === InstructionMode.Flow) &&
			selectedKeys[0] === v.key
		) {
			removeSelectorValueFromConfig(instruction)
		} else {
			handleSelectorQuickInstructionClick(v.key, instruction)
		}
		setOpen(false)
	})

	/**
	 * When there's only one option, can click directly
	 */
	if (menuItems.length === 1) {
		return (
			<ActionWrapper active={false} onClick={() => handleClick(menuItems[0])} {...rest}>
				{instruction.name}
			</ActionWrapper>
		)
	}

	return (
		<Popover
			rootClassName={styles.popover}
			trigger="click"
			placement="topLeft"
			open={open}
			arrow={false}
			onOpenChange={setOpen}
			content={
				<DelightfulMenu
					items={menuItems}
					className={styles.menu}
					onClick={handleClick}
					selectedKeys={selectedKeys}
				/>
			}
		>
			<ActionWrapper active={open || !!targetValue?.name} {...rest}>
				{targetValue?.name ?? instruction.name}
			</ActionWrapper>
		</Popover>
	)
})

export default SelectorAction

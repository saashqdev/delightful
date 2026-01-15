import { memo, useEffect, useMemo, useState } from "react"
import type { DragOverEvent, DragStartEvent } from "@dnd-kit/core"
import {
	DndContext,
	useSensor,
	useSensors,
	PointerSensor,
	DragOverlay,
	rectIntersection,
} from "@dnd-kit/core"
import { arrayMove } from "@dnd-kit/sortable"
import { Flex, message } from "antd"
import type {
	QuickInstruction,
	QuickInstructionList,
	SystemInstruct,
	CommonQuickInstruction,
} from "@/types/bot"
import { InstructionGroupType } from "@/types/bot"
import { useTranslation } from "react-i18next"
import { useMemoizedFn } from "ahooks"
import { isSystemItem } from "../../const"
import { DropZone } from "./components/DropZone"
import { DragItem } from "./components/DragItem"

export type IdType = InstructionGroupType | string

interface InstructionListProps {
	instructionsList: QuickInstructionList[]
	updateInstructsList: (list: QuickInstructionList[]) => void
	addInstruction: (type: InstructionGroupType) => void
	selectInstruction: (type: InstructionGroupType, data: CommonQuickInstruction) => void
	deleteInstruction: (type: InstructionGroupType, data: CommonQuickInstruction) => void
}

const InstructionList = memo(
	({
		instructionsList,
		selectInstruction,
		addInstruction,
		deleteInstruction,
		updateInstructsList,
	}: InstructionListProps) => {
		const { t } = useTranslation("interface")

		const [list, setList] = useState<QuickInstructionList[]>([])
		const [activeItem, setActiveItem] = useState<QuickInstruction>()
		const [placeholderId, setPlaceholderId] = useState<IdType | null>(null)
		const [activeContainer, setActiveContainer] = useState<InstructionGroupType | null>(null)
		const [isCrossContainer, setIsCrossContainer] = useState(false)
		const [isDrag, setIsDrag] = useState(false)
		// const sensors = useSensors(useSensor(PointerSensor));

		const sensors = useSensors(
			useSensor(PointerSensor, {
				activationConstraint: {
					distance: 4,
				},
			}),
		)

		useEffect(() => {
			if (instructionsList.length > 0) {
				setList(instructionsList)
			}
		}, [instructionsList])

	// Find which container the ID belongs to
		const findContainer = (id: IdType) => {
			return list.find((container) => container.items.some((item) => item.id === id))
				?.position
		}

		const clear = () => {
			setPlaceholderId(null)
			setActiveItem(undefined)
			setActiveContainer(null)
			setIsCrossContainer(false)
			setIsDrag(false)
		}

		// Start dragging
	const handleDragStart = (event: DragStartEvent) => {
		setIsDrag(true)
	}

	// During drag
	const handleDragOver = (event: DragOverEvent) => {
		const { over } = event
		if (!over) return

		setIsCrossContainer(true)
		// Target item ID or target container ID
		const overId = over.id as IdType

		const isZone = over.data.current?.isContainer || false

		// If hovering over a container
		if (isZone) {
			// Same container, clear
			if (activeContainer === overId) {
				setPlaceholderId(null)
			}
			// Not the container of current moving item, set placeholder to container id
			else if (!placeholderId) {
				setPlaceholderId(overId)
				return
			}
		}

		// Hovering over an item, set placeholder to item's id

		// Target container
		const overContainer = findContainer(overId)

		if (!activeContainer || !overContainer) return

		// Check if drag crosses containers
		if (overContainer !== activeContainer) {
			// If target container is toolbar, check if target item is a system instruction, if yes cannot move, otherwise can move
			if (overContainer === InstructionGroupType.TOOL) {
				const overItem = over.data.current?.item

				if (overItem && isSystemItem(overItem)) return
				setPlaceholderId(overId)
			} else if (placeholderId !== overId) {
				setPlaceholderId(overId)
			}
		} else {
			// Dragging within the same container does not set placeholder
			setPlaceholderId(null)
		}
	}

	// Drag end
	const handleDragEnd = (event: any) => {
			const { active, over } = event
			if (!over) {
				setPlaceholderId(null)
				return
			}

			const activeId = active.id
			const overId = over.id

			const fromZone = findContainer(activeId)
			const overZone = findContainer(overId)

			if (!fromZone) {
				setPlaceholderId(null)
				return
			}

			const newItems = [...list]

			// Get source zone and target zone data
			let fromZoneData = newItems.find((item) => item.position === fromZone)
			let overZoneData = newItems.find((item) => item.position !== fromZone)

			// Initialize to default values
			if (!fromZoneData) {
				fromZoneData = { position: fromZone, items: [] }
				newItems.push(fromZoneData)
			}
			if (!overZoneData) {
				const targetPosition =
					InstructionGroupType.TOOL === fromZone
						? InstructionGroupType.DIALOG
						: InstructionGroupType.TOOL
				overZoneData = { position: targetPosition, items: [] }
				newItems.push(overZoneData)
			}

			if (fromZone === overZone) {
				// **Sort within same container**
				const newIndex = fromZoneData.items.findIndex((item) => item.id === overId)
				const newItem = fromZoneData.items[newIndex]
				if (!newItem) return
				if (isSystemItem(newItem)) {
					message.error(t("agent.systemInstructionError"))
					clear()
					return
				}

				const oldIndex = fromZoneData.items.findIndex((item) => item.id === activeId)
				fromZoneData.items = arrayMove(fromZoneData.items, oldIndex, newIndex)
			} else {
				// **Move across containers**

				const movedItem = fromZoneData.items.find((item) => item.id === activeId)
				if (!movedItem) return

				// Target zone cannot have duplicate instruction names
				if (overZoneData.items.findIndex((i) => i.name === movedItem.name) !== -1) {
					message.error(t("agent.nameRepeatError"))
					clear()
					return
				}

				// Target zone can have at most 5 instructions
				const filterItems = overZoneData.items.filter((item) => !isSystemItem(item)) || []
				if (filterItems.length >= 5) {
					message.error(t("agent.maxInstruction"))
					clear()
					return
				}

				fromZoneData.items = fromZoneData.items.filter((item) => item.id !== activeId)
				const overIndex = overZoneData.items.findIndex((item) => item.id === placeholderId)
				// If position not found, insert at the end
				if (overIndex !== -1) {
					overZoneData.items.splice(overIndex, 0, movedItem!)
				} else {
					overZoneData.items.push(movedItem!)
				}
			}

			// Update data source
			updateInstructsList(newItems)
			setList(newItems)
			clear()
		}

		const zoneItems = useMemo(() => {
			return [
				{
					id: InstructionGroupType.TOOL,
					title: t("agent.toolbar"),
					data:
						list.find((item) => item.position === InstructionGroupType.TOOL)?.items ||
						[],
				},
				{
					id: InstructionGroupType.DIALOG,
					title: t("agent.dialog"),
					data:
						list.find((item) => item.position === InstructionGroupType.DIALOG)?.items ||
						[],
				},
			]
		}, [list, t])

		// Toggle system instruction visibility
		const handleShowSystem = useMemoizedFn((itemId?: string) => {
			if (!itemId) return
			const newList = instructionsList.map((group) => {
				if (group.position === InstructionGroupType.TOOL) {
					const newItems = group.items.map((item) => {
						if (item.id === itemId) {
							return { ...item, hidden: !(item as SystemInstruct).hidden }
						}
						return item
					})
					return { ...group, items: newItems }
				}
				return group
			})

			updateInstructsList(newList)
		})

		return (
			<Flex vertical gap={6} flex={1} style={{ height: "100%" }}>
				<DndContext
					sensors={sensors}
					collisionDetection={rectIntersection}
					onDragStart={handleDragStart}
					onDragOver={handleDragOver}
					onDragEnd={handleDragEnd}
				>
					<Flex gap={12} style={{ height: "100%" }}>
						{zoneItems.map((zone) => (
							<DropZone
								key={zone.id}
								title={zone.title}
								id={zone.id}
								items={zone.data}
								isDrag={isDrag}
								fromZone={activeContainer}
								activeItem={activeItem}
								isCrossContainer={isCrossContainer}
								placeholderId={placeholderId}
								addInstruction={addInstruction}
								selectInstruction={selectInstruction}
								deleteInstruction={deleteInstruction}
								handleShowSystem={handleShowSystem}
							/>
						))}
					</Flex>
					<DragOverlay>
						{isCrossContainer && activeItem ? (
							<DragItem
								type={InstructionGroupType.TOOL}
								isSortable
								id={activeItem.id}
								item={activeItem}
							/>
						) : null}
					</DragOverlay>
				</DndContext>
			</Flex>
		)
	},
)

export default InstructionList






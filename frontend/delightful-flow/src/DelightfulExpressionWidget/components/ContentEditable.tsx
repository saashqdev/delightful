/* eslint-disable @typescript-eslint/naming-convention */
import { useMemoizedFn, useMount, useResetState, useUpdateEffect } from "ahooks"
import clsx from "clsx"
import { cloneDeep, endsWith, last } from "lodash"
import React, {
	forwardRef,
	memo,
	useCallback,
	useEffect,
	useImperativeHandle,
	useMemo,
	useRef,
	useState,
} from "react"
import { ExpressionMode, KeyCodeMap, TextAreaModeTrigger } from "../constant"
import { useGlobalContext } from "../context/GlobalContext/useGlobalContext"
import { useTextareaModeContext } from "../context/TextareaMode/useTextareaModeContext"
import { SnowflakeId, checkIsAllDollarWrapped } from "../helpers"
import { Edit } from "../style"
import {
	CursorRef,
	EXPRESSION_ITEM,
	EXPRESSION_VALUE,
	EditRef,
	LabelTypeMap,
	type ChangeRef,
} from "../types"
import { transferSpecialSign } from "../utils"
import useEncryption from "./hooks/useEncryption"
import useExtraClassname from "./hooks/useExtraClassname"
import useSelectionChange from "./hooks/useSelectionChange"
import LabelCheckbox from "./nodes/LabelCheckbox/LabelCheckbox"
import LabelDatetime from "./nodes/LabelDatetime/LabelDatetime"
import LabelDepartmentNames from "./nodes/LabelDepartmentNames/LabelDepartmentNames"
import { LabelFunc } from "./nodes/LabelFunc/LabelFunc"
import LabelMember from "./nodes/LabelMember/LabelMember"
import LabelMultiple from "./nodes/LabelMultiple/LabelMultiple"
import LabelNames from "./nodes/LabelNames/LabelNames"
import { LabelNode } from "./nodes/LabelNode/LabelNode"
import { LabelPassword } from "./nodes/LabelPassword/LabelPassword"
import LabelSelect from "./nodes/LabelSelect/LabelSelect"
import { LabelTextBlock } from "./nodes/LabelText/LabelText"

const { LabelText } = LabelTypeMap

const getSpaceText = () => {
	return { type: LabelText, uniqueId: SnowflakeId(), value: "\u200B" } as EXPRESSION_ITEM
}
const LabelTypes = [
	LabelTypeMap.LabelNode,
	LabelTypeMap.LabelFunc,
	LabelTypeMap.LabelMember,
	LabelTypeMap.LabelMultiple,
	LabelTypeMap.LabelDateTime,
	LabelTypeMap.LabelSelect,
	LabelTypeMap.LabelCheckbox,
	LabelTypeMap.LabelDepartmentNames,
	LabelTypeMap.LabelNames,
]
const ALL_TYPE = [...Object.values(LabelTypeMap)]

export interface EditChangeRef {
	onLabelValueChange: (uniqueId: string, labelValue: EXPRESSION_ITEM) => void
	getDataSource: () => any
	setCurrentNod: (val: any) => void
	getCurrentNode: () => any
}

export interface KeeboardEditRef extends HTMLDivElement {
	onkeydown: (e: any) => void
	onkeyup: (e: any) => void
}

interface ContentEditableProps {
	changeRef: React.MutableRefObject<ChangeRef>
	bordered: boolean
	mode: ExpressionMode
	minHeight?: string
	setPosition: React.Dispatch<
		React.SetStateAction<{
			left: number
			top: number
		}>
	>
	inputRef: React.RefObject<HTMLDivElement>
}

const ContentEditable = (
	{ changeRef, bordered = true, mode, minHeight, setPosition, inputRef }: ContentEditableProps,
	ref: EditRef,
) => {
	const { disabled, showMultipleLine, zoom, selectPanelOpen, isInFlow } = useGlobalContext()
	const { openSelectPanel } = useTextareaModeContext()
	const [displayValue, setDisplayValue, resetDisplayValues] = useResetState<EXPRESSION_ITEM[]>([
		getSpaceText(),
	])
	const [editKey, setEditKey] = useState(new Date().valueOf())
	const [selectedNode, setSelectedNode] = useState("") // Current selected node data (excluding text nodes)

	const editRef = useRef({} as KeeboardEditRef)
	const cursorRef = useRef({} as CursorRef)
	const editChangeRef = useRef({} as EditChangeRef) // Provide cross-component communication container methods

	const isResetCursor = useRef(false) // Whether to reset cursor
	const isEntering = useRef(false) // Whether IME (pinyin) is active

	const isKeyDown = useRef(false) // Track key down for long-press input
	const isExistNoUpdate = useRef(false) // Track pending updates while key is held

	const newValue = useRef([] as EXPRESSION_VALUE[]) // Latest applied data inside the editable div
	const currentNodes = useRef([] as EXPRESSION_VALUE[]) // All node data inside the editable div

	const { extraClassname, makeCanScroll, banScroll } = useExtraClassname()

	const computedZoom = useMemo(() => {
		if (!isInFlow) return 1
		return zoom
	}, [isInFlow, zoom])

	const { handleFocusWhenEncryption } = useEncryption({
		clearDisplayValues: resetDisplayValues,
		displayValue,
		setDisplayValue,
	})

	const getItemType = useMemoizedFn((typeString = "") => {
		const types = Object.values(LabelTypeMap)
		return types.find((type) => typeString.includes(type))
	})

	const setCursor: EditRef["setCursor"] = useMemoizedFn(({ id, type, offset }) => {
		cursorRef.current = {
			id,
			type,
			offset,
		}

		if (id) {
			const index = displayValue.findIndex((item) => item.uniqueId === id)
			cursorRef.current.prevId = displayValue[index - 1]?.uniqueId
			cursorRef.current.nextId = displayValue[index + 1]?.uniqueId
		}
	})

	const checkIfScrollable = useMemoizedFn(() => {
		const scrollHeight =
			inputRef?.current?.getElementsByClassName("editable-container")[0]?.scrollHeight || 0
		const clientHeight =
			inputRef?.current?.getElementsByClassName("editable-container")[0]?.clientHeight || 0
		return scrollHeight > clientHeight
	})

	const setCurrentCursor = useMemoizedFn((e?: any) => {
		if (e) {
			// Enable scroll only when container is scrollable
			if (checkIfScrollable()) makeCanScroll()
		}
		handleFocusWhenEncryption()
		// Handle single node click
		const sel = window.getSelection && window.getSelection()
		const { id, type } = e?.target?.dataset || {}
		const itemType = getItemType(type || "") as LabelTypeMap

		const scrollTop =
			// @ts-ignore
			inputRef?.current?.getElementsByClassName("editable-container")[0]?.scrollTop || 0
		if ([LabelTypeMap.LabelNode, LabelTypeMap.LabelFunc].includes(itemType)) {
			const cascaderRect = editRef?.current?.getBoundingClientRect?.()
			const range = e.target?.getBoundingClientRect?.()

			setPosition({
				left: (range.left - cascaderRect.left + 10) / computedZoom,
				top: (range.top - cascaderRect.top) / computedZoom + 24 - scrollTop,
			})
			setSelectedNode(id)
			return
		}
		setSelectedNode("")

		if (sel && sel.rangeCount > 0) {
			const dom = sel?.getRangeAt?.(0)
			// @ts-ignore
			const { id, type } = dom?.endContainer?.parentNode?.dataset || {}
			const offset = dom?.endOffset
			setCursor({ id, type, offset })
		}
	})

	const cursorMoveToSpecifyPosition = useCallback(() => {
		if (!isResetCursor.current) return
		isResetCursor.current = false
		const { id, prevId, nextId } = cursorRef.current || {}
		let { offset } = cursorRef.current || {}
		const val = displayValue.find((item) => item.uniqueId === id)

		const selection = window.getSelection()

		let dom = null
		if (id) dom = document.getElementById(id)
		if (!dom && prevId) {
			const index = displayValue.findIndex((item) => item.uniqueId === prevId)
			if (index >= 0) {
				if (LabelTypes.includes(displayValue[index].type)) {
					dom = document.getElementById(displayValue[index + 1].uniqueId)
					offset = 0
				} else {
					dom = document.getElementById(displayValue[index].uniqueId)
					offset = -1
				}
			}
		}
		if (!dom && nextId) {
			const index = displayValue.findIndex((item) => item.uniqueId === nextId)
			if (index >= 0) {
				const itemType = getItemType(displayValue[index].type) as LabelTypeMap
				if (LabelTypes.includes(itemType)) {
					dom = document.getElementById(displayValue[index - 1].uniqueId)
					offset = -1
				} else {
					dom = document.getElementById(displayValue[index].uniqueId)
					offset = 0
				}
			}
		}

		// Fallback: move cursor to the end
		if (!dom) {
			const { uniqueId } = last(displayValue) || { uniqueId: "" }
			dom = document.getElementById(uniqueId)
			offset = -1
		}

		const rangeText = dom?.lastChild

		// @ts-ignore
		if (!rangeText || (val && val.value !== rangeText?.data)) return
		if (offset < 0) {
			// @ts-ignore
			const strLen = rangeText.data.length
			if (strLen + (offset + 1) > 0) offset = strLen + (offset + 1)
		}

		const range = document.createRange()
		try {
			range?.setStart?.(rangeText, offset)
			range?.setEnd?.(rangeText, offset)
			// @ts-ignore
			selection?.removeAllRanges?.()
			// @ts-ignore
			selection?.addRange?.(range)
		} catch (e) {}
		setCurrentCursor()
	}, [displayValue, getItemType, setCurrentCursor])

	useEffect(() => {
		cursorMoveToSpecifyPosition()
	}, [cursorMoveToSpecifyPosition])

	const updateDisplayValue: EditRef["updateDisplayValue"] = useMemoizedFn((val) => {
		setEditKey(new Date().valueOf())
		newValue.current = cloneDeep(val)
		const values = []
		// @ts-ignore
		val.forEach((item) => {
			item.uniqueId = item.uniqueId || SnowflakeId()
		})

		// @ts-ignore
		val.forEach((item, index) => {
			if (item.type.includes(LabelText)) {
				const itemType = getItemType(val[index - 1]?.type)
				if (LabelTypes.includes(itemType as LabelTypeMap))
					item.value = `\u200B${item.value}`
				values.push(item)
				return
			}

			if (index === 0 && !item.type.includes(LabelText)) {
				values.push(getSpaceText())
				values.push(item)
				if (val.length === 1) values.push(getSpaceText())
				return
			}

			if (!val[index - 1].type.includes(LabelText)) values.push(getSpaceText())
			else {
				const lastOneCharacter = val[index - 1].value?.slice(-1)
				if (lastOneCharacter === "\n") values.push(getSpaceText())
			}
			values.push(item)

			if (index === val.length - 1 && !item.type.includes(LabelText))
				values.push(getSpaceText())
		})
		if (!values.length) values.push(getSpaceText())

		currentNodes.current = values
		setDisplayValue(values)
	})

	const filterNullValue = useMemoizedFn((resultValue = []) => {
		return (
			resultValue
				// @ts-ignore
				.map((item) => {
					// @ts-ignore
					if (item && item.type.includes(LabelText))
						// @ts-ignore
						item.value = item.value.replaceAll(/\u200B/g, "")
					return item
				})
				// @ts-ignore
				.filter((item) => item)
				.filter(
					// @ts-ignore
					(item) =>
						// @ts-ignore
						![LabelText].includes(getItemType(item.type) as LabelTypeMap) ||
						// @ts-ignore
						item.value,
				)
				// @ts-ignore
				.reduce((res, item) => {
					// @ts-ignore
					if (item && item.type.includes(LabelText)) res.push(item)
					// @ts-ignore
					else res.push(displayValue.find((v) => v.uniqueId === item.uniqueId))
					return res
				}, [])
		)
	})

	useUpdateEffect(() => {
		if (selectedNode) {
			openSelectPanel()
		}
	}, [selectedNode])

	const handleChange = useMemoizedFn((evt: any) => {
		// In textarea mode, typing '/' opens the expression menu

		const { data } = evt.nativeEvent
		if (data === TextAreaModeTrigger) {
			const isPass = checkIsAllDollarWrapped(evt.target.innerText)
			if (isPass) {
				evt.stopPropagation()
				openSelectPanel()
			}
		}

		// // When user types '@', insert a new text block
		// if (data === "@") {
		// 	evt.stopPropagation()

		// 	// Insert a new text block
		// 	const newTextBlock = {
		// 		type: LabelText, // Assume LabelText is the text type
		// 		uniqueId: SnowflakeId(),
		// 		value: "@", // Initialize the new text block with @
		// 	}

		// 	// Insert the new text block into current resultValue
		// 	currentNodes.current.push(newTextBlock)
		// 	changeRef.current.handleChange([...currentNodes.current])

		// 	// Reset cursor after inserting new block
		// 	isResetCursor.current = true
		// 	setCurrentCursor()

		// 	// Prevent original text update
		// 	return
		// }

		// IME (Chinese) composition starts
		if (evt.type === "compositionstart") {
			isEntering.current = true
			changeRef.current.hiddenPlaceholder()
			return
		}

		// IME composition ends
		if (evt.type === "compositionend") {
			isEntering.current = false
		}

		// Skip further handling while IME composition is active
		if (isEntering.current) return

		// Defer updates while key is held down
		if (isKeyDown.current) {
			isExistNoUpdate.current = true
			return
		}
		const dom = editRef.current
		// Get cursor position
		const selection = window.getSelection()
		const scrollTop =
			// @ts-ignore
			inputRef?.current?.getElementsByClassName("editable-container")[0]?.scrollTop || 0

		if (selection?.rangeCount && selection?.rangeCount > 0) {
			const cascaderRect = dom.getBoundingClientRect()
			const range = selection.getRangeAt(0).getBoundingClientRect()

			setPosition({
				left: (range.left - cascaderRect.left + 10) / computedZoom,
				top: (range.top - cascaderRect.top) / computedZoom + 20 - scrollTop,
			})
		}
		// @ts-ignore
		let { childNodes } = dom
		// @ts-ignore
		childNodes = Array.from(childNodes)
		// @ts-ignore
		let resultValue = childNodes.map((childNode) => {
			if (childNode.nodeName === "#text") {
				isResetCursor.current = true
				return {
					type: LabelText,
					uniqueId: SnowflakeId(),
					value: childNode.data,
				}
			}
			const currentType = getItemType(childNode?.dataset?.type) as LabelTypeMap
			if (!ALL_TYPE.includes(currentType)) {
				isResetCursor.current = true
				return ""
			}

			if ([LabelText].includes(currentType)) {
				if (childNode?.childNodes?.length > 1) {
					cursorRef.current = {
						...cursorRef.current,
						offset: -2,
					}
					isResetCursor.current = true
				}
				return {
					type: LabelText,
					uniqueId: childNode.dataset.id,
					value: childNode.innerText,
				}
			}
			return {
				type: currentType,
				uniqueId: childNode.dataset.id,
			}
		})

		currentNodes.current = cloneDeep(resultValue)

		resultValue = filterNullValue(resultValue)

		const lastNode = last(currentNodes.current)
		if (!lastNode || !lastNode.type.includes(LabelText)) isResetCursor.current = true

		changeRef.current.handleChange(resultValue)
		newValue.current = resultValue

		if (isResetCursor.current) {
			updateDisplayValue(resultValue)
		} else {
			setCurrentCursor()
		}
		isExistNoUpdate.current = false
		evt.stopPropagation()
	})

	const getCursor = useMemoizedFn(() => {
		const { id, type, offset } = cursorRef.current || {}
		if (!type || !type.includes(LabelText) || offset <= 0 || !id) return cursorRef.current
		const v = document.getElementById(id)?.innerText || ""
		if (!v.substr(0, offset).includes("\u200B")) return cursorRef.current
		return {
			id,
			type,
			offset: offset - 1,
		}
	})

	// Pick render component based on node type
	const renderLabel = useMemoizedFn((type: LabelTypeMap) => {
		const labelMap = {
			[LabelTypeMap.LabelFunc]: LabelFunc,
			[LabelTypeMap.LabelNode]: LabelNode,
			[LabelTypeMap.LabelText]: LabelTextBlock,
			[LabelTypeMap.LabelMember]: LabelMember,
			[LabelTypeMap.LabelDateTime]: LabelDatetime,
			[LabelTypeMap.LabelSelect]: LabelSelect,
			[LabelTypeMap.LabelMultiple]: LabelMultiple,
			[LabelTypeMap.LabelCheckbox]: LabelCheckbox,
			[LabelTypeMap.LabelPassword]: LabelPassword,
			[LabelTypeMap.LabelDepartmentNames]: LabelDepartmentNames,
			[LabelTypeMap.LabelNames]: LabelNames,
		}
		const itemType = getItemType(type) as LabelTypeMap
		if (!labelMap[itemType]) return null
		return labelMap[itemType]
	})

	// Delete the current item
	const handleDelete = useMemoizedFn((val: EXPRESSION_ITEM) => {
		const resultData = cloneDeep(newValue.current)
		const index = resultData.findIndex((item) => item.uniqueId === val.uniqueId)
		if (index > -1) resultData.splice(index, 1)
		changeRef.current.handleChange(resultData)
		updateDisplayValue(resultData)
	})

	// Update the current item
	const handleUpdate = useMemoizedFn((val: EXPRESSION_ITEM) => {
		const resultData = cloneDeep(newValue.current)
		const index = resultData.findIndex((item) => item.uniqueId === val.uniqueId)
		if (index > -1) resultData.splice(index, 1, val)
		changeRef.current.handleChange(resultData)
		updateDisplayValue(resultData)
	})

	const handleBackspace = useMemoizedFn((e: any) => {
		// If a node is selected, delete it
		if (selectedNode) {
			handleDelete({ uniqueId: selectedNode } as EXPRESSION_ITEM)
			return true
		}

		// @ts-ignore
		const dom = document.getSelection().getRangeAt(0)
		if (!dom.collapsed) return true // Skip when a selection exists
		// @ts-ignore
		const { id } = dom?.endContainer?.parentNode?.dataset || {}
		if (!id) return true

		const index = currentNodes?.current?.findIndex((item) => item.uniqueId === id)
		if (index < 0) return true
		const node = currentNodes?.current[index]

		const offset = dom.endOffset
		const str = node?.value || ""

		// Only handle deleting the leading zero-width space (endOffset excludes newlines)
		if (offset !== 1 || str[0] !== "\u200B") return true

		const secondNode = (currentNodes.current || [])[index - 1]
		const currentType = getItemType((secondNode || {}).type) as LabelTypeMap
		if (!secondNode || !LabelTypes.includes(currentType)) return true // Prior node must be a label node

		if (isKeyDown.current) return true

		setCurrentCursor()
		cursorRef.current.offset = 0
		isResetCursor.current = true
		handleDelete(secondNode)
		return false
	})

	// Handle keyboard shortcuts
	const handleKeyboardShortcuts = useMemoizedFn((e: React.KeyboardEvent) => {
		// Undo/redo shortcuts (Ctrl/Cmd+Z, Ctrl+Y, Cmd+Shift+Z)
		if (
			(e.ctrlKey || e.metaKey) &&
			(e.key === "z" || e.key === "Z" || e.key === "y" || e.key === "Y")
		) {
			// Let the event bubble; return false to skip further handling here
			return false
		}

		// Save shortcut (Ctrl/Cmd+S)
		if ((e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S")) {
			e.preventDefault()
			return false
		}

		return true
	})

	useEffect(() => {
		editRef.current.onkeydown = (e) => {
			// Handle shortcuts first
			if (!handleKeyboardShortcuts(e)) {
				// Let undo/redo bubble
				if (
					(e.ctrlKey || e.metaKey) &&
					(e.key === "z" || e.key === "Z" || e.key === "y" || e.key === "Y")
				) {
					return true // Allow event to continue
				}
				return false
			}

			if (e.keyCode === KeyCodeMap.BACKSPACE) {
				return handleBackspace(e)
			}
			if ([KeyCodeMap.ENTER].includes(e.keyCode)) {
				const { id, offset } = cursorRef.current
				const lastNode = last(currentNodes.current)
				const isEnd = lastNode?.uniqueId === id && lastNode?.value?.length === offset // Cursor at end?
				const endIsLineBreak = endsWith(lastNode?.value || "", "\n") // Is last char a newline?
				const isAddMultiline = isEnd && !endIsLineBreak
				// if (isAddMultiline) {
				// 	cursorRef.current = {
				// 		...cursorRef.current,
				// 		offset: -2,
				// 	}
				// 	isResetCursor.current = true
				// }
				document.execCommand("insertHtml", true, isAddMultiline ? "\n\n" : "\n")
				return false
			}

			return true
		}
		editRef.current.onkeyup = (e) => {
			if ([KeyCodeMap.RIGHT, KeyCodeMap.LEFT].includes(e.keyCode)) setCurrentCursor()
		}
	})

	const onLabelValueChange = useMemoizedFn((uniqueId: string, labelValue: string) => {
		if (!uniqueId || !labelValue) return
		const index = displayValue.findIndex((item) => item.uniqueId === uniqueId)
		if (index > 0) {
			let copyValue = [...displayValue]
			// @ts-ignore
			copyValue.splice(index, 1, labelValue)
			// @ts-ignore
			copyValue = filterNullValue(copyValue)
			updateDisplayValue(copyValue)
			changeRef.current.handleChange(copyValue)
		}
	})

	const getDisplayValue = useMemoizedFn(() => {
		return displayValue
	})

	const getCurrentNode = useMemoizedFn(() => {
		return selectedNode
	})

	const setCurrentNode = useMemoizedFn((uniqueId: string) => {
		return setSelectedNode(uniqueId)
	})

	useEffect(() => {
		Object.assign(editChangeRef.current, {
			onLabelValueChange,
			getDataSource: changeRef?.current?.getDataSource,
			setCurrentNode,
			getCurrentNode,
		})
	}, [onLabelValueChange, changeRef, setCurrentNode, getCurrentNode])

	useImperativeHandle(
		// @ts-ignore
		ref,
		() => ({
			updateDisplayValue,
			getDisplayValue,
			getCursor,
			setCursor,
			setCurrentNode,
			getCurrentNode,
		}),
		[updateDisplayValue, getDisplayValue, getCursor, setCursor, setCurrentNode, getCurrentNode],
	)

	const [wrapperWidth, setWrapperWidth] = useState(0)

	useMount(() => {
		setWrapperWidth(editRef.current.clientWidth)
	})

	useSelectionChange(editRef)

	return (
		<Edit
			className={clsx("nodrag", extraClassname)}
			bordered={bordered}
			showMultipleLine={showMultipleLine}
			onInput={handleChange}
			onCompositionStart={handleChange}
			onCompositionEnd={handleChange}
			suppressContentEditableWarning
			mode={mode}
			minHeight={minHeight}
			// @ts-ignore
			ref={editRef}
			onClick={setCurrentCursor}
			key={editKey}
			contentEditable={!disabled}
			onKeyDown={(evt) => {
				evt.stopPropagation()
				isKeyDown.current = true
			}}
			onKeyUp={(e) => {
				// onkeyup fires after oninput
				e.stopPropagation()
				isKeyDown.current = false
				if (isExistNoUpdate.current) handleChange(e)
			}}
			onPaste={(e) => {
				e.nativeEvent.stopImmediatePropagation()
				e.stopPropagation()
				// Read plain text from clipboard
				let clipboardText = e.clipboardData.getData("text/plain")

				// Escape < and > to avoid HTML parsing
				clipboardText = transferSpecialSign(clipboardText)
				document.execCommand("insertHtml", true, clipboardText)
				e.preventDefault()
			}}
			onBlur={(e) => {
				setTimeout(() => {
					if (!selectPanelOpen) {
						setSelectedNode("")
					}
				}, 500)
				banScroll()
			}}
		>
			{displayValue.map((item) => {
				const Component = renderLabel(item.type)
				// console.error(item);
				return (
					// @ts-ignore
					<Component
						key={item.uniqueId}
						config={item}
						// @ts-ignore
						editChangeRef={editChangeRef}
						selected={selectedNode === item.uniqueId}
						deleteFn={handleDelete}
						wrapperWidth={wrapperWidth}
						updateFn={handleUpdate}
					/>
				)
			})}
		</Edit>
	)
}
// @ts-ignore
export default memo(forwardRef(ContentEditable))


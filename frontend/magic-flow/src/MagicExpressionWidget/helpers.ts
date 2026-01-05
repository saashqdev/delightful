// @ts-ignore
import Snowflake from "snowflake-id"
import { TextAreaModeTrigger } from "./constant"
import { DataSourceOption } from "@/common/BaseUI/DropdownRenderer/Reference"
import { Splitor } from "@/common/BaseUI/DropdownRenderer/Reference/constants"
import { getReferencePrefix } from "@/MagicFlow/utils"
import { CursorRef, EXPRESSION_VALUE, InputExpressionValue, LabelTypeMap, MethodOption } from "./types"
import _ from "lodash"

const snowflake = new Snowflake({
	mid: 1024,
	offset: (2020 - 1970) * 31536000 * 1000,
})

/**
 * @description Generate a unique id
 * @returns {string} unique id
 */
export const SnowflakeId = () => {
	return snowflake.generate()
}

/** Find the root node */
export const findRootNodeByValue = (treeData: Record<string, any>[], targetValue: string) => {
    // Traverse treeData entries
	for (let i = 0; i < treeData.length; i += 1) {
		const currentNode = treeData[i]

        // Return current node if its value matches the target
		if (currentNode.value === targetValue) {
			return currentNode
		}

        // If the current node has children, recurse into them
		if (currentNode.children) {
			const result = findRootNodeByValue(currentNode.children, targetValue)
			if (result) {
                return currentNode // If a child contains the target, return the current node
			}
		}
	}

    // If the target is not found, return null
	return null
}

/** Find both the root node and the target node */
export const findRootNodeAndValueByValue = (
	treeData: Record<string, any>[],
	targetValue: string,
) => {
	const findNode = (
		data: Record<string, any>[],
		target: string,
		parent: Record<string, any> | null,
	): { root: Record<string, any>; value: Record<string, any> } | null => {
		for (let i = 0; i < data.length; i += 1) {
			const currentNode = data[i]
			if (currentNode.value === target) {
				return {
					root: parent || currentNode,
					value: currentNode,
				}
			}
			if (currentNode.children) {
				const result = findNode(currentNode.children, target, currentNode)
				if (result) {
					return result
				}
			}
		}
		return null
	}

	return findNode(treeData, targetValue, null)
}

/** Get the last segment from a dotted path, e.g., aaa.bbb.ccc -> ccc */
export const getLastName = (str: string) => {
	const paths = str.split(".")
	if (paths.length === 0) return str
	return paths[paths.length - 1]
}



export const checkIsAllDollarWrapped = (str: string) => {
    let pass = false

    const isCharCheck = (checkStr: string, index: number) => {
        const beforeChar = checkStr[index-1]
        const nextChar = checkStr[index+1]
        const isBeforeCharChecked = beforeChar === "'" || beforeChar === '"' || beforeChar === TextAreaModeTrigger
        const isNextCharChecked = nextChar === "'" || nextChar === '"' || nextChar === TextAreaModeTrigger
        return isBeforeCharChecked && isNextCharChecked
    }
    
    for(let i = 0; i < str.length; i++) {
        const char = str[i]
        if(char === TextAreaModeTrigger) {
            if(i === 0 || i === str.length - 1) return true
            if(!isCharCheck(str, i)) return true
        }
    }

    return pass
}

// Build a map of all nodes keyed by their reference key
export const getDataSourceMap = (data: DataSourceOption[]) => {
    let map = {} as Record<string, DataSourceOption>
    data.forEach((item) => {
		const prefix = getReferencePrefix(item)
		if(prefix) {
			// Handle the case where the node itself is selected
            if(prefix === item.key) {
                map[prefix] = item
            }else{
                map[`${prefix}${Splitor}${item.key}`] = item;

            }
		}else {
			map[`${item.key}`] = item;
		}
        if (item.children) {

            map = {
                ...map,
                ...getDataSourceMap(item.children)
            }
        }
    });
    return map;
};

/** Get the first expression item, typically the inner value for the expression radio control */
export const getExpressionFirstItem = (value: InputExpressionValue) => {
	return value?.expression_value?.[0]
}

export const transformDataSource = (dataSource: MethodOption[]): DataSourceOption[] => {
	return dataSource.map(item => {
		return {
			title: item.label,
			key: item.value,
			nodeId: "",
			nodeType: "",
			children: item.children ? transformDataSource(item.children) : [] as DataSourceOption[],
			arg: item.arg,
			type: undefined,
			isRoot: false,
			isConstant: false,
			rawSchema: undefined,
			renderType: undefined,
			isGlobal: false,
			desc: item.desc,
			isMethod: (item.children || []).length > 0 ? false : true,
            return_type: item.return_type,
            selectable: (item.children || []).length > 0 ? false : true
		}
	}) as DataSourceOption[]
}

const findAllIndices = (str: string, char: string): number[] => {
    str = str.trim()
    const regex = new RegExp(char, 'g');
    const indices: number[] = [];
    let match;
    while ((match = regex.exec(str)) !== null) {
        indices.push(match.index);
    }
    return indices;
};

// Find the expression block containing the @ trigger and return its offset and uniqueId
export const findTargetTriggerField = (expressionField: EXPRESSION_VALUE[], cursor: CursorRef) => {
    const { offset, id } = cursor
    // Locate all expression blocks containing the trigger character
    const allHasTriggerFields = _.cloneDeep(expressionField.filter((field) =>
        field.value.includes(TextAreaModeTrigger),
    ))

    // Replace newlines to avoid offset miscalculations
    const replacedFields = allHasTriggerFields.map(field => ({
        ...field,
        value: field.value.replace(/\n/g, '$')
    }))


    // All trigger positions
    const allTriggerIndices = replacedFields.reduce((result, field) => {
        // console.log("field.value",field.value,field.value.length)
        const indicesArr = findAllIndices(field.value, TextAreaModeTrigger)
        // console.log("indicesArr",indicesArr)
        const indicesFields = indicesArr.map((indices) => ({
            uniqueId: field.uniqueId,
            offset: indices,
        }))
        result.push(...indicesFields)
        return result
    }, [] as Array<{
        offset: number;
        uniqueId: string;
    }>)

    // console.log("allTriggerIndices",allTriggerIndices,allHasTriggerFields,offset,id)

    // Show the list as long as any trigger is before the current cursor
    // TODO: investigate why the cursor offset is sometimes trigger+2
    const targetTriggerField = allTriggerIndices?.find?.(indicesObject => {
        const isSameOffset = indicesObject.offset === offset - 1 || indicesObject.offset === offset - 2
        const isSameField = indicesObject.uniqueId === id
        return isSameOffset && isSameField
    })
    
    return targetTriggerField
}

// Check whether the input value is a reference node
export const checkIsReferenceNode = (input: any) => {
    return input?.type === LabelTypeMap.LabelFunc || input?.type === LabelTypeMap.LabelNode
}
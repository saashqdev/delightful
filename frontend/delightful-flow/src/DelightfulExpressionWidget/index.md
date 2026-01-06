# Expression Component v2

## Basic example

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "./components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        // console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap}/>
}
```

## Supports function data sources

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "./components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        // console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap}/>
}
```

## Supports textarea mode

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "./components/dataSource"
import methodExpressionSource from "./mock/expressionSource"
import { ExpressionMode } from "./constant"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} mode={ExpressionMode.TextArea} pointedValueType="expression_value" nodeMap={mockNodeMap} methodsDataSource={methodExpressionSource} showExpand/>
}
```

## Supports editing field content

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource } from "./components/dataSource"
import { ExpressionMode } from "./constant"


export default () => {
    const [expression1, setExpression1] = useState({
        "type": "expression",
        "const_value": [],
        "expression_value": [
            {
                "type": "fields",
                "value": "token_response.body",
                "name": "token response body",
                "args": []
            },
            {
                "type": "input",
                "value": "['code']",
                "name": "",
                "args": []
            }
        ]
    })
    const [expression2, setExpression2] = useState(null)

    const onExpression1Change = useCallback((val) => {
        console.log('value1:', val)
        setExpression1(val)
    }, [])

    
    const onExpression2Change = useCallback((val) => {
        console.log('value2:', val)
        setExpression2(val)
    }, [])

    return <>
                <DelightfulExpressionWidget allowModifyField value={expression1} onChange={onExpression1Change} dataSource={mockDataSource} mode={ExpressionMode.Common} />
                <br/>
                <DelightfulExpressionWidget allowModifyField value={expression2} onChange={onExpression2Change} dataSource={mockDataSource} mode={ExpressionMode.TextArea} pointedValueType="expression_value"/>
            </>
}
```

## Disabled

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource } from "./components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} disabled/>
}
```

## Constant data source

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource } from "./components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

	const constantSource = [{
		"title": "User",
		"key": "user",
		"nodeId": "",
		"nodeType": "21",
		"type": "string",
		"isRoot": false,
		"children": [],
		"isConstant": true
	},{
		"title": "System",
		"key": "system",
		"nodeId": "",
		"nodeType": "21",
		"type": "string",
		"isRoot": false,
		"children": [],
		"isConstant": true
	}]

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} constantDataSource={constantSource} multiple={false} dataSource={mockDataSource}/>
}
```

## Supports editing in a modal

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "./components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        // console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap} allowOpenModal showMultipleLine={false} onlyExpression disabled />
}
```

## Adapts to different fields in a multidimensional table

```jsx
import { DelightfulExpressionWidget } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "./components/dataSource"
import { mockMultipleList } from "@/DelightfulExpressionWidget/components/nodes/LabelMultiple/mock"
import DepartmentModal from "./mock/DepartmentModal"


export default () => {
    const [expression, setExpression] = useState(null)

    const [multiple, setMultiple] = useState(null)

    const filterMemberList = []

    const [select, setSelect] = useState(null)

    const [datetime, setDatetime] = useState(null)

    const [checkbox, setCheckbox] = useState(null)
	
    const [departmentNames, setDepartmentNames] = useState(null)

    const [names, setNames] = useState(null)

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
        setExpression(val)
    }, [])

    const onMultipleChange = useCallback((val) => {
        console.log('value:', val)
        setMultiple(val)
    }, []) 
	
    const onDatetimeChange = useCallback((val) => {
        console.log('value:', val)
        setDatetime(val)
    }, []) 

	const onCheckboxChange = useCallback((val) => {
        console.log('value:', val)
        setCheckbox(val)
    }, []) 

	
	const onSelectChange = useCallback((val) => {
        console.log('value:', val)
        setSelect(val)
    }, []) 

	
	const onDepartmentNamesChange = useCallback((val) => {
        console.log('value:', val)
        setDepartmentNames(val)
    }, []) 

	
	const onNamesChange = useCallback((val) => {
        console.log('value:', val)
        setNames(val)
    }, []) 

	const handleOk = useCallback(() => {
		console.log("ok")
    }, []) 


    return <>
        <strong>Member</strong>
		<DelightfulExpressionWidget value={expression} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'member',
			props: {
				options: [],
				value: [],
				onChange: () => {},
				searchType: 'member',
				onSearch: async () => {
					const options = await Promise.resolve(filterMemberList)
					return options
				}
			}
		}}/>


        <strong>Single select</strong>
		<DelightfulExpressionWidget value={select} onChange={onSelectChange} dataSource={mockDataSource} nodeMap={mockNodeMap} multiple={false} renderConfig={{
			type: 'select',
			props: {
				options: mockMultipleList,
				value: [],
				onChange: () => {}
			}
		}}/>

        <strong>Multi select</strong>
		<DelightfulExpressionWidget value={multiple} onChange={onMultipleChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'multiple',
			props: {
				options: mockMultipleList,
				value: [],
				onChange: () => {}
			}
		}}/>
		
        <strong>Date</strong>
		<DelightfulExpressionWidget value={datetime} multiple={false} onChange={onDatetimeChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'datetime',
			props: {
				value: [],
				onChange: () => {}
			}
		}}/>

		
		<strong>Checkbox</strong>
		<DelightfulExpressionWidget value={checkbox} multiple={false} onChange={onCheckboxChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'checkbox',
			props: {
				value: null,
				onChange: () => {}
			}
		}}/>

        <strong>Department</strong>
		<DelightfulExpressionWidget value={departmentNames} multiple={false} onChange={onDepartmentNamesChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'department_names',
			props: {
				editComponent: DepartmentModal
			}
		}}/>


        <strong>Generic text block</strong>
		<DelightfulExpressionWidget value={names} multiple={true} onChange={onNamesChange} dataSource={mockDataSource} nodeMap={mockNodeMap} renderConfig={{
			type: 'names',
			props: {
				value: null,
				onChange: () => {},
				editComponent: DepartmentModal,
				options: [{
                    id:"xxx",
                    label: "Test knowledge base"
                },{
                    id:"yyy",
                    label: "Test knowledge base 2"
                }],
                suffix: (item) => {
                    return <div onClick={() => {
                        console.log("item", item)
                    }}>111</div>
                }
			}
		}}/>
	</>
}
```


## Expression data source structure

See the expression examples for context.

| Parameter   | Description                                                            | Type   | Required |
| ----------- | ---------------------------------------------------------------------- | ------ | -------- |
| label       | Label                                                                 | string | Yes      |
| value       | Selected value                                                         | string | Yes      |
| return_type | Return type for function blocks (only present when the option is a fn) | string | -        |
| args        | Function block arguments (array of argument blocks)                    | array  | -        |
| desc        | Function block description                                             | string | -        |
| children    | Function block child options                                           | array  | -        |

## API

| Parameter         | Description                                 | Type                          | Default                 |
| ----------------- | ------------------------------------------- | ----------------------------- | ----------------------- |
| dataSource        | Expression data source                       | DataSourceItem[] (see above)  | -                       |
| placeholder       | Placeholder text                             | string                        | -                       |
| mode              | Expression mode                              | ExpressionMode                | ExpressionMode.Common    |
| value             | Expression value                             | InputExpressionValue          | -                       |
| onChange          | Expression change handler                    | (value: InputExpressionValue) => void | () => {}          |
| allowExpression   | Whether expressions are allowed              | boolean                       | false                   |
| pointedValueType  | Target value type for expression input       | 'const' or 'expression'       | -                       |
| allowModifyField  | Whether field values can be edited           | boolean                       | false                   |
| disabled          | Whether the widget is disabled               | boolean                       | false                   |
| multiple          | Whether multiple selection is enabled        | boolean                       | true                    |




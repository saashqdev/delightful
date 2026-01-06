# Condition Component v2

## Basic example

```jsx
import { DelightfulConditionEdit } from '@/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "@/DelightfulExpressionWidget/components/dataSource"


export default () => {
    const [expression, setExpression] = useState(null)

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
        setExpression(val)
    }, [])

    return <DelightfulConditionEdit value={expression} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap}/>
}
```


## DelightfulConditionEditWrap

```jsx
import DelightfulConditionEditWrap from '@/common/BaseUI/DelightfulConditionWrap/index';
import React,{ useState, useCallback } from "react"
import { mockDataSource, mockNodeMap } from "@/DelightfulExpressionWidget/components/dataSource"


export default () => {
    const [expression, setExpression] = useState({
		id:"xxx",
		structure: undefined
	})

    const onExpressionChange = useCallback((val) => {
        console.log('value:', val)
    }, [])

    return <DelightfulConditionEditWrap value={expression.structure} onChange={onExpressionChange} dataSource={mockDataSource} nodeMap={mockNodeMap}/>
}
```







# Form Component v2

```shell
yarn add @feb/json-schema-editor
```

## Basic Usage

```jsx
import JsonSchemaEditor from './index';
import { useState } from "react";
import { mockDataSource, mockNodeMap } from "@/MagicExpressionWidget/components/dataSource"

export default () => {

    
    const [data, setData] = useState({
    "title": "",
    "description": "",
    "value": null,
    "type": "object",
    "properties": {
        "field_1": {
            "type": "object",
            "properties": {
                "field_23": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": null
                },
                "field_31": {
                    "type": "object",
                    "properties": {
                        "field_32": {
                            "type": "string",
                            "title": "",
                            "description": "",
                            "value": null
                        },
                        "field_33": {
                            "type": "array",
                            "title": "",
                            "description": "",
                            "value": null,
                            "items": {
                                "type": "string"
                            }
                        },
                        
                        "field_34": {
                            "type": "array",
                            "title": "",
                            "description": "",
                            "value": null,
                            "items": {
                                "type": "object"
                            },
                            "properties": {}
                        }
                    },
                    "required": [
                        "field_32",
                        "field_33"
                    ],
                    "title": "",
                    "description": "",
                    "value": null
                },
                "field_29": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": null
                }
            },
            "required": [
                "field_23",
                "field_29",
                "field_31"
            ],
            "title": "",
            "description": "",
            "value": null
        }
    },
    "required": [
        "field_1"
    ]
})


    const handleOnChange = value => {
        console.log("schema change", value)
        setData(value)
    }

    return <JsonSchemaEditor data={data} onChange={handleOnChange} allowExpression expressionSource={mockDataSource} nodeMap={mockNodeMap}/>
}
```

## Enable Expressions

By default expressions are disabled. To enable them, set `allowExpression` to `true` and provide an expression data source via `expressionSource`.

```jsx
import JsonSchemaEditor from './index';

import { mockDataSource, mockNodeMap } from "@/MagicExpressionWidget/components/dataSource"


const handleOnChange = value => {
    // console.log("schema change", value)
}


export default () => (
  <JsonSchemaEditor allowExpression expressionSource={mockDataSource} debuggerMode onChange={handleOnChange} nodeMap={mockNodeMap}/>
);
```

<!-- ## Allow Non-JSON Root Types

The root node supports only `array` and `object` by default. Set `onlyJson` to `false` to allow other root types.

```jsx
import JsonSchemaEditor from './index';

const handleOnChange = value => {
    // console.log("schema change", value)
}


export default () => <JsonSchemaEditor onlyJson={false} onChange={handleOnChange}/>;
``` -->

## Disable Manual Add/Delete

Add/Delete is enabled by default. Set `allowOperation` to `false` to prevent manual operations and rely on data linkage only.

```jsx
import JsonSchemaEditor from './index';

const handleOnChange = value => {
    // console.log("schema change", value)
}


export default () => <JsonSchemaEditor allowOperation={false} onChange={handleOnChange}/>;
```

## Customize Allowed Types

Currently the allowed types are `object`, `array`, `string`, `number`, and `boolean`; other types are filtered out. When both `onlyJson` and `customOptions` are set, `customOptions` takes precedence.

```jsx
import JsonSchemaEditor from './index';

const customOptions = {
  items: ['string', 'number'],
  root: ['object', 1, 'string', {}],
  normal: ['string', 'number', 'array'],
};

const handleOnChange = value => {
    // console.log("schema change", value)
}

export default () => <JsonSchemaEditor customOptions={customOptions} onChange={handleOnChange}/>;
```

<!-- ## Enable JSON Import

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor jsonImport />;
``` -->

## Skip Auto-Creating First Child

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor oneChildAtLeast={false} />;
```

## Customize First Child Key

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor firstChildKey="organization" />;
```

## Add Nodes via API

```jsx
import JsonSchemaEditor from './index';
import React from "react";
import { Button } from "antd";
import { nanoid } from "nanoid"
import _ from "lodash";

const refInstance = React.createRef(null)

const onAdd = () => {
    if(refInstance && refInstance.current) {
        const keys = new Array(1).fill(0).map(o => _.uniqueId("field_"))
        keys.forEach(key => {
            refInstance.current.addRootChildField(key)
        })
    }
}

const onDel = () => {
    if(refInstance && refInstance.current) {
        refInstance.current.deleteRootChildField('field_0')
    }
}

const onDelExcludeFirst = () => {
    if(refInstance && refInstance.current) {
        refInstance.current.deleteRootChildFieldsNotIn(['field_0'])
    }
}

export default () => <>
    <Button onClick={onAdd} type="primary">Add</Button>
    <Button onClick={onDel} type="primary" style={{marginLeft: '10px'}}>Delete field_0</Button>
    <Button onClick={onDelExcludeFirst} type="primary" style={{marginLeft: '10px'}}>Delete all except field_0</Button>
    <JsonSchemaEditor ref={refInstance} allowOperation={false}/>
</>;
```

## Disable Editing Parameter Key

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor disableFields={['key']} />;
```

## Hide Value Column

```jsx
import JsonSchemaEditor from './index';
import { ShowColumns } from "./constants";

export default () => <JsonSchemaEditor displayColumns={[ShowColumns.Key, ShowColumns.Label, ShowColumns.Type, ShowColumns.Description]} />;
```

## Hide Type and Label Columns

```jsx
import JsonSchemaEditor from './index';
import { ShowColumns } from "./constants";

export default () => <JsonSchemaEditor displayColumns={[ShowColumns.Key, ShowColumns.Value]} allowExpression/>;
```

## Customize Column Labels

```jsx
import JsonSchemaEditor from './index';
import { ShowColumns } from "./constants";

export default () => <JsonSchemaEditor columnNames={{
    [ShowColumns.Label]: "Variable Name",
    [ShowColumns.Key]: "Variable Key"
}}/>;
```

## Disallow Adding Parameters

```jsx
import JsonSchemaEditor from './index';
import { ShowColumns } from "./constants";

export default () => <JsonSchemaEditor showAdd={false} showOperation={false}/>;
```

## Special Field Handling

```jsx
import JsonSchemaEditor from './index';
import { useState } from "react";
import { mockDataSource, mockNodeMap } from "@/MagicExpressionWidget/components/dataSource"
import { ShowColumns } from "./constants";
import { DisabledField } from "./types/Schema"

export default () => {

    
    const [data, setData] = useState({
		"type": "array",
		"key": "root",
		"sort": 0,
        "title": "Context Memory",
		"description": "",
		"required": null,
		"value": null,
		"items": {
			"type": "object",
			"key": "messages",
			"sort": 0,
            "title": "Message History",
			"description": "",
			"required": [
				"role",
				"content"
			],
			"value": null,
			"items": null,
			"properties": {
				"role": {
					"type": "string",
					"key": "role",
					"sort": 0,
                    "title": "Role",
					"description": "",
					"required": null,
					"value": null,
					"items": null,
					"properties": null
				},
				"content": {
					"type": "string",
					"key": "content",
					"sort": 1,
                    "title": "Content",
					"description": "",
					"required": null,
					"value": null,
					"items": null,
					"properties": null
				}
			}
		},
		"properties": null
	})

	const mockConstantExpressionOptions = [{
        "title": "Constants",
		"key": "",
		"nodeId": "Wrapper",
		"nodeType": "21",
		"type": "",
		"isRoot": true,
		"children": [
			{
                "title": "User",
				"key": "user",
				"nodeId": "",
				"nodeType": "21",
				"type": "string",
				"isRoot": false,
				"children": [],
				"isConstant": true
			},
			{
                "title": "System",
				"key": "system",
				"nodeId": "",
				"nodeType": "21",
				"type": "string",
				"isRoot": false,
				"children": [],
				"isConstant": true
			}
		]
	}]


    const handleOnChange = value => {
        // console.log("schema change", value)
        setData(value)
    }

    return <JsonSchemaEditor data={data} onChange={handleOnChange} allowExpression expressionSource={mockDataSource} nodeMap={mockNodeMap}  displayColumns={[ShowColumns.Key, ShowColumns.Label, ShowColumns.Type, ShowColumns.Value]} showImport={false} disableFields={[DisabledField.Title, DisabledField.Type]} allowAdd={false} onlyExpression customFieldsConfig={{
		role: {
			allowOperation: false,
			constantsDataSource: mockConstantExpressionOptions,
			onlyExpression: false
		},
		content: {
			allowOperation: false,
		},
		root: {
			allowOperation: true,
			allowAdd: true
		}
	}} showTopRow />
}
```

## Enable Encryption

To hide field values, enable encryption. The backend must encrypt and fill `encryption_value`; `value` remains null. If `value` is modified, the backend will re-encrypt it.

```jsx
import JsonSchemaEditor from './index';
import { useState } from "react";
import { mockDataSource, mockNodeMap } from "@/MagicExpressionWidget/components/dataSource"
import { ShowColumns } from "./constants";

export default () => {

    
    const [data, setData] = useState({
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "",
    "description": "",
    "required": [
        "field_1",
        "asdasd",
        "field_3",
        "field_4"
    ],
    "value": null,
    "encryption": false,
    "encryption_value": null,
    "items": null,
    "properties": {
        "field_1": {
            "type": "string",
            "key": "field_1",
            "sort": 0,
            "title": "",
            "description": "",
            "required": null,
            "value": null,
            "encryption": true,
            "encryption_value": "K41OmxJPvR4JUmijEaWdv9EmezfQfLHTem2cL0VN4hU6a8ahv9g6zPeUGpYT9nrcLQVyHERJyEd6qAMN55q3E8fsT6QWvgbAiC4EfaVy+XVdaISddg3GOARRWhvr0BXQN2aI9IToI9IDcFd4dkIanxBeob9nFnKKrv1gyMiNt4cC1Aj55r0k4wY0xC2y6tmXnjzTlF3WZn7JJgI4S+9nJhAdviwFBGeUNsH1Z9hVW/A3ovUzMHc2Q/t3u0X8aPhoqFIl3XuZAv95txnhMkUR30mG63n/FgtFKU+ebCKBqnuePNOUXdZmfskmAjhL72cmMO+QDVSv3mh1hkandPmTuwSyGErs71nMWFbbY9BZxkU1e6j0yiGzRMVBwLWMBDzI7d5p/GCniW5Kp7Cvg6VqcyKmK9p5h50RNreq4OInO51dDOvNzSMKaPI04Kc7o+bE/XoeaMOCyad5/DZV7BIPCL3OrDI1EEvvtST5Kiivt0vdvIFtzkp64EL9pqmAzxvM4IHL7NIh042Njv1hlpxSOdEmezfQfLHTem2cL0VN4hUACJt4YUbrBo8S4GJKqJjC1D9ixvNEW8vG8Cy6QT+YdnGqAN9kCQOHdjB//S2z66um8cjgdp3mHKejPDi/ZOJ+8s6SIp5M7TSsCcicxZFs/HUN2N2c/uA8uQ+/d59R0RaVXMF3+2lxyy8aiJfXOfaiJS0DVduXFXzRWI1pqs1Wx7sxS6dtI7Cu/KGkeQqIPLelcIE8CcDeR/LOAA3hVCMNM26W0MmeRo7L+ve6tNkxrNoh6VJMdRVNLLgf9IooSrSjNlr6LaRlLuAyO0P+vyamuzFLp20jsK78oaR5Cog8t6VwgTwJwN5H8s4ADeFUIw0zbpbQyZ5Gjsv697q02TGsc5f10+cx/2U4c38tkolzJIHOayHIiMH+4ZravpPtdxYgk/5JhyfYD8dm8slDyA8qDASYBK5EWiczCUQGGkMiRtAA/EBxVHXXtHPoargWUIKHeLJm9ntHnhEq+dntgZ3xgFwujkc8IYUMHJA3yzbn/SWyjuni1JnFpuQTrTiMqxddkcAPZlV8ZqHF8UUI7e9Ny5gUS0qQ1NskGhdpr9qTAa/PlqETXuZf0qLIjp5VZV9BIVTLJ+c/mrIcOg4Sufj8JbKO6eLUmcWm5BOtOIyrF12RwA9mVXxmocXxRQjt70002ay3JkpMdyP2aUakbZ1w1iMB0BdD/+0XopzaGB1Ig8llv5sy09ixMuj93xEqkXlT40KWn9e7OCq2fUjsxcL0BoPsSPEqZISbuH2AI7bRa9EmezfQfLHTem2cL0VN4hU6a8ahv9g6zPeUGpYT9nrcI6SvWOl/9JJNI6brc+/HwNOSPkCm6IQaKdZfvZE2ZQCm8cjgdp3mHKejPDi/ZOJ+Qgs6xAwHQf5lwjXcz2eYq0RPp5f5t1J1KgrmQTYkwawFU4Y9dgMnLnD5aTgEgg4o",
            "items": null,
            "properties": null
        },
        "asdasd": {
            "type": "string",
            "key": "asdasd",
            "sort": 1,
            "title": "",
            "description": "",
            "required": null,
            "value": {
                "type": "const",
                "const_value": [
                    {
                        "type": "input",
                        "value": "ffff",
                        "name": "",
                        "args": null
                    }
                ],
                "expression_value": null
            },
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "field_3": {
            "type": "number",
            "key": "field_3",
            "sort": 2,
            "title": "",
            "description": "",
            "required": null,
            "value": null,
            "encryption": true,
            "encryption_value": "dZHgX9xkPmvAX3iawchn+n4YjK4pK8AB/GRMmwqX4KAsRahYby6DfQf98OuEHSJaHAGe6wWsGPkt6wS0/TNRW336zbPRtMDqcZVv42dNmD9Q9SUi7J6/M2ePVJDARiA8AZSdbVzfQJyhpchMZZ0aVSoO/WpWujtylJbWNWy2ebk5wRRyxE/aub3IMTNGkL1DVP6uYhXww1UnWDkpywpvzcCzgna+zojatUlZI36JsVHEB5AqfVMKtjQEDFAXExzYLujBv7fLdD9DJ0byMVb3+FoGJ+bsWRhYlYs/j2a0Eg5U/q5iFfDDVSdYOSnLCm/NRpC8H+fCMatz+H5UOr2VJQwj5lZukkHxT2oUROLS8baWmk01J2gHjSlFG2i763MCm3hTUrL/5fnNBC3amche7KgHLSgT12ZRpdEUIOj4LNJ3RPrekcnbpljdMlGW/nlYmnSU5XqOJqY0P9psPb5dTtIMOi77TRMszZu6Yhlvp8z/P2Csacg8xKT6pHwEfWPm1Zj9QHHe+XWZlZBD2t4dW34YjK4pK8AB/GRMmwqX4KCNQ+PD5Nwqf4YCubwdbEoQb1sN5gfOWI/bHAkx/1GyKplPnYmo44jC8S+ld4uWT24iBGk0bDSsLwWfrlcQ8xWB1qEXXY2wYYVLyI47nXo+25iqaX1yURrFVS6jhXE1oS0aVpZBft0443v8LESYpyqH03y12RRYZb+W2YbkDmZVoyK3XWLjdusAQsXRvrC9AumPZueznF1XvnLQcRtiCIzH3AWeERgiYCSqI+4PQ4Ax/wzcaLnccPnZJ3MyHelY4dJlCO3kL+296y4IR8sqMabZIrddYuN26wBCxdG+sL0C6Y9m57OcXVe+ctBxG2IIjMfcBZ4RGCJgJKoj7g9DgDH/P1VmcCvJhNDRaR7OdEBWRYwEE34e4hlDaMIs2NAzR89sOUUNm0ER3OLrb6ubZhSHukSg3BDdsLclyt1vLG2c2nie4JE1lgWLHsAGRzN0+oB/7Pw05FJly08NOl/+aK2hi9sJJejoDdKi8TUE8RdSRwGUnW1c30CcoaXITGWdGlUFDJ/j28MAf8wWSurg84OGg2C3jvB+OesjZubtdrm7xyX4KYjR0cgQBUjwcWohfKQOa+otk6jL2dBp44jKyuMbAZSdbVzfQJyhpchMZZ0aVQUMn+PbwwB/zBZK6uDzg4aRJJhxkWmzVpo7wDS7HwzOsz0GHeerUQwfBU7imkFoYrBB2nI2klE2LJ3n66XUTf1CJWTJaV6AIsMxUgMI7QNB4bxcKVAWXN7oZmK7SVjWhZwulCQoZFSk1ccg1HxGEjdOvQErR6xrxAPDxpFq1Zr6lQ6BlecKtnwQbTtRiyPQNWNzj0de9qBB/515HIakuS9rJ8XqdafeoeFS9WfoFCIaHos4uOt6+L+bIxhdYcCVqHlMyAOE1nmxFYkOFbRNn2A=",
            "items": null,
            "properties": null
        },
        "field_4": {
            "type": "boolean",
            "key": "field_4",
            "sort": 3,
            "title": "",
            "description": "",
            "required": null,
            "value": {
                "type": "expression",
                "const_value": null,
                "expression_value": [
                    {
                        "type": "input",
                        "value": "dddd",
                        "name": "",
                        "args": null
                    }
                ]
            },
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        }
    }
})


    const handleOnChange = value => {
        // console.log("schema change", value)
        setData(value)
    }

  return <JsonSchemaEditor data={data} allowExpression expressionSource={mockDataSource} debuggerMode onChange={handleOnChange} nodeMap={mockNodeMap} displayColumns={[ShowColumns.Key, ShowColumns.Label, ShowColumns.Type, ShowColumns.Description, ShowColumns.Value, ShowColumns.Encryption, ShowColumns.Required]}/>
}
```


























<!-- ## Allow Using Current Form Fields as an Expression Source

```jsx
import JsonSchemaEditor from './index';

const source = [
    {
        "label": "System variables",
        "value": "fields_6530fe660cd5f",
        "desc": "",
        "children": [
            {
                "label": "HTTP status code",
                "value": "guzzle.response.http_code"
            }
        ]
    }
]


const handleOnChange = value => {
    console.log("schema change", value)
}


export default () => (
  <JsonSchemaEditor allowExpression expressionSource={source} allowSourceInjectBySelf  uniqueFormId="XXXXXX" onChange={handleOnChange}/>
);
``` -->

<!-- ## Inject Upstream Expression Source

```jsx
import JsonSchemaEditor from './index';

const source = [
    {
        "label": "System variables",
        "value": "fields_6530fe660cd5f",
        "desc": "",
        "children": [
            {
                "label": "HTTP status code",
                "value": "guzzle.response.http_code"
            }
        ]
    }
]

const contextSource = [
    {
        "label": "Input configuration",
        "value": "fields",
        "desc": "",
        "children": [
            {
                "label": "Upstream field one",
                "value": "XXX.one"
            },
            {
                "label": "Upstream field two",
                "value": "XXX.two"
            }
        ]
    }
]

const handleOnChange = value => {
    // console.log("schema change", value)
}

const handleInnerSourceChange = innerSource => {
    // console.log("inner source map", innerSource)
}


export default () => (
  <JsonSchemaEditor allowExpression expressionSource={source} contextExpressionSource={contextSource} onChange={handleOnChange} onInnerSourceMapChange={handleInnerSourceChange}/>
);
``` -->

<!-- ## Enable Debug Mode

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor debuggerMode />;
``` -->

<!-- ## JSON Editor

```jsx
import JsonSchemaEditor from './index';

export default () => <JsonSchemaEditor jsonEditor />;
``` -->

<!-- ## Notice

```shell
npm install monaco-editor
```

The JSON editor uses a CDN; for offline use add the following

```jsx ｜ pure
import { loader } from '@monaco-editor/react';
import * as monaco from 'monaco-editor';
loader.config({ monaco });
``` -->

## Expression Data Source Shape

See the expression example for details.

| Param        | Description                                                          | Type   | Required |
| ------------ | -------------------------------------------------------------------- | ------ | -------- |
| label        | Display label                                                        | string | Yes      |
| value        | Actual selected value                                                | string | Yes      |
| return_type  | Return type when the cascader option is a function                   | string | -        |
| args         | Function parameters (array of parameter blocks) when option is a function | array  | -        |
| desc         | Function description when option is a function                       | string | -        |
| children     | Nested options when option is a function                             | array  | -        |

## API

| Param                   | Description                                                | Type                                      | Default                  |
| ----------------------- | ---------------------------------------------------------- | ----------------------------------------- | ------------------------ |
| jsonEditor              | Whether to show the JSON editor                            | boolean                                   | -                        |
| onChange                | Callback when the schema changes                           | (schema: Schema) => void                  | -                        |
| onBlur                  | Callback when the editor loses focus                       | (schema: Schema) => void                  | -                        |
| data                    | Initial schema                                             | Schema or string                          | -                        |
| allowExpression         | Allow values to be expressions                             | boolean                                   | false                    |
| onlyJson                | Restrict the root node to complex types                    | boolean                                   | true                     |
| customOptions           | Custom node options                                        | object                                    | {}                       |
| jsonImport              | Enable importing JSON                                      | boolean                                   | false                    |
| debuggerMode            | Enable debug mode                                          | boolean                                   | false                    |
| allowOperation          | Allow manually adding/removing elements                    | boolean                                   | true                     |
| oneChildAtLeast         | Require at least one child                                 | boolean                                   | true                     |
| firstChildKey           | Key for the first auto-generated child                     | string                                    | field_0                  |
| disabledFields          | Globally disabled fields (user cannot edit)                | DisableField[]                            | []                       |
| appendPosition          | Insertion position (after current or at tail)              | AppendPosition                            | AppendPosition.Next      |
| allowSourceInjectBySelf | Allow current form fields to be used as a data source      | boolean                                   | false                    |
| uniqueFormId            | Unique id for the current form                             | string                                    | -                        |
| contextExpressionSource | Upstream expression data source                            | ExpressionSource                          | -                        |
| onInnerSourceMapChange  | Callback when internal expression data source changes      | (innerSource: Record<string, Common.Options>) => void | -                        |
| displayColumns          | Columns to display                                         | ShowColumns[]                             | [1,2,3,4]                |


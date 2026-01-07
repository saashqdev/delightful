# DelightfulSegmented Component

DelightfulSegmented is an enhanced segmented control component based on Ant Design's Segmented, offering more polished styles and interactions.

## Features

-   **Rounded design**: Rounded corners by default for a modern look
-   **Custom styling**: Dark-mode friendly with consistent visuals
-   **Easy to use**: Same API as Ant Design Segmented
-   **Fully compatible**: Supports all Ant Design Segmented props and features

## Installation

```bash
# Included in @dtyq/delightful-ui; no separate install needed
```

## Basic Usage

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Daily", "Weekly", "Monthly"]} defaultValue="Daily" />
}
```

## Handle Option Changes

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"
import { useState } from "react"

const App = () => {
	const [value, setValue] = useState("Daily")

	const handleChange = (newValue) => {
		setValue(newValue)
		console.log("Selected:", newValue)
	}

	return (
		<DelightfulSegmented options={["Daily", "Weekly", "Monthly"]} value={value} onChange={handleChange} />
	)
}
```

## Object-Type Options

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"

const App = () => {
	return (
		<DelightfulSegmented
			options={[
				{ label: "Option One", value: "option1" },
				{ label: "Option Two", value: "option2" },
				{ label: "Option Three", value: "option3" },
			]}
			defaultValue="option1"
		/>
	)
}
```

## Options with Icons

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"
import { AppstoreOutlined, BarsOutlined } from "@ant-design/icons"

const App = () => {
	return (
		<DelightfulSegmented
			options={[
				{
					value: "list",
					icon: <BarsOutlined />,
					label: "List",
				},
				{
					value: "grid",
					icon: <AppstoreOutlined />,
					label: "Grid",
				},
			]}
		/>
	)
}
```

## Non-Rounded Design

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Option One", "Option Two", "Option Three"]} circle={false} />
}
```

## Disabled State

```tsx
import { DelightfulSegmented } from "@delightful/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Option One", "Option Two", "Option Three"]} disabled={true} />
}
```

## Props

The DelightfulSegmented component inherits all properties from Ant Design Segmented and adds the following prop:

| Prop Name | Type    | Default | Description          |
| --------- | ------- | ------- | -------------------- |
| circle    | boolean | true    | Use rounded corners? |

Inherited key props include:

| Prop Name    | Type                                                                                                               | Default  | Description                     |
| ------------ | ------------------------------------------------------------------------------------------------------------------ | -------- | -------------------------------- |
| options      | string[] \| number[] \| Array<{ label: ReactNode; value: string \| number; icon?: ReactNode; disabled?: boolean }> | []       | Configure each segmented item   |
| defaultValue | string \| number                                                                                                   | -        | Default selected value          |
| value        | string \| number                                                                                                   | -        | Current selected value          |
| onChange     | (value: string \| number) => void                                                                                  | -        | Callback when option changes    |
| disabled     | boolean                                                                                                            | false    | Whether disabled                |
| block        | boolean                                                                                                            | false    | Stretch to parent width         |
| size         | 'large' \| 'middle' \| 'small'                                                                                     | 'middle' | Control size                    |

For more props, see the [Ant Design Segmented documentation](https://ant.design/components/segmented/).

## Notes

1. DelightfulSegmented uses rounded design by default; set `circle` to false for square corners
2. The component has special dark-mode style optimizations by default
3. When used with other components, object-type options are recommended for easier state management

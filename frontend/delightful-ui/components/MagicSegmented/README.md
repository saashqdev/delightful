# DelightfulSegmented Component

DelightfulSegmented is an enhanced segmented controller component based on Ant Design Segmented, providing more beautiful styles and interactive effects.

## Features

-   **Rounded Design**: Provides rounded corner style by default, more modern and beautiful
-   **Custom Styles**: Adapted to dark mode, providing unified visual experience
-   **Easy to Use**: Maintains the same API as Ant Design Segmented
-   **Fully Compatible**: Supports all properties and functionality of Ant Design Segmented

## Installation

```bash
# Included in @dtyq/delightful-ui, no need to install separately
```

## Basic Usage

```tsx
import { DelightfulSegmented } from "@dtyq/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Daily", "Weekly", "Monthly"]} defaultValue="Daily" />
}
```

## Handling Option Changes

```tsx
import { DelightfulSegmented } from "@dtyq/delightful-ui"
import { useState } from "react"

const App = () => {
	const [value, setValue] = useState("Daily")

	const handleChange = (newValue) => {
		setValue(newValue)
		console.log("Current selected:", newValue)
	}

	return (
		<DelightfulSegmented options={["Daily", "Weekly", "Monthly"]} value={value} onChange={handleChange} />
	)
}
```

## Object Type Options

```tsx
import { DelightfulSegmented } from "@dtyq/delightful-ui"

const App = () => {
	return (
		<DelightfulSegmented
			options={[
				{ label: "Option 1", value: "option1" },
				{ label: "Option 2", value: "option2" },
				{ label: "Option 3", value: "option3" },
			]}
			defaultValue="option1"
		/>
	)
}
```

## Options with Icons

```tsx
import { DelightfulSegmented } from "@dtyq/delightful-ui"
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
import { DelightfulSegmented } from "@dtyq/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} circle={false} />
}
```

## Disabled State

```tsx
import { DelightfulSegmented } from "@dtyq/delightful-ui"

const App = () => {
	return <DelightfulSegmented options={["Option 1", "Option 2", "Option 3"]} disabled={true} />
}
```

## Properties Description

The DelightfulSegmented component inherits all properties from the Ant Design Segmented component and adds the following properties:

| Property Name | Type    | Default | Description              |
| ------------- | ------- | ------- | ------------------------ |
| circle        | boolean | true    | Whether to use rounded design |

Main inherited properties include:

| Property Name | Type                                                                                                               | Default  | Description                                |
| ------------- | ------------------------------------------------------------------------------------------------------------------ | -------- | ------------------------------------------ |
| options       | string[] \| number[] \| Array<{ label: ReactNode; value: string \| number; icon?: ReactNode; disabled?: boolean }> | []       | Configuration of each Segmented Item        |
| defaultValue  | string \| number                                                                                                   | -        | Default selected value                     |
| value         | string \| number                                                                                                   | -        | Currently selected value                   |
| onChange      | (value: string \| number) => void                                                                                  | -        | Callback function when option changes      |
| disabled      | boolean                                                                                                            | false    | Whether to disable                         |
| block         | boolean                                                                                                            | false    | Adjust width to parent element width       |
| size          | 'large' \| 'middle' \| 'small'                                                                                     | 'middle' | Control size                               |

For more properties, please refer to [Ant Design Segmented Documentation](https://ant.design/components/segmented/).

## Notes

1. DelightfulSegmented uses rounded design by default, you can set it to square corners through the `circle` property
2. This component has special style optimizations in dark mode, no additional configuration needed
3. When used with other components, it is recommended to use object type options for easy state management

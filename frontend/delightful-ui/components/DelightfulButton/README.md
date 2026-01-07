# DelightfulButton Delightful Button Component

`DelightfulButton` is an enhanced button based on Ant Design's Button component, providing more customization options and style optimization.

## Properties

| Property       | Type                            | Default  | Description                                          |
| -------------- | ------------------------------- | -------- | ---------------------------------------------------- |
| justify        | CSSProperties["justifyContent"] | "center" | Horizontal alignment of button content               |
| theme          | boolean                         | true     | Whether to apply theme styles                        |
| tip            | ReactNode                       | -        | Tooltip content displayed on mouse hover             |
| ...ButtonProps | -                               | -        | Support all properties of Ant Design Button          |

## Basic Usage

```tsx
import { DelightfulButton } from '@/components/base/DelightfulButton';

// Basic button
<DelightfulButton>Click me</DelightfulButton>

// Button with icon
<DelightfulButton icon={<IconStar />}>Favorite</DelightfulButton>

// Button with tooltip
<DelightfulButton tip="This is a tooltip">Hover to see tooltip</DelightfulButton>

// Custom alignment
<DelightfulButton justify="flex-start">Left aligned content</DelightfulButton>

// Without theme styles
<DelightfulButton theme={false}>No theme style</DelightfulButton>

// Different types of buttons
<DelightfulButton type="primary">Primary button</DelightfulButton>
<DelightfulButton type="default">Default button</DelightfulButton>
<DelightfulButton type="dashed">Dashed button</DelightfulButton>
<DelightfulButton type="link">Link button</DelightfulButton>
<DelightfulButton type="text">Text button</DelightfulButton>
```

## Features

1. **Enhanced style control**: Provides more style customization options such as content alignment
2. **Built-in tooltip functionality**: Easily add hover tooltips through the `tip` property
3. **Theme integration**: Control whether to apply theme styles through the `theme` property
4. **Flexible icon support**: Fully compatible with Ant Design's icon system

## When to Use

-   When you need to place a button on a page
-   When you need better style control for buttons
-   When you need buttons with hover tooltips
-   When you need buttons with specific content alignment

DelightfulButton component makes your buttons more flexible and beautiful, while maintaining all the functionality of Ant Design buttons.

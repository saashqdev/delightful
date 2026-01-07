# DelightfulButton — Enhanced Button Component

`DelightfulButton` is an enhanced button built on Ant Design's Button, offering more customization options and styling improvements.

## Props

| Prop           | Type                            | Default  | Description                        |
| -------------- | ------------------------------- | -------- | ---------------------------------- |
| justify        | CSSProperties["justifyContent"] | "center" | Horizontal alignment of button content |
| theme          | boolean                         | true     | Whether to apply theme styles      |
| tip            | ReactNode                       | -        | Tooltip content on hover           |
| ...ButtonProps | -                               | -        | Supports all Ant Design Button props |

## Basic Usage

```tsx
import { DelightfulButton } from '@/components/base/DelightfulButton';

// Basic button
<DelightfulButton>Click me</DelightfulButton>

// Button with icon
<DelightfulButton icon={<IconStar />}>Favorite</DelightfulButton>

// Button with tooltip
<DelightfulButton tip="This is a tip">Hover to view tooltip</DelightfulButton>

// Custom alignment
<DelightfulButton justify="flex-start">Left-aligned content</DelightfulButton>

// Without theme styles
<DelightfulButton theme={false}>No theme styles</DelightfulButton>

// Different button types
<DelightfulButton type="primary">Primary Button</DelightfulButton>
<DelightfulButton type="default">Default Button</DelightfulButton>
<DelightfulButton type="dashed">Dashed Button</DelightfulButton>
<DelightfulButton type="link">Link Button</DelightfulButton>
<DelightfulButton type="text">Text Button</DelightfulButton>
```

## Features

1. **Enhanced style control**: More styling options, such as content alignment
2. **Built-in tooltip**: Easily add hover tips via the `tip` prop
3. **Theme integration**: Control theme application via the `theme` prop
4. **Flexible icon support**: Fully compatible with Ant Design's icon system

## When to Use

-   When you need to place a button on a page
-   When you require better style control for buttons
-   When you want buttons with hover tooltips
-   When button content needs specific alignment

The `DelightfulButton` component makes buttons more flexible and visually appealing, while retaining all Ant Design Button features.

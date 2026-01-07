# DelightfulTag Delightful Tag Component

`DelightfulTag` is an enhanced tag component based on Ant Design's Tag, offering more polished styles and a better user experience.

## Properties

| Property    | Type | Default | Description                            |
| ----------- | ---- | ------- | -------------------------------------- |
| ...TagProps | -    | -       | Supports all Ant Design Tag properties |

## Basic Usage

```tsx
import { DelightfulTag } from '@/components/base/DelightfulTag';

// Basic tag
<DelightfulTag>Tag content</DelightfulTag>

// Closable tag
<DelightfulTag closable>Closable tag</DelightfulTag>

// Colored tags
<DelightfulTag color="blue">Blue tag</DelightfulTag>
<DelightfulTag color="red">Red tag</DelightfulTag>
<DelightfulTag color="green">Green tag</DelightfulTag>
<DelightfulTag color="orange">Orange tag</DelightfulTag>

// Tag with icon
<DelightfulTag icon={<IconStar />}>Tag with icon</DelightfulTag>

// Handle close event
<DelightfulTag closable onClose={() => console.log('Tag closed')}>
  Click to close
</DelightfulTag>
```

## Features

1. **Optimized styles**: Larger border radii, softer fill colors, overall more polished
2. **Custom close icon**: Uses the `DelightfulIcon` component for a unified visual style
3. **Flexible layout**: Internal flex layout ensures centered alignment
4. **Borderless design**: Transparent border by default for a modern look

## When to Use

-   When presenting tag-like data
-   When categorizing data
-   When displaying statuses or attributes
-   When users need to add or remove tags

The DelightfulTag component makes tag presentation more attractive and consistent while retaining all Ant Design Tag features.

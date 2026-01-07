# DelightfulTag Delightful Tag Component

`DelightfulTag` is an enhanced version of Ant Design's Tag component that provides more aesthetically pleasing styles and better user experience.

## Properties

| Property    | Type | Default | Description                                  |
| ----------- | ---- | ------- | -------------------------------------------- |
| ...TagProps | -    | -       | Support all attributes of Ant Design Tag     |

## Basic Usage

```tsx
import { DelightfulTag } from '@/components/base/DelightfulTag';

// Basic tag
<DelightfulTag>Tag content</DelightfulTag>

// Closable tag
<DelightfulTag closable>Closable tag</DelightfulTag>

// Tags with colors
<DelightfulTag color="blue">Blue tag</DelightfulTag>
<DelightfulTag color="red">Red tag</DelightfulTag>
<DelightfulTag color="green">Green tag</DelightfulTag>
<DelightfulTag color="orange">Orange tag</DelightfulTag>

// Tag with icon
<DelightfulTag icon={<IconStar />}>Tag with icon</DelightfulTag>

// Handling close event
<DelightfulTag closable onClose={() => console.log('Tag closed')}>
  Click to close
</DelightfulTag>
```

## Features

1. **Optimized styles**: Larger border radius, softer fill colors, more beautiful overall
2. **Custom close icon**: Uses DelightfulIcon component as the close icon for visual consistency
3. **Flexible layout**: Uses flex layout internally to ensure centered content alignment
4. **Borderless design**: Uses transparent borders by default for a more modern look

## When to Use

-   When displaying tagged data
-   When categorizing data
-   When displaying status or properties
-   When allowing users to add or delete tags

DelightfulTag component makes your tag display more beautiful and unified, while maintaining all the functionality of Ant Design Tag.

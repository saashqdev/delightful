# DelightfulEmpty — Empty State Component

`DelightfulEmpty` is a simplified empty state component based on Ant Design's Empty, providing internationalization support and clean default styles.

## Props

| Prop          | Type | Default | Description                         |
| ------------- | ---- | ------- | ----------------------------------- |
| ...EmptyProps | -    | -       | Supports all Ant Design Empty props |

## Basic Usage

```tsx
import { DelightfulEmpty } from '@/components/base/DelightfulEmpty';

// Basic usage
<DelightfulEmpty />

// Custom description text (overrides i18n text)
<DelightfulEmpty description="No data found" />

// Custom image
<DelightfulEmpty image="/path/to/custom-image.png" />

// Use in list or table
<div style={{ textAlign: 'center', padding: '20px 0' }}>
  <DelightfulEmpty />
</div>

// With action button
<DelightfulEmpty>
  <button>Create New Content</button>
</DelightfulEmpty>
```

## Features

1. **Internationalization**: Auto uses i18n translated "no data" text
2. **Minimal styling**: Defaults to `Empty.PRESENTED_IMAGE_SIMPLE` for a clean look
3. **Easy to use**: No extra configuration, works out of the box
4. **Fully customizable**: Supports all Ant Design Empty component props

## When to Use

-   When a page or container has no data
-   When search or filter results are empty
-   When a list, table, or result set is empty
-   When prompting users to create their first content

The `DelightfulEmpty` component makes your empty states clean and internationalized, suitable for any scenario.

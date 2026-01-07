# DelightfulCheckFavor Delightful Checkbox Component

DelightfulCheckFavor is a custom-styled checkbox component designed for favorites and preference settings scenarios. This component provides a selectable/deselectable interactive element with special visual styling, making it more intuitive in favorite-related features.

## Properties

| Property | Type                       | Default | Description                    |
| -------- | -------------------------- | ------- | ------------------------------ |
| checked  | boolean                    | false   | Whether the item is checked    |
| onChange | (checked: boolean) => void | -       | Callback when checked state changes |

## Basic Usage

```tsx
import DelightfulCheckFavor from '@/components/base/DelightfulCheckFavor';
import { useState } from 'react';

// Basic usage
const [isChecked, setIsChecked] = useState(false);

<DelightfulCheckFavor
  checked={isChecked}
  onChange={(checked) => setIsChecked(checked)}
/>

// Default checked
<DelightfulCheckFavor
  checked={true}
  onChange={(checked) => console.log('Checked state:', checked)}
/>

// Usage in list items
<div className="item">
  <span>Favorite item</span>
  <DelightfulCheckFavor
    checked={item.isFavorite}
    onChange={(checked) => handleFavoriteChange(item.id, checked)}
  />
</div>
```

## Features

-   **Custom Styling**: Differs from traditional checkboxes, provides appearance more suitable for favorites scenarios
-   **Easy to Use**: Simple API design, convenient to use
-   **State Management**: Supports controlled mode, allowing external state control of checked state
-   **Interactive Feedback**: Provides intuitive visual feedback, enhancing user experience
-   **Lightweight**: Simple component implementation, no additional dependencies

## Use Cases

-   Item selection in favorites
-   Interactive elements for like/favorite features
-   Toggle options in preference settings
-   Any interface elements that need to display "favorite" or "like" status

DelightfulCheckFavor component provides a visually more appropriate checkbox for favorite scenarios, allowing users to get more intuitive feedback when performing favorite operations, improving overall user experience.

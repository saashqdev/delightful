# DelightfulCheckFavor Magic Favorite Checkbox Component

DelightfulCheckFavor is a custom-styled checkbox component designed for favorites and preference settings scenarios. It provides a selectable/deselectable interactive element with a distinctive visual style, making favorite-related functionality more intuitive.

## Properties

| Property | Type                       | Default | Description                         |
| -------- | -------------------------- | ------- | ----------------------------------- |
| checked  | boolean                    | false   | Whether the checkbox is selected    |
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

// Use in a list item
<div className="item">
  <span>Favorite item</span>
  <DelightfulCheckFavor
    checked={item.isFavorite}
    onChange={(checked) => handleFavoriteChange(item.id, checked)}
  />
</div>
```

## Features

-   **Custom styles**: Distinct from traditional checkboxes, with an appearance better suited to favorite scenarios
-   **Simple to use**: Clean API design for easy usage
-   **State management**: Supports controlled mode; manage checked state externally
-   **Interaction feedback**: Provides intuitive visual feedback to enhance UX
-   **Lightweight**: Simple implementation without extra dependencies

## Use Cases

-   Selecting items in a favorites list
-   Interactive element for like/favorite functionality
-   Toggle options in preference settings
-   Any UI element that indicates a "favorite" or "like" state

The DelightfulCheckFavor component offers a checkbox styled specifically for favorite scenarios, giving users more intuitive feedback during favorite actions and improving the overall user experience.

# DelightfulCheckFavor Magic Checkbox Component

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

## 使用场景

-   收藏夹中的项目选择
-   喜爱/收藏功能的交互元素
-   偏好设置中的开关选项
-   任何需要表示"收藏"或"喜爱"状态的界面元素

DelightfulCheckFavor 组件通过提供一个视觉上更符合收藏场景的复选框，使得用户在进行收藏操作时能够获得更直观的反馈，提升整体用户体验。

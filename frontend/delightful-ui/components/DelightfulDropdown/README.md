# DelightfulDropdown Delightful Dropdown Menu Component

`DelightfulDropdown` is an enhanced dropdown menu based on Ant Design's Dropdown component, providing more aesthetically pleasing styles and better user experience.

## Properties

| Property         | Type      | Default | Description                                          |
| ---------------- | --------- | ------- | ---------------------------------------------------- |
| menu             | MenuProps | -       | Menu configuration to define dropdown content and behavior |
| overlayClassName | string    | -       | Custom class name for dropdown menu                  |
| ...DropDownProps | -         | -       | Support all properties of Ant Design Dropdown        |

## Basic Usage

```tsx
import { DelightfulDropdown } from '@/components/base/DelightfulDropdown';
import { Button, Space } from 'antd';
import type { MenuProps } from 'antd';
import { IconSettings, IconUser, IconLogout } from '@tabler/icons-react';

// Define menu items
const items: MenuProps['items'] = [
  {
    key: '1',
    label: 'Profile',
    icon: <IconUser size={16} />,
  },
  {
    key: '2',
    label: 'Settings',
    icon: <IconSettings size={16} />,
  },
  {
    type: 'divider',
  },
  {
    key: '3',
    label: 'Logout',
    icon: <IconLogout size={16} />,
    danger: true,
  },
];

// Basic usage
<DelightfulDropdown menu={{ items }}>
  <Button>Click to show dropdown menu</Button>
</DelightfulDropdown>

// With trigger method
<DelightfulDropdown menu={{ items }} trigger={['hover']}>
  <Button>Hover to show dropdown menu</Button>
</DelightfulDropdown>

// With arrow
<DelightfulDropdown menu={{ items }} arrow>
  <Button>Dropdown menu with arrow</Button>
</DelightfulDropdown>

// With event handling
<DelightfulDropdown
  menu={{
    items,
    onClick: (e) => console.log('Menu item clicked:', e.key),
  }}
>
  <Button>Click menu item to trigger event</Button>
</DelightfulDropdown>

// Disabled state
<DelightfulDropdown menu={{ items }} disabled>
  <Button>Disabled dropdown menu</Button>
</DelightfulDropdown>
```

## Features

1. **Optimized styles**: Larger border radius, better spacing, and more beautiful hover effects
2. **Icon spacing optimization**: Optimized spacing between icons and text for better layout
3. **Danger item style enhancement**: Provides more obvious visual hints for dangerous operations
4. **Submenu position optimization**: Adjusts submenu position for more natural display
5. **Theme adaptation**: Automatically adapts to light/dark theme for consistent visual experience

## When to Use

-   When you need to place a dropdown menu on a page
-   When you need to provide multiple action options without taking much space
-   When you need to group and display related operations
-   When you need to include dangerous operations in dropdown menu
-   When you need more beautiful dropdown menu styles

DelightfulDropdown component makes your dropdown menu more beautiful and user-friendly, while maintaining all the functionality of Ant Design Dropdown.

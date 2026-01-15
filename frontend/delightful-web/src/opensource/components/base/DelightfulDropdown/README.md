# DelightfulDropdown — Enhanced Dropdown Menu Component

`DelightfulDropdown` is an enhanced dropdown menu built on Ant Design's Dropdown, offering cleaner styling and a better user experience.

## Props

| Prop             | Type      | Default | Description                                  |
| ---------------- | --------- | ------- | -------------------------------------------- |
| menu             | MenuProps | -       | Menu configuration defining content/behavior |
| overlayClassName | string    | -       | Custom class name for the dropdown           |
| ...DropDownProps | -         | -       | Supports all Ant Design Dropdown props       |

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
    label: 'Log out',
    icon: <IconLogout size={16} />,
    danger: true,
  },
];

// Basic usage
<DelightfulDropdown menu={{ items }}>
  <Button>Click to show dropdown</Button>
</DelightfulDropdown>

// With trigger
<DelightfulDropdown menu={{ items }} trigger={['hover']}>
  <Button>Hover to show dropdown</Button>
</DelightfulDropdown>

// With arrow
<DelightfulDropdown menu={{ items }} arrow>
  <Button>Dropdown with arrow</Button>
</DelightfulDropdown>

// With event handling
<DelightfulDropdown
  menu={{
    items,
    onClick: (e) => console.log('Clicked menu item:', e.key),
  }}
>
  <Button>Click menu item to trigger event</Button>
</DelightfulDropdown>

// Disabled state
<DelightfulDropdown menu={{ items }} disabled>
  <Button>Disabled dropdown</Button>
</DelightfulDropdown>
```

## Features

1. **Optimized styling**: Larger rounded corners, balanced spacing, and polished hover effects
2. **Icon spacing**: Improved spacing between icons and text for alignment
3. **Danger item styling**: Clearer visual cues for dangerous actions
4. **Submenu positioning**: Adjusted submenu placement for natural display
5. **Theme adaptation**: Auto adapts to light/dark themes for consistent visuals

## When to Use

-   Provide dropdown menus in a page
-   Offer multiple actions without consuming much space
-   Group related actions in a single menu
-   Include dangerous actions in dropdowns
-   Use refined dropdown styles for better UX

The `DelightfulDropdown` component keeps dropdowns elegant and user-friendly while retaining all features of Ant Design's Dropdown.

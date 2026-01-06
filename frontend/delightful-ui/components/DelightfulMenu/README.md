# DelightfulMenu Magic Menu Component

`DelightfulMenu` is an enhanced menu component based on Ant Design Menu, providing cleaner styles and better user experience.

## Properties

| Property     | Type | Default | Description                              |
| ------------ | ---- | ------- | ---------------------------------------- |
| ...MenuProps | -    | -       | All properties of Ant Design Menu are supported |

## Basic Usage

```tsx
import { DelightfulMenu } from '@/components/base/DelightfulMenu';
import { IconHome, IconUser, IconSettings } from '@tabler/icons-react';

// Basic usage
<DelightfulMenu
  items={[
    {
      key: 'home',
      label: 'Home',
      icon: <IconHome size={16} />,
    },
    {
      key: 'profile',
      label: 'Profile',
      icon: <IconUser size={16} />,
    },
    {
      key: 'settings',
      label: 'Settings',
      icon: <IconSettings size={16} />,
    },
  ]}
/>

// Default selected item
<DelightfulMenu
  defaultSelectedKeys={['home']}
  items={[
    {
      key: 'home',
      label: 'Home',
    },
    {
      key: 'profile',
      label: 'Profile',
    },
  ]}
/>

// Vertical menu
<DelightfulMenu
  mode="vertical"
  items={[
    {
      key: 'home',
      label: 'Home',
    },
    {
      key: 'profile',
      label: 'Profile',
    },
  ]}
/>

// Menu with submenus
<DelightfulMenu
  mode="vertical"
  items={[
    {
      key: 'home',
      label: 'Home',
    },
    {
      key: 'settings',
      label: 'Settings',
      children: [
        {
          key: 'general',
          label: 'General Settings',
        },
        {
          key: 'account',
          label: 'Account Settings',
        },
      ],
    },
  ]}
/>

// Dangerous operations
<DelightfulMenu
  items={[
    {
      key: 'profile',
      label: 'Profile',
    },
    {
      key: 'logout',
      label: 'Logout',
      danger: true,
    },
  ]}
/>

// Listen to selection events
<DelightfulMenu
  onClick={({ key }) => console.log('Clicked:', key)}
  items={[
    {
      key: 'home',
      label: 'Home',
    },
    {
      key: 'profile',
      label: 'Profile',
    },
  ]}
/>
```

## Features

1. **Clean Design**: Removes background color and border of selected items, providing a cleaner visual effect
2. **Transparent Background**: Menu background is transparent, integrating better with various interfaces
3. **Optimized Spacing**: Reasonable spacing between menu items improves readability
4. **Dangerous Operation Optimization**: Dangerous operation items have special hover effects, making them more prominent

## When to Use

-   When you need to provide navigation functionality in a page
-   When you need to display a group of related operations or functions
-   When you need to display options in a dropdown menu
-   When you need to create a context menu (right-click menu)

The DelightfulMenu component makes your menu cleaner and more user-friendly, while maintaining all the features of Ant Design Menu.

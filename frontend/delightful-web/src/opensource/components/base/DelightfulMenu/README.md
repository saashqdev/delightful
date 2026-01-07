# DelightfulMenu — Enhanced Menu Component

`DelightfulMenu` is an enhanced menu built on Ant Design's Menu, offering cleaner styling and a better user experience.

## Props

| Prop         | Type | Default | Description                     |
| ------------ | ---- | ------- | ------------------------------- |
| ...MenuProps | -    | -       | Supports all Ant Design Menu props |

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

// With submenu
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

// Dangerous action
<DelightfulMenu
  items={[
    {
      key: 'profile',
      label: 'Profile',
    },
    {
      key: 'logout',
      label: 'Log out',
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

1. **Minimal styling**: Removes selected item background and borders for a clean look
2. **Transparent background**: Blends seamlessly into different UIs
3. **Optimized spacing**: Reasonable spacing between items for readability
4. **Danger actions**: Special hover effect for dangerous actions for visibility

## When to Use

- Provide navigation within a page
- Display a set of related actions or features
- Show options in a dropdown menu
- Create context menus (right-click menus)

The `DelightfulMenu` component keeps menus clean and user-friendly while retaining all features of Ant Design's Menu.

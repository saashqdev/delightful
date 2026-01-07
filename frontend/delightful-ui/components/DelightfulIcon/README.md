# DelightfulIcon Delightful Icon Component

`DelightfulIcon` is an icon wrapper component based on Tabler Icons, providing theme adaptation and unified style control.

## Properties

| Property     | Type                                                                    | Default | Description                                       |
| ------------ | ----------------------------------------------------------------------- | ------- | ------------------------------------------------- |
| component    | ForwardRefExoticComponent<Omit<IconProps, "ref"> & RefAttributes<Icon>> | -       | The Tabler icon component to render              |
| active       | boolean                                                                 | false   | Whether the icon is in active state              |
| animation    | boolean                                                                 | false   | Whether to enable animation effect               |
| ...IconProps | -                                                                       | -       | Supports all Tabler Icons properties             |

## Basic Usage

```tsx
import { DelightfulIcon } from '@/components/base/DelightfulIcon';
import { IconHome, IconStar, IconSettings } from '@tabler/icons-react';

// Basic icon
<DelightfulIcon component={IconHome} />

// Custom size
<DelightfulIcon component={IconStar} size={24} />

// Custom color (overrides theme color)
<DelightfulIcon component={IconSettings} color="blue" />

// Custom stroke width
<DelightfulIcon component={IconHome} stroke={2} />

// Active state
<DelightfulIcon component={IconStar} active />

// With animation effect (if implemented)
<DelightfulIcon component={IconSettings} animation />
```

## Features

1. **Theme Adaptation**: Automatically adjusts icon color based on current theme (light/dark)
2. **Unified Style**: Provides unified stroke width and color by default
3. **Type Safety**: Full TypeScript support with complete type definitions
4. **Flexible Extension**: Easy to customize various icon properties through attributes

## When to Use

-   When you need to use Tabler Icons in your application
-   When icons need to automatically adapt to theme changes
-   When you need centralized icon style management
-   When you need to add interactive states to icons (such as active state)

DelightfulIcon component makes your icon usage simpler and more unified, while ensuring they adapt to your application's theme settings.

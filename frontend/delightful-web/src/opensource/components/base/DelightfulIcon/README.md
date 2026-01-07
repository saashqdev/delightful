# DelightfulIcon — Icon Wrapper Component

`DelightfulIcon` is an icon wrapper built on Tabler Icons, providing theme adaptation and unified style control.

## Props

| Prop         | Type                                                                    | Default | Description                          |
| ------------ | ----------------------------------------------------------------------- | ------- | ------------------------------------ |
| component    | ForwardRefExoticComponent<Omit<IconProps, "ref"> & RefAttributes<Icon>> | -       | Tabler icon component to render      |
| active       | boolean                                                                 | false   | Whether in active state              |
| animation    | boolean                                                                 | false   | Whether to enable animation          |
| ...IconProps | -                                                                       | -       | Supports all Tabler Icons props      |

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

1. **Theme adaptation**: Auto adjusts icon color based on current theme (light/dark)
2. **Unified styling**: Default consistent stroke width and color
3. **Type safety**: Full TypeScript support with complete type definitions
4. **Flexible extension**: Easily customize icon properties via props

## When to Use

-   Use Tabler icons in your application
-   Want icons that auto-adapt to theme changes
-   Need unified icon style management
-   Add interactive states to icons (like active state)

The `DelightfulIcon` component makes icon usage simpler and more unified, while ensuring they adapt to your app's theme settings.

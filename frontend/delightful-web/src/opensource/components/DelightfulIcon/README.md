# DelightfulIcon

`DelightfulIcon` is a wrapper around Tabler Icons that adds theme-aware styling and unified appearance controls.

## Props

| Name         | Type                                                                    | Default | Description                             |
| ------------ | ----------------------------------------------------------------------- | ------- | --------------------------------------- |
| component    | ForwardRefExoticComponent<Omit<IconProps, "ref"> & RefAttributes<Icon>> | -       | The Tabler icon component to render     |
| active       | boolean                                                                 | false   | Whether the icon is in the active state |
| animation    | boolean                                                                 | false   | Whether to enable icon animation        |
| ...IconProps | -                                                                       | -       | Supports all Tabler Icons props         |

## Basic Usage

```tsx
import { DelightfulIcon } from '@/components/base/DelightfulIcon'
import { IconHome, IconStar, IconSettings } from '@tabler/icons-react'

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

// With animation enabled (if implemented)
<DelightfulIcon component={IconSettings} animation />
```

## Highlights

1. **Theme-aware**: Automatically adapts icon color to the current theme (light/dark)
2. **Consistent styling**: Ships with unified stroke width and color defaults
3. **Type-safe**: Full TypeScript support with complete typings
4. **Easy to extend**: Customize icon behavior and appearance via props

## When to Use

-   Use Tabler icons in the app
-   Need icons that react to theme changes
-   Want centralized control over icon styling
-   Need interactive states such as active/selected

DelightfulIcon makes icons simple and consistent while adapting to your application's theme settings.

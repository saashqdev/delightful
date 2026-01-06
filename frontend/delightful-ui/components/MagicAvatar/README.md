# DelightfulAvatar Magic Avatar Component

`DelightfulAvatar` is an enhanced avatar component based on Ant Design Avatar, providing auto-generated colors, badge support, and more features.

## Properties

| Property Name | Type                                      | Default  | Description                              |
| ------------- | ----------------------------------------- | -------- | ---------------------------------------- |
| badgeProps    | BadgeProps                                | -        | Badge properties, used to display badges on avatars |
| size          | number \| 'large' \| 'small' \| 'default' | 40       | Avatar size                              |
| shape         | 'circle' \| 'square'                      | 'square' | Avatar shape, default is square          |
| src           | string                                    | -        | Avatar image URL                         |
| ...AvatarProps | -                                         | -        | Supports all Ant Design Avatar properties |

## Basic Usage

```tsx
import { DelightfulAvatar } from '@/components/base/DelightfulAvatar';

// Basic usage - use text (automatically takes the first two characters)
<DelightfulAvatar>Username</DelightfulAvatar>

// Use image
<DelightfulAvatar src="https://example.com/avatar.png" />

// Custom size
<DelightfulAvatar size={64}>Large Avatar</DelightfulAvatar>
<DelightfulAvatar size={24}>Small Avatar</DelightfulAvatar>

// Use badge
<DelightfulAvatar
  badgeProps={{
    count: 5,
    dot: true,
    status: 'success'
  }}
>
  User
</DelightfulAvatar>

// Custom style
<DelightfulAvatar style={{ border: '2px solid red' }}>Custom</DelightfulAvatar>
```

## Features

1. **Auto-generated colors**: When no image is provided, background and text colors are automatically generated based on text content
2. **Badge support**: Add badges on avatars through `badgeProps` to show status or numbers
3. **Text truncation**: Automatically truncates text to first two characters for avatar content
4. **URL validation**: Automatically validates src as a valid URL, falls back to text display if invalid
5. **Unified style**: Provides unified border and text shadow effects

## When to Use

-   When you need to display user avatars
-   When you need to show status or notification count on avatars
-   When you need to auto-generate colorful avatars based on usernames
-   When you need to display user identifiers in lists or comments

DelightfulAvatar component makes your avatar display more beautiful and intelligent, without needing to prepare avatar images for each user, while still presenting personalized effects.

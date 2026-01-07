# DelightfulAvatar Delightful Avatar Component

`DelightfulAvatar` is an enhanced avatar component based on Ant Design Avatar, providing automatic color generation, badge support, and other features.

## Properties

| Property       | Type                                      | Default  | Description                                       |
| -------------- | ----------------------------------------- | -------- | ------------------------------------------------- |
| badgeProps     | BadgeProps                                | -        | Badge properties for displaying badges on avatar |
| size           | number \| 'large' \| 'small' \| 'default' | 40       | Size of the avatar                                |
| shape          | 'circle' \| 'square'                      | 'square' | Shape of the avatar, default is square            |
| src            | string                                    | -        | URL of the avatar image                           |
| ...AvatarProps | -                                         | -        | Supports all Ant Design Avatar properties         |

## Basic Usage

```tsx
import { DelightfulAvatar } from '@/components/base/DelightfulAvatar';

// Basic usage - using text (automatically takes first two characters)
<DelightfulAvatar>Username</DelightfulAvatar>

// Using image
<DelightfulAvatar src="https://example.com/avatar.png" />

// Custom size
<DelightfulAvatar size={64}>Large Avatar</DelightfulAvatar>
<DelightfulAvatar size={24}>Small Avatar</DelightfulAvatar>

// Using badge
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

1. **Automatic Color Generation**: When no image is provided, automatically generates background and text colors based on text content
2. **Badge Support**: Can add badges on avatars via `badgeProps` to display status or numbers
3. **Text Truncation**: Automatically takes the first two characters of text as avatar content
4. **URL Validation**: Automatically validates if src is a valid URL, falls back to text display when invalid
5. **Unified Styling**: Provides unified border and text shadow effects

## When to Use

-   When you need to display user avatars
-   When you need to show status or notification count on avatars
-   When you need to automatically generate colored avatars based on usernames
-   When you need to display user identifiers in lists or comments

The DelightfulAvatar component makes your avatar display more beautiful and intelligent, presenting personalized effects without needing to prepare avatar images for each user.

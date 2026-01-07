# DelightfulEmoji — Emoji Rendering Component

DelightfulEmoji is a simple emoji image renderer for displaying emoji symbols in the UI. Built on HTML's `img` tag, it supports customizable emoji codes, namespaces, and suffixes.

## Props

| Prop   | Type   | Default   | Description                                      |
| ------ | ------ | --------- | ------------------------------------------------ |
| code   | string | -         | Emoji code (required)                            |
| ns     | string | "emojis/" | Emoji namespace for constructing image path      |
| suffix | string | ".png"    | File suffix for emoji image                      |
| size   | number | -         | Emoji image size                                 |

Additionally, supports all HTML `img` tag attributes except `src` and `alt`.

## Basic Usage

```tsx
import DelightfulEmoji from '@/components/base/DelightfulEmoji';

// Basic usage
<DelightfulEmoji code="smile" />

// Custom namespace and suffix
<DelightfulEmoji code="heart" ns="custom/" suffix=".svg" />

// Set emoji size
<DelightfulEmoji code="thumbs_up" size={24} />

// Add other img attributes
<DelightfulEmoji code="star" className="custom-emoji" onClick={handleClick} />
```

## Features

-   **Simple and easy**: Just provide an emoji code to render
-   **Highly customizable**: Custom namespace, file suffix, and size
-   **Flexible**: Supports all standard `img` tag attributes
-   **Lightweight**: Clean implementation with no extra dependencies

## Use Cases

-   Display emoji symbols in chat interfaces
-   Insert emojis in rich text editors
-   Add emotional elements to the user interface
-   Use emojis to express feelings in comments and feedback systems

The `DelightfulEmoji` component has a clean design and integrates easily into any scenario requiring emoji display. It provides a unified emoji rendering interface, ensuring consistency and maintainability.

# DelightfulEmojiPanel — Emoji Picker Component

DelightfulEmojiPanel is a feature-rich emoji picker for applications. It displays a grid of clickable emojis and supports switching between different emoji categories (e.g., regular emojis and favorites).

## Props

| Prop    | Type                       | Default | Description                                                         |
| ------- | -------------------------- | ------- | ------------------------------------------------------------------- |
| onClick | (emoji: EmojiInfo) => void | -       | Callback when an emoji is clicked; receives the selected emoji info |

The `EmojiInfo` type is defined as follows:

```typescript
export type EmojiInfo = {
	/** Emoji code */
	code: string
	/** Emoji namespace */
	ns: string
	/** Emoji suffix */
	suffix?: string
	/** Skin tone */
	tone?: string
	/** Emoji size */
	size?: number
}
```

## Basic Usage

```tsx
import DelightfulEmojiPanel from "@/components/base/DelightfulEmojiPanel"
import type { EmojiInfo } from "@/components/base/DelightfulEmojiPanel/types"

// Basic usage
const handleEmojiClick = (emoji: EmojiInfo) => {
	console.log("Selected emoji:", emoji)
	// Handle emoji selection logic
}

;<DelightfulEmojiPanel onClick={handleEmojiClick} />

// Use inside a Popover
import { Popover } from "antd"
;<Popover
	content={<DelightfulEmojiPanel onClick={handleEmojiClick} />}
	trigger="click"
	styles={{
		body: {
			padding: 0,
		},
	}}
>
	<Button>Open Emoji Panel</Button>
</Popover>
```

## Features

-   **Rich emoji set**: Multiple built-in emoji collections
-   **Category switching**: Switch between different emoji panels
-   **Polished UI**: Designed layout with scroll area and bottom switch bar
-   **Custom callback**: Use `onClick` to customize post-selection behavior
-   **Responsive grid**: Emoji grid adapts to various container sizes

## Use Cases

-   Emoji picker in chat applications
-   Emoji input for social media comments
-   Emoji insertion in rich text editors
-   Any interaction requiring emoji selection

The `DelightfulEmojiPanel` component provides a complete emoji selection solution that integrates easily into apps requiring emoji input, enhancing user experience and interaction.

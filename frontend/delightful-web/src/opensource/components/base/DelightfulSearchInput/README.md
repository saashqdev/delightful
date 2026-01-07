# DelightfulSearchInput Magic Search Input Component

`DelightfulSearchInput` is an enhanced search input based on Ant Design's Input.Search, offering more polished styles and a better user experience.

## Properties

| Property       | Type | Default | Description                                 |
| -------------- | ---- | ------- | ------------------------------------------- |
| ...SearchProps | -    | -       | Supports all Ant Design Input.Search props  |

## Basic Usage

```tsx
import { DelightfulSearchInput } from '@/components/base/DelightfulSearchInput';

// Basic usage
<DelightfulSearchInput placeholder="Enter search content" />

// With default value
<DelightfulSearchInput defaultValue="Default search content" />

// Listen for search events
<DelightfulSearchInput
  placeholder="Press Enter to search"
  onSearch={(value) => console.log('Search value:', value)}
/>

// Listen for input changes
<DelightfulSearchInput
  placeholder="Search while typing"
  onChange={(e) => console.log('Current input:', e.target.value)}
/>

// Disabled state
<DelightfulSearchInput disabled placeholder="Disabled" />

// Loading state
<DelightfulSearchInput loading placeholder="Loading..." />

// Custom styles
<DelightfulSearchInput
  placeholder="Custom styles"
  style={{ width: 300 }}
/>
```

## Features

1. **Optimized styles**: Larger border radii, softer background, overall more polished
2. **Built-in search icon**: Uses the DelightfulIcon component for a unified visual style
3. **Minimalist design**: Hides the default search button; use Enter key to search
4. **Borderless focus**: No border or shadow on focus to keep the UI clean

## When to Use

-   Add search functionality to a page
-   Filter or query via user-entered keywords
-   Need a visually pleasing search input
-   Perform real-time search as users type

The DelightfulSearchInput component makes search inputs more attractive and user-friendly while retaining all Ant Design Input.Search features.

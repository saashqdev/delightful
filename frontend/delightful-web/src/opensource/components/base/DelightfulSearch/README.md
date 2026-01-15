# DelightfulSearch Delightful Search Component

`DelightfulSearch` is a simplified search input based on Ant Design's Input component, offering a built-in search icon and optimized styles.

## Properties

| Property      | Type | Default | Description                         |
| ------------- | ---- | ------- | ----------------------------------- |
| ...InputProps | -    | -       | Supports all Ant Design Input props |

## Basic Usage

```tsx
import { DelightfulSearch } from '@/components/base/DelightfulSearch';

// Basic usage
<DelightfulSearch />

// With default value
<DelightfulSearch defaultValue="Default search content" />

// Listen for input changes
<DelightfulSearch
  onChange={(e) => console.log('Current input:', e.target.value)}
/>

// Custom placeholder
<DelightfulSearch placeholder="Custom placeholder" />

// Disabled state
<DelightfulSearch disabled />

// Custom styles
<DelightfulSearch
  style={{ width: 300 }}
/>

// Using a ref
const searchRef = useRef<InputRef>(null);
<DelightfulSearch ref={searchRef} />
```

## Features

1. **Built-in search icon**: Displays a search icon before the input by default
2. **Internationalization**: Uses the i18n-translated "Search" as the default placeholder
3. **Minimal design**: Provides a clean search input style
4. **Fully customizable**: Supports all Ant Design Input properties

## When to Use

-   Add a simple search input to a page
-   When a search button isn't required—just an input and icon
-   Need a lightweight search component
-   Integrate search with other components

The DelightfulSearch component delivers a clean, easy-to-use search input suitable for various search scenarios.

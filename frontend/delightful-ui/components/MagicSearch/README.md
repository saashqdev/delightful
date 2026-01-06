# DelightfulSearch Magic Search Component

`DelightfulSearch` is a simplified search input based on Ant Design's Input component, providing a built-in search icon and optimized styles.

## Properties

| Property      | Type | Default | Description                                          |
| ------------- | ---- | ------- | ---------------------------------------------------- |
| ...InputProps | -    | -       | Support all properties of Ant Design Input           |

## Basic Usage

```tsx
import { DelightfulSearch } from '@/components/base/DelightfulSearch';

// Basic usage
<DelightfulSearch />

// With default value
<DelightfulSearch defaultValue="Default search content" />

// Listen to input changes
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

// Using ref
const searchRef = useRef<InputRef>(null);
<DelightfulSearch ref={searchRef} />
```

## Features

1. **Built-in search icon**: Shows a search icon before the input by default
2. **i18n support**: Automatically uses i18n translated "Search" as default placeholder
3. **Clean design**: Provides a clean search input box style
4. **Fully customizable**: Supports all properties of Ant Design Input component

## When to Use

-   When you need to add a simple search input on a page
-   When you only need an input box and search icon without a search button
-   When you need a lightweight search component
-   When you need to use it with other components for search functionality

DelightfulSearch component makes your search input box cleaner and easier to use, suitable for any scenario that requires search functionality.

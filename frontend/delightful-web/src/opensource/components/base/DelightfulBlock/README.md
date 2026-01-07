# DelightfulBlock Delightful Block Component

`DelightfulBlock` is a simple editable content block component. It provides a `div` container with the `contentEditable` attribute, allowing users to directly edit its content.

## Properties

| Property  | Type                           | Default | Description                                         |
| --------- | ------------------------------ | ------- | --------------------------------------------------- |
| children  | ReactNode                      | -       | Content displayed inside the block                  |
| ...props  | HTMLAttributes<HTMLDivElement> | -       | Supports all HTML attributes for the `div` element  |

## Basic Usage

```tsx
import DelightfulBlock from '@/components/base/DelightfulBlock';

// Basic usage
<DelightfulBlock>Editable content</DelightfulBlock>

// Set styles
<DelightfulBlock
  style={{
    padding: '10px',
    border: '1px solid #eee',
    borderRadius: '4px'
  }}
>
  Editable content with styles
</DelightfulBlock>

// Add event handlers
<DelightfulBlock
  onBlur={(e) => console.log('Edit completed', e.currentTarget.textContent)}
  onInput={(e) => console.log('Content changed', e.currentTarget.textContent)}
>
  Editable content with event handlers
</DelightfulBlock>
```

## Features

1. **Simple and lightweight**: Provides the most basic content editing without complex formatting controls
2. **Easy to integrate**: Easily integrates into scenarios that need content editing
3. **Fully customizable**: Supports all `div` HTML attributes; customize styles and behavior as needed
4. **Reference-friendly**: Maintains a reference to the DOM element via `useRef` for convenient external operations

## When to Use

-   When simple content editing is needed
-   When users should directly edit text content on the page
-   For simple editing scenarios without complex rich-text requirements
-   When you need to customize the appearance and behavior of the editing area

The DelightfulBlock component offers a simple yet flexible content editing solution, suitable for scenarios that require basic text editing capabilities.

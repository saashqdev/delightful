# DelightfulScrollBar Delightful Scrollbar Component

`DelightfulScrollBar` is an enhanced scrollbar component based on `simplebar-core`, delivering a more attractive and smoother custom scrollbar experience.

## Properties

| Property            | Type                    | Default | Description                                |
| ------------------- | ----------------------- | ------- | ------------------------------------------ |
| children            | ReactNode \| RenderFunc | -       | Child elements or a render function        |
| scrollableNodeProps | object                  | {}      | Props for the scrollable node              |
| ...SimpleBarOptions | -                       | -       | Supports all `simplebar-core` options      |
| ...HTMLAttributes   | -                       | -       | Supports all HTML `div` element attributes |

## Basic Usage

```tsx
import { DelightfulScrollBar } from '@/components/base/DelightfulScrollBar';

// Basic usage
<DelightfulScrollBar style={{ maxHeight: '300px' }}>
  <div>
    {/* Lots of content */}
    {Array.from({ length: 50 }).map((_, index) => (
      <p key={index}>This is line {index + 1}</p>
    ))}
  </div>
</DelightfulScrollBar>

// Custom scrollbar options
<DelightfulScrollBar
  autoHide={false} // always show the scrollbar
  style={{ maxHeight: '300px', width: '100%' }}
>
  <div>
    {/* Content */}
  </div>
</DelightfulScrollBar>

// Using a render function
<DelightfulScrollBar>
  {({ scrollableNodeRef, scrollableNodeProps, contentNodeRef, contentNodeProps }) => (
    <div {...scrollableNodeProps}>
      <div {...contentNodeProps}>
        {/* Custom content */}
        <p>This content is rendered via the render function</p>
      </div>
    </div>
  )}
</DelightfulScrollBar>

// Inside a container
<div style={{ height: '500px', border: '1px solid #ccc' }}>
  <DelightfulScrollBar style={{ height: '100%' }}>
    <div>
      {/* Content */}
    </div>
  </DelightfulScrollBar>
</div>

// Horizontal scrolling
<DelightfulScrollBar style={{ width: '300px' }}>
  <div style={{ display: 'flex', width: '1000px' }}>
    {Array.from({ length: 20 }).map((_, index) => (
      <div
        key={index}
        style={{
          width: '100px',
          height: '100px',
          background: `hsl(${index * 20}, 70%, 70%)`,
          margin: '0 10px'
        }}
      >
        Item {index + 1}
      </div>
    ))}
  </div>
</DelightfulScrollBar>
```

## Features

1. **Attractive scrollbar**: Modern styling, more appealing than the native scrollbar
2. **Smooth scrolling**: Powered by `simplebar-core` for smooth behavior
3. **Auto-hide**: Hides the scrollbar when idle by default to reduce visual noise
4. **Cross-browser consistency**: Consistent styling and behavior across browsers
5. **Flexible rendering**: Supports both child elements and render functions

## When to Use

-   Need a visually pleasing custom scrollbar
-   Maintain consistent scrollbar styles across browsers
-   Provide a better scrolling experience
-   Control show/hide behavior of the scrollbar
-   Use scrollbars within complex layouts

The DelightfulScrollBar component makes scrollbars more attractive and user-friendly for any scrolling content scenario.

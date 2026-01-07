# DelightfulSpin Delightful Loading Component

`DelightfulSpin` is an enhanced loading component based on Ant Design's Spin, offering branded loading animations and improved style control.

## Properties

| Property     | Type    | Default | Description                           |
| ------------ | ------- | ------- | ------------------------------------- |
| section      | boolean | false   | Whether to use segmented loading      |
| ...SpinProps | -       | -       | Supports all Ant Design Spin props    |

## Basic Usage

```tsx
import { DelightfulSpin } from '@/components/base/DelightfulSpin';

// Basic usage
<DelightfulSpin spinning />

// Wrap content
<DelightfulSpin spinning>
  <div>Content loading</div>
</DelightfulSpin>

// Different sizes
<DelightfulSpin size="small" spinning />
<DelightfulSpin spinning /> {/* default size */}
<DelightfulSpin size="large" spinning />

// Segmented loading animation
<DelightfulSpin section={true} spinning />

// Center within a container
<div style={{ height: '200px', position: 'relative' }}>
  <DelightfulSpin spinning />
</div>

// With tip text
<DelightfulSpin tip="Loading..." spinning />
```

## Features

1. **Branded animations**: Uses Delightful-branded Lottie animation as the loading indicator
2. **Adaptive sizes**: Provides small, default, and large preset sizes
3. **Centered layout**: Automatically centers within its container
4. **Segmented animation**: Toggle different animation styles via the `section` prop
5. **Theme-aware**: Automatically adapts to light/dark themes

## When to Use

-   Show loading state while pages or components load
-   Provide visual feedback during data requests
-   Indicate waiting for long-running operations
-   Prevent interaction with content while loading
-   Deliver a branded loading experience

The DelightfulSpin component elevates loading state presentation with a branded, polished experience suitable for a wide range of scenarios.

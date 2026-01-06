# DelightfulSpin Magic Loading Component

`DelightfulSpin` is an enhanced loading component based on Ant Design's Spin component, providing branded loading animations and better style control.

## Properties

| Property     | Type    | Default | Description                                          |
| ------------ | ------- | ------- | ---------------------------------------------------- |
| section      | boolean | false   | Whether to use segment-style loading animation       |
| ...SpinProps | -       | -       | Support all properties of Ant Design Spin            |

## Basic Usage

```tsx
import { DelightfulSpin } from '@/components/base/DelightfulSpin';

// Basic usage
<DelightfulSpin spinning />

// Wrap content
<DelightfulSpin spinning>
  <div>Loading content</div>
</DelightfulSpin>

// Different sizes
<DelightfulSpin size="small" spinning />
<DelightfulSpin spinning /> {/* Default size */}
<DelightfulSpin size="large" spinning />

// Segment-style loading animation
<DelightfulSpin section={true} spinning />

// Center in container
<div style={{ height: '200px', position: 'relative' }}>
  <DelightfulSpin spinning />
</div>

// With tooltip text
<DelightfulSpin tip="Loading..." spinning />
```

## Features

1. **Branded animation**: Uses Delightful brand Lottie animation as loading indicator
2. **Adaptive sizes**: Provides small, medium, and large preset sizes
3. **Center layout**: Automatically centers in container
4. **Segment-style animation**: Switch different animation styles through `section` property
5. **Theme adaptation**: Automatically adapts to light/dark theme

## When to Use

-   Display loading state when page or component is loading
-   Provide visual feedback during data requests
-   Provide wait prompts during long operations
-   When you need to prevent user interaction with loading content
-   When you need branded loading experience

DelightfulSpin component makes your loading state display more beautiful and branded, suitable for any scenario that requires loading prompts.

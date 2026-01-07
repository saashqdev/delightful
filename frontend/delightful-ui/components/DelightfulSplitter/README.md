# DelightfulSplitter Delightful Split Panel Component

`DelightfulSplitter` is an enhanced split panel based on Ant Design's Splitter component, providing a cleaner style and better user experience.

## Properties

| Property         | Type | Default | Description                                      |
| ---------------- | ---- | ------- | ------------------------------------------------ |
| ...SplitterProps | -    | -       | Support all properties of Ant Design Splitter    |

## Sub-components

-   `DelightfulSplitter.Panel` - Sub-panel component of the splitter, used to define each resizable area

## Basic Usage

```tsx
import { DelightfulSplitter } from '@/components/base/DelightfulSplitter';

// Basic usage - horizontal split
<DelightfulSplitter>
  <DelightfulSplitter.Panel>
    <div>Left panel content</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Right panel content</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Vertical split
<DelightfulSplitter split="horizontal">
  <DelightfulSplitter.Panel>
    <div>Top panel content</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Bottom panel content</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Set default size
<DelightfulSplitter defaultSizes={[30, 70]}>
  <DelightfulSplitter.Panel>
    <div>Left panel content (30%)</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Right panel content (70%)</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Multiple panels
<DelightfulSplitter>
  <DelightfulSplitter.Panel>
    <div>First panel</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Second panel</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Third panel</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Nested usage
<DelightfulSplitter>
  <DelightfulSplitter.Panel>
    <div>Left panel</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <DelightfulSplitter split="horizontal">
      <DelightfulSplitter.Panel>
        <div>Top-right panel</div>
      </DelightfulSplitter.Panel>
      <DelightfulSplitter.Panel>
        <div>Bottom-right panel</div>
      </DelightfulSplitter.Panel>
    </DelightfulSplitter>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>
```

## Features

1. **Clean design**: Removes default drag handle styles for a cleaner visual effect
2. **Seamless dragging**: No obvious visual interference when dragging the divider
3. **Flexible layout**: Supports horizontal and vertical splitting, as well as nested usage
4. **Zero padding**: Panels have no padding by default, allowing content to make full use of space

## When to Use

-   When you need to create a resizable layout
-   When you need to split the screen into multiple interactive areas
-   When you need to implement interfaces like code editors, file browsers that require flexible space adjustment
-   When you want users to be able to customize the size of each area

DelightfulSplitter component makes your split panels cleaner and more user-friendly, while maintaining all the functionality of Ant Design Splitter.

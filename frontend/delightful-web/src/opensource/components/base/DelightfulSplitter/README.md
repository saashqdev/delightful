# DelightfulSplitter — Enhanced Split Panel Component

`DelightfulSplitter` is an enhanced split panel built on Ant Design's Splitter component, providing a cleaner look and better user experience.

## Props

| Prop             | Type | Default | Description                            |
| ---------------- | ---- | ------- | -------------------------------------- |
| ...SplitterProps | -    | -       | Supports all Ant Design Splitter props |

## Subcomponents

-   `DelightfulSplitter.Panel` - Child panel for defining each resizable area

## Basic Usage

```tsx
import { DelightfulSplitter } from '@/components/base/DelightfulSplitter';

// Basic usage — vertical split
<DelightfulSplitter>
  <DelightfulSplitter.Panel>
    <div>Left panel content</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Right panel content</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Horizontal split
<DelightfulSplitter split="horizontal">
  <DelightfulSplitter.Panel>
    <div>Top panel content</div>
  </DelightfulSplitter.Panel>
  <DelightfulSplitter.Panel>
    <div>Bottom panel content</div>
  </DelightfulSplitter.Panel>
</DelightfulSplitter>

// Set default sizes
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

1. **Minimal design**: Removes default handle styling for a clean look
2. **Seamless dragging**: Minimal visual distraction while dragging dividers
3. **Flexible layout**: Supports vertical and horizontal splits and nesting
4. **Zero padding**: Panels have no default padding to maximize content area

## When to Use

-   Create resizable layouts
-   Split the screen into multiple interactive areas
-   Build UIs like code editors or file explorers needing flexible space
-   Let users customize the size of each area

The `DelightfulSplitter` component keeps split panes clean and user-friendly while retaining all features of Ant Design's Splitter.

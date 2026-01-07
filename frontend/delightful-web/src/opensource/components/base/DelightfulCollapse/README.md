# DelightfulCollapse — Enhanced Collapse Panel Component

`DelightfulCollapse` is an enhanced Collapse panel built on Ant Design's Collapse, offering cleaner aesthetics and a better user experience.

## Props

| Prop             | Type | Default | Description                         |
| ---------------- | ---- | ------- | ----------------------------------- |
| ...CollapseProps | -    | -       | Supports all Ant Design Collapse props |

## Basic Usage

```tsx
import { DelightfulCollapse } from '@/components/base/DelightfulCollapse';
import { Collapse } from 'antd';

const { Panel } = Collapse;

// Basic usage
<DelightfulCollapse>
  <Panel header="Panel Title 1" key="1">
    <p>Panel content 1</p>
  </Panel>
  <Panel header="Panel Title 2" key="2">
    <p>Panel content 2</p>
  </Panel>
  <Panel header="Panel Title 3" key="3">
    <p>Panel content 3</p>
  </Panel>
</DelightfulCollapse>

// Default expand specific panel
<DelightfulCollapse defaultActiveKey={['1']}>
  <Panel header="Default expanded panel" key="1">
    <p>This is the default expanded panel content</p>
  </Panel>
  <Panel header="Default collapsed panel" key="2">
    <p>This is the default collapsed panel content</p>
  </Panel>
</DelightfulCollapse>

// Accordion mode (only one panel at a time)
<DelightfulCollapse accordion>
  <Panel header="Accordion panel 1" key="1">
    <p>Accordion panel content 1</p>
  </Panel>
  <Panel header="Accordion panel 2" key="2">
    <p>Accordion panel content 2</p>
  </Panel>
</DelightfulCollapse>

// Listen to expand/collapse events
<DelightfulCollapse onChange={(key) => console.log('Currently expanded panels:', key)}>
  <Panel header="Panel 1" key="1">
    <p>Panel content 1</p>
  </Panel>
  <Panel header="Panel 2" key="2">
    <p>Panel content 2</p>
  </Panel>
</DelightfulCollapse>
```

## Features

1. **Optimized style**: Uses ghost mode to remove borders for a cleaner look
2. **Custom expand icon**: Uses the `DelightfulIcon` component for consistent visuals
3. **Smooth animations**: Rotation animation on expand/collapse
4. **Flexible layout**: Expand icon on the right, aligned with modern design

## When to Use

- Group complex content for easier presentation
- Save page space by collapsing content
- Create accordion effects (only one panel open at a time)
- Categorize information and allow users to view on demand

The `DelightfulCollapse` component makes your collapse panels more elegant and user-friendly while retaining all features of Ant Design's Collapse.

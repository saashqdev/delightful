# DelightfulCollapse Magic Collapse Panel Component

`DelightfulCollapse` is an enhanced collapse panel based on Ant Design's Collapse component, providing more aesthetically pleasing styles and better user experience.

## Properties

| Property          | Type | Default | Description                                      |
| ----------------- | ---- | ------- | ------------------------------------------------ |
| ...CollapseProps  | -    | -       | Support all properties of Ant Design Collapse    |

## Basic Usage

```tsx
import { DelightfulCollapse } from '@/components/base/DelightfulCollapse';
import { Collapse } from 'antd';

const { Panel } = Collapse;

// Basic usage
<DelightfulCollapse>
  <Panel header="This is panel title 1" key="1">
    <p>This is panel content 1</p>
  </Panel>
  <Panel header="This is panel title 2" key="2">
    <p>This is panel content 2</p>
  </Panel>
  <Panel header="This is panel title 3" key="3">
    <p>This is panel content 3</p>
  </Panel>
</DelightfulCollapse>

// Default expand specified panel
<DelightfulCollapse defaultActiveKey={['1']}>
  <Panel header="Default expanded panel" key="1">
    <p>This is default expanded panel content</p>
  </Panel>
  <Panel header="Default collapsed panel" key="2">
    <p>This is default collapsed panel content</p>
  </Panel>
</DelightfulCollapse>

// Accordion mode (only one panel can be expanded at a time)
<DelightfulCollapse accordion>
  <Panel header="Accordion panel 1" key="1">
    <p>Accordion panel content 1</p>
  </Panel>
  <Panel header="Accordion panel 2" key="2">
    <p>Accordion panel content 2</p>
  </Panel>
</DelightfulCollapse>

// Listen to expand/collapse events
<DelightfulCollapse onChange={(key) => console.log('Currently expanded panel:', key)}>
  <Panel header="Monitored panel 1" key="1">
    <p>Panel content 1</p>
  </Panel>
  <Panel header="Monitored panel 2" key="2">
    <p>Panel content 2</p>
  </Panel>
</DelightfulCollapse>
```

## Features

1. **Optimized styles**: Uses ghost mode to remove borders for a cleaner, more beautiful look
2. **Custom expand icon**: Uses DelightfulIcon component as the expand icon for visual consistency
3. **Smooth animations**: Smooth rotation animation effect when expanding/collapsing
4. **Flexible layout**: Expand icon positioned on the right, following modern design trends

## When to Use

-   When you need to group and display complex content
-   When you need to save page space by collapsing content
-   When you want to create an accordion effect (only one panel expanded at a time)
-   When you need to categorize information and allow users to view it on demand

DelightfulCollapse component makes your collapse panels more beautiful and user-friendly, while maintaining all the functionality of Ant Design Collapse.

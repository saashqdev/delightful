# DelightfulEllipseWithTooltip Delightful Ellipsis Tooltip Component

`DelightfulEllipseWithTooltip` is an intelligent text ellipsis component that automatically displays ellipsis when text exceeds a specified width, and shows the full text via tooltip on mouse hover.

## Properties

| Property          | Type   | Default | Description                                                   |
| ----------------- | ------ | ------- | ------------------------------------------------------------- |
| text              | string | -       | The text content to be displayed                              |
| maxWidth          | string | -       | Maximum text width, excess replaced with ellipsis, e.g. "200px", "50%" |
| ...HTMLAttributes | -      | -       | All properties of HTML div element are supported              |

## Basic Usage

```tsx
import { DelightfulEllipseWithTooltip } from '@/components/base/DelightfulEllipseWithTooltip';

// Basic usage
<DelightfulEllipseWithTooltip
  text="This is a long text, when it exceeds the specified width it will display an ellipsis, and hovering the mouse will display the full content"
  maxWidth="200px"
/>

// Custom styles
<DelightfulEllipseWithTooltip
  text="Ellipsis text with custom styles"
  maxWidth="150px"
  style={{
    color: 'blue',
    fontSize: '16px',
    fontWeight: 'bold'
  }}
/>

// Use in table cells
<Table
  columns={[
    {
      title: 'Description',
      dataIndex: 'description',
      render: (text) => (
        <DelightfulEllipseWithTooltip
          text={text}
          maxWidth="150px"
        />
      ),
    },
    // Other columns...
  ]}
  dataSource={data}
/>

// Use in list items
<List
  dataSource={data}
  renderItem={(item) => (
    <List.Item>
      <DelightfulEllipseWithTooltip
        text={item.title}
        maxWidth="100%"
      />
    </List.Item>
  )}
/>

// Handle events
<DelightfulEllipseWithTooltip
  text="Click me to trigger an event"
  maxWidth="120px"
  onClick={() => console.log('Text was clicked')}
/>
```

## Features

1. **Intelligent Detection**: Tooltip is only displayed when text actually overflows
2. **Clean Design**: Uses single-line ellipsis to keep the interface neat and clean
3. **Flexible Configuration**: Can set maximum width to adapt to various layout needs
4. **Fully Customizable**: Supports all properties and styles of div elements

## When to Use

-   When you need to display potentially long text in limited space
-   When you need to keep the interface clean while not losing information
-   When displaying titles or descriptions in tables, lists, cards, and other components
-   When you need to ensure users can view the full content of truncated text

The DelightfulEllipseWithTooltip component makes your long text display more elegant and user-friendly, while maintaining interface cleanliness and information completeness.

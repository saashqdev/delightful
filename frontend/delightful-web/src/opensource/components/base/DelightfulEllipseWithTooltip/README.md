# DelightfulEllipseWithTooltip — Smart Text Ellipsis with Tooltip

`DelightfulEllipseWithTooltip` is an intelligent text ellipsis component that automatically shows ellipsis when text exceeds the specified width and displays the full text in a tooltip on hover.

## Props

| Prop              | Type   | Default | Description                                                          |
| ----------------- | ------ | ------- | -------------------------------------------------------------------- |
| text              | string | -       | Text content to display                                              |
| maxWidth          | string | -       | Maximum width; overflow replaced with ellipsis, e.g., "200px", "50%" |
| ...HTMLAttributes | -      | -       | Supports all HTML `div` element attributes                           |

## Basic Usage

```tsx
import { DelightfulEllipseWithTooltip } from '@/components/base/DelightfulEllipseWithTooltip';

// Basic usage
<DelightfulEllipseWithTooltip
  text="This is a long text that will show an ellipsis when it exceeds the specified width. Hover to see the full content."
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
  text="Click me to trigger event"
  maxWidth="120px"
  onClick={() => console.log('Text was clicked')}
/>
```

## Features

1. **Smart detection**: Tooltip only shows when text actually overflows
2. **Minimal design**: Single-line ellipsis keeps the UI clean
3. **Flexible configuration**: Set max width to fit any layout
4. **Fully customizable**: Supports all `div` element props and styles

## When to Use

-   Display potentially long text in limited space
-   Keep the UI clean while preserving information
-   Show titles or descriptions in tables, lists, cards, etc.
-   Ensure users can view the full text content when truncated

The `DelightfulEllipseWithTooltip` component makes long text display elegant and user-friendly, maintaining a clean interface while ensuring complete information.

# DelightfulMindmap — Interactive Mind Map Component

DelightfulMindmap renders and enables interactive manipulation of mind maps. Built on the MindMap library, it supports display, zooming, and adaptive layout for an intuitive visualization experience.

## Props

| Prop     | Type    | Default | Description                                        |
| -------- | ------- | ------- | -------------------------------------------------- |
| data     | object  | -       | Mind map data structure with nodes and connections |
| readonly | boolean | false   | Readonly mode; when true, users cannot edit        |

Additionally, supports other HTML `div` element attributes.

## Basic Usage

```tsx
import DelightfulMindmap from '@/components/base/DelightfulMindmap';

// Basic usage
const mindmapData = {
  id: 'root',
  topic: 'Central Topic',
  children: [
    {
      id: 'sub1',
      topic: 'Subtopic 1',
      children: [
        { id: 'sub1-1', topic: 'Subtopic 1-1' },
        { id: 'sub1-2', topic: 'Subtopic 1-2' }
      ]
    },
    {
      id: 'sub2',
      topic: 'Subtopic 2',
      children: [
        { id: 'sub2-1', topic: 'Subtopic 2-1' }
      ]
    }
  ]
};

<DelightfulMindmap data={mindmapData} />

// Readonly mode
<DelightfulMindmap data={mindmapData} readonly={true} />

// Custom styles
<DelightfulMindmap
  data={mindmapData}
  className="custom-mindmap"
  style={{ height: '500px', border: '1px solid #eee' }}
/>
```

## Data Structure

The mind map data structure follows this format:

```typescript
interface MindMapNode {
	id: string // Unique node identifier
	topic: string // Node display text
	children?: MindMapNode[] // Child nodes
	style?: object // Optional node style
	expanded?: boolean // Whether child nodes are expanded
	// Other optional properties...
}
```

## Features

-   **Interactive operations**: Zooming, dragging, and node expand/collapse
-   **Adaptive layout**: Automatically adjusts node positions for optimal visuals
-   **Responsive design**: Adapts to container size changes
-   **Readonly mode**: Ideal for presentation-only scenarios
-   **Custom styling**: Customize the appearance via CSS

## Use Cases

-   Visualizing knowledge structures
-   Presenting project plans and task breakdowns
-   Organizing and relating concepts and ideas
-   Structuring learning content
-   Any scenario requiring hierarchical and relational display

The `DelightfulMindmap` component offers an intuitive and powerful way to display and manipulate mind maps, helping users better understand and organize information.

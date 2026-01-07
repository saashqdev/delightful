# DelightfulMarkmap Enhanced Mind Map Component

`DelightfulMarkmap` is a component for rendering and displaying mind maps, implemented based on the Markmap library, supporting conversion of Markdown-formatted text into interactive mind maps.

## 属性

| Property | Type    | Default | Description                          |
| -------- | ------- | ------- | ------------------------------------ |
| content  | string  | -       | Mind map content in Markdown format  |
| readonly | boolean | false   | Whether in read-only mode, no editing allowed |
| ...rest  | -       | -       | Support passing other HTML attributes to container |

## Basic Usage

```tsx
import { DelightfulMarkmap } from '@/components/base/DelightfulMarkmap';

// Basic usage
const markdownContent = `
# Project Plan
## Phase One
### Requirements Analysis
### Prototype Design
## Phase Two
### Development
### Testing
## Phase Three
### Deployment
### Maintenance
`;

<DelightfulMarkmap content={markdownContent} />

// Read-only mode
<DelightfulMarkmap content={markdownContent} readonly />

// Custom styles
<DelightfulMarkmap
  content={markdownContent}
  style={{ height: '500px', width: '100%' }}
/>
```

## Mind Map Format

DelightfulMarkmap uses Markdown heading hierarchy to define mind map node hierarchy:

```markdown
# Root Node

## Second Level Node 1

### Third Level Node 1-1

### Third Level Node 1-2

## Second Level Node 2

### Third Level Node 2-1

#### Fourth Level Node 2-1-1
```

Each heading becomes a node in the mind map. The heading level determines the node's position in the hierarchy.

## Features

1. **Markdown Support**: Create mind maps using familiar Markdown syntax
2. **Interactive Experience**: Support zooming, panning, and collapsing/expanding nodes
3. **Auto Layout**: Automatically calculates node positions and connections, no manual formatting needed
4. **Responsive Design**: Automatically adapts to container size
5. **Lightweight**: Fast loading and excellent performance

## When to Use

-   When you need to display hierarchical information
-   When you need to visualize project plans or organizational structures
-   When you need to display knowledge systems or concept relationships
-   When you need to convert Markdown documents to mind maps
-   When you need to embed interactive mind maps in conversations or documents

DelightfulMarkmap makes mind map creation and display simple and efficient, making it an ideal choice for presenting structured information.

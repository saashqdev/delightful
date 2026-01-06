# ReactFlow Helper Lines

The React Flow helper lines component provides alignment guides to help users align nodes precisely while dragging.

## Features

- Show alignment guides while dragging nodes
- Optional node snapping for precise alignment
- Horizontal and vertical alignment
- Multiple alignment modes: left, right, center, top, and bottom
- Respects viewport zoom and pan
- Rich customization options
- Toggle via control panel button or keyboard shortcut

## Usage

### Basic Usage

```tsx
import { ReactFlow, useViewport } from 'reactflow';
import { HelperLines, useHelperLines } from '@/DelightfulFlow/components/HelperLines';

function FlowComponent() {
  const { x, y, zoom } = useViewport();
  const [helperLinesEnabled, setHelperLinesEnabled] = useState(false);
  
  const {
    horizontalLines,
    verticalLines,
    handleNodeDragStart,
    handleNodeDrag,
    handleNodeDragStop,
    hasHelperLines
  } = useHelperLines({
    nodes,
    onNodeDragStart,
    onNodeDrag,
    onNodeDragStop,
    onNodesChange, // Required if you need node snapping
    enabled: helperLinesEnabled, // Toggle helper lines on/off
  });

  return (
    <>
      {/* Add a toggle button */}
      <button onClick={() => setHelperLinesEnabled(!helperLinesEnabled)}>
        {helperLinesEnabled ? 'Disable helper lines' : 'Enable helper lines'}
      </button>
      
      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodeDragStart={handleNodeDragStart}
        onNodeDrag={handleNodeDrag}
        onNodeDragStop={handleNodeDragStop}
        onNodesChange={onNodesChange} // Required for snap-to-align
        {...otherProps}
      >
        {/* Other components */}
        
        {/* Render helper lines */}
        {hasHelperLines && (
          <HelperLines
            horizontalLines={horizontalLines}
            verticalLines={verticalLines}
            transform={{ x, y, zoom }}
          />
        )}
      </ReactFlow>
    </>
  );
}
```

### Custom Configuration

Customize behavior and appearance via the `options` prop:

```tsx
const {
  horizontalLines,
  verticalLines,
  // ...
} = useHelperLines({
  nodes,
  onNodeDragStart,
  onNodeDrag,
  onNodeDragStop,
  onNodesChange,
  enabled: helperLinesEnabled, // Toggle with state
  options: {
    threshold: 8,        // Alignment threshold
    color: '#0077ff',    // Guide color
    lineWidth: 2,        // Guide width
    zIndex: 10000,       // z-index
    enableSnap: true     // Enable snap-to-align
  }
});
```

Then pass these options to the `HelperLines` component:

```tsx
<HelperLines
  horizontalLines={horizontalLines}
  verticalLines={verticalLines}
  transform={{ x, y, zoom }}
  color={options.color}
  lineWidth={options.lineWidth}
  zIndex={options.zIndex}
/>
```

## API Reference

### `useHelperLines` Hook

#### Parameters

`useHelperLines` accepts a configuration object with these properties:

| Property | Type | Required | Description |
| --- | --- | --- | --- |
| nodes | Node[] | Yes | React Flow nodes array |
| onNodeDragStart | Function | No | Original node drag start callback |
| onNodeDrag | Function | No | Original node drag callback |
| onNodeDragStop | Function | No | Original node drag end callback |
| onNodesChange | Function | No | Nodes change callback, required for snapping |
| options | HelperLinesOptions | No | Helper lines configuration |
| enabled | boolean | No | Whether helper lines are enabled, default false |

#### Returns

| Property | Type | Description |
| --- | --- | --- |
| horizontalLines | number[] | Positions of horizontal helper lines |
| verticalLines | number[] | Positions of vertical helper lines |
| handleNodeDragStart | Function | Node drag start handler |
| handleNodeDrag | Function | Node drag handler |
| handleNodeDragStop | Function | Node drag end handler |
| hasHelperLines | boolean | Whether helper lines should render |
| options | Object | Current configuration in use |
| enabled | boolean | Whether helper lines are enabled |

### `HelperLines` Component

#### Props

| Property | Type | Required | Default | Description |
| --- | --- | --- | --- | --- |
| horizontalLines | number[] | Yes | - | Positions of horizontal helper lines |
| verticalLines | number[] | Yes | - | Positions of vertical helper lines |
| transform | ViewportTransform | Yes | - | Viewport transform info |
| color | string | No | '#ff0071' | Helper line color |
| lineWidth | number | No | 1 | Helper line width |
| zIndex | number | No | 9999 | z-index for helper lines |

### Type Definitions

```typescript
interface ViewportTransform {
  x: number;
  y: number;
  zoom: number;
}

interface HelperLinesOptions {
  threshold?: number;
  color?: string;
  lineWidth?: number;
  zIndex?: number;
  enableSnap?: boolean;
}
```

## Integrate in a Control Panel

Add a toggle button to the flow control panel to enable or disable helper lines:

```tsx
// Add a helper-lines toggle to the control items
const controlItemGroups = [
  // ...other control groups
  [
    {
      icon: helperLinesEnabled ? (
        <IconRuler stroke={1} color="#FF7D00" />
      ) : (
        <IconRuler stroke={1} />
      ),
      callback: () => setHelperLinesEnabled(!helperLinesEnabled),
      tooltips: `${helperLinesEnabled ? 'Disable' : 'Enable'} helper lines (Ctrl+H)`,
      helperLinesEnabled,
    },
  ],
];

// Add keyboard shortcut support
useEffect(() => {
  const handleKeyDown = (event) => {
    // Ctrl+H or Command+H toggles helper lines
    if ((event.ctrlKey || event.metaKey) && event.key === 'h') {
      event.preventDefault();
      setHelperLinesEnabled(!helperLinesEnabled);
    }
  };
  
  document.addEventListener('keydown', handleKeyDown);
  return () => {
    document.removeEventListener('keydown', handleKeyDown);
  };
}, [helperLinesEnabled, setHelperLinesEnabled]);
```

## How It Works

Core logic for helper lines:

1. Listen for node drag events.
2. During drag, compute positions between the active node and other nodes.
3. When edges or centers are within the threshold, show alignment guides.
4. Render guides with absolutely positioned elements, accounting for zoom and pan.

Snap-to-align logic:

1. When a node approaches an alignment target, compute precise aligned coordinates.
2. Update node position through `onNodesChange` so the node snaps into place.
3. If multiple alignment candidates exist, choose the closest guide to the drag position.

Enable/disable behavior:

1. The `helperLinesEnabled` state toggles the feature.
2. When disabled, drag events pass through without helper-line calculations.
3. The control panel button and keyboard shortcut toggle the flag.
4. Helper lines render only when enabled and alignment targets exist.

Alignment checks cover:

- Horizontal
  - Top: align top edges
  - Bottom: align bottom edges
  - Center: align vertical center lines

- Vertical
  - Left: align left edges
  - Right: align right edges
  - Center: align horizontal center lines

## Example

### Full Example with Toggle

```tsx
import React, { useState, useCallback, useEffect } from 'react';
import ReactFlow, { 
  addEdge, 
  Background, 
  Controls, 
  useNodesState, 
  useEdgesState, 
  useViewport 
} from 'reactflow';
import { HelperLines, useHelperLines } from '@/DelightfulFlow/components/HelperLines';
import { IconRuler } from '@tabler/icons-react';
import 'reactflow/dist/style.css';

const initialNodes = [
  {
    id: '1',
    type: 'default',
    data: { label: 'Node 1' },
    position: { x: 250, y: 5 },
  },
  // ... other nodes
];

const initialEdges = [
  // ... edge definitions
];

function Flow() {
  const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes);
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);
  const { x, y, zoom } = useViewport();
  const [helperLinesEnabled, setHelperLinesEnabled] = useState(false);

  const onConnect = useCallback(
    (params) => setEdges((eds) => addEdge(params, eds)),
    [setEdges],
  );

  // Use the helper lines hook
  const {
    horizontalLines,
    verticalLines,
    handleNodeDragStart,
    handleNodeDrag,
    handleNodeDragStop,
    hasHelperLines,
  } = useHelperLines({
    nodes,
    onNodesChange,
    enabled: helperLinesEnabled,
    options: {
      threshold: 8,
      color: '#ff0071',
      enableSnap: true
    }
  });
  
  // Add keyboard shortcut support
  useEffect(() => {
    const handleKeyDown = (event) => {
      // Ctrl+H or Command+H toggles helper lines
      if ((event.ctrlKey || event.metaKey) && event.key === 'h') {
        event.preventDefault();
        setHelperLinesEnabled(!helperLinesEnabled);
      }
    };
    
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [helperLinesEnabled]);

  return (
    <div style={{ height: '100%' }}>
      {/* Control panel */}
      <div style={{ position: 'absolute', top: 10, right: 10, zIndex: 10 }}>
        <button
          onClick={() => setHelperLinesEnabled(!helperLinesEnabled)}
          style={{
            background: helperLinesEnabled ? '#ff7d00' : '#ffffff',
            color: helperLinesEnabled ? '#ffffff' : '#000000',
            border: '1px solid #ddd',
            borderRadius: '4px',
            padding: '8px',
            cursor: 'pointer',
          }}
          title={`${helperLinesEnabled ? 'Disable' : 'Enable'} helper lines (Ctrl+H)`}
        >
          <IconRuler size={20} stroke={1.5} />
        </button>
      </div>
      
      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeDragStart={handleNodeDragStart}
        onNodeDrag={handleNodeDrag}
        onNodeDragStop={handleNodeDragStop}
        fitView
      >
        <Background />
        <Controls />
        
        {/* Render helper lines */}
        {hasHelperLines && (
          <HelperLines
            horizontalLines={horizontalLines}
            verticalLines={verticalLines}
            transform={{ x, y, zoom }}
            color="#ff0071"
            lineWidth={1}
          />
        )}
      </ReactFlow>
    </div>
  );
} 
# DelightfulMermaid — Mermaid Diagram Renderer

`DelightfulMermaid` is a Mermaid.js-based renderer that converts Mermaid syntax into visual diagrams like flowcharts, sequence diagrams, and Gantt charts.

## Props

| Prop   | Type   | Default | Description                  |
| ------ | ------ | ------- | ---------------------------- |
| data   | string | -       | Diagram definition in Mermaid syntax |

## Basic Usage

```tsx
import { DelightfulMermaid } from '@/components/base/DelightfulMermaid';

// Basic flowchart
<DelightfulMermaid
  data={`
    graph TD
    A[Start] --> B{Decision}
    B -->|Yes| C[Process]
    B -->|No| D[End]
    C --> D
  `}
/>

// Sequence diagram
<DelightfulMermaid
  data={`
    sequenceDiagram
    ParticipantA->>ParticipantB: Hello, B!
    ParticipantB->>ParticipantA: Hello, A!
  `}
/>

// Gantt chart
<DelightfulMermaid
  data={`
    gantt
    title Project Plan
    dateFormat  YYYY-MM-DD
    section Phase 1
    Task 1           :a1, 2023-01-01, 30d
    Task 2           :after a1, 20d
    section Phase 2
    Task 3           :2023-02-15, 12d
    Task 4           :24d
  `}
/>

// Class diagram
<DelightfulMermaid
  data={`
    classDiagram
    ClassA <|-- ClassB
    ClassA : +String property1
    ClassA : +method1()
    ClassB : +method2()
  `}
/>

// State diagram
<DelightfulMermaid
  data={`
    stateDiagram-v2
    [*] --> State1
    State1 --> State2: Trigger
    State2 --> [*]
  `}
/>
```

## Features

1. **Dual viewing modes**: Toggle between diagram and code for easy viewing and editing
2. **Theme support**: Auto adapts to light/dark themes for consistent visuals
3. **Click-to-preview**: Click diagrams to preview fullscreen with more detail
4. **Error handling**: Friendly error messages for invalid Mermaid syntax
5. **SVG export**: Export diagrams as SVG for sharing and reuse

## When to Use

- Visualize flows, relationships, or sequences
- Embed flowcharts, sequence diagrams, and Gantt charts in documentation
- Convert textual descriptions into intuitive graphical representations
- Display complex diagrams in Markdown content
- Create interactive diagram presentations

The `DelightfulMermaid` component makes visualizations like flow and relationship diagrams intuitive and professional, suitable for any chart visualization scenario.

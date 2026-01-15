# DelightfulPdfRender Architecture Design

## Overview

The DelightfulPdfRender component has been refactored according to SOLID principles and component best practices, splitting the original 697-line monolithic component into multiple single-responsibility, reusable modules.

## Architecture Diagram

```
DelightfulPdfRender/
├── index.tsx                    # Main component (164 lines)
├── types.ts                     # Type definitions
├── styles.ts                    # Style definitions
├── hooks/                       # Custom Hooks
│   ├── usePdfState.ts          # PDF state management (51 lines)
│   ├── usePdfActions.ts        # PDF action logic (227 lines)
│   ├── useKeyboardControls.ts  # Keyboard event handling (65 lines)
│   ├── useContainerSize.ts     # Container size listener (40 lines)
│   └── useScrollListener.ts    # Scroll listener (52 lines)
└── components/                  # Sub-components
    ├── Toolbar/                # Toolbar component
    ├── PageNavigation/         # Page navigation component
    ├── ZoomControls/          # Zoom control component
    ├── ActionDropdown/        # Action dropdown menu component
    └── PdfViewer/            # PDF viewer component
```

## Design Principles

### 1. Single Responsibility Principle (SRP)

-   Each hook is responsible for only one specific function
-   Each component handles only one UI module
-   Main component is only responsible for composition and coordination

### 2. Open/Closed Principle (OCP)

-   Extend functionality through props interface
-   Hooks can be independently extended and modified
-   Components support style and behavior customization

### 3. Dependency Inversion Principle (DIP)

-   Main component depends on abstract hook interfaces
-   Sub-components receive dependencies through props
-   Loosely coupled module design

### 4. Separation of Concerns

-   State management separated from UI rendering
-   Business logic separated from interaction logic
-   Styles separated from component logic

## Module Details

### Custom Hooks

#### `usePdfState.ts`

Responsible for core PDF document state management:

-   Page number, zoom, rotation state
-   Loading state and error state
-   State reset on file changes

#### `usePdfActions.ts`

Contains all PDF operation business logic:

-   Page navigation (previous, next, jump to)
-   Zoom control (zoom in, zoom out, reset)
-   Rotation control (clockwise, counterclockwise)
-   Document operations (reload, download, fullscreen)
-   Event handlers (load success/failure)

#### `useKeyboardControls.ts`

Handles keyboard shortcuts:

-   Arrow key navigation
-   Zoom shortcuts
-   Fullscreen toggle
-   Input field conflict avoidance

#### `useContainerSize.ts`

Monitors container size changes:

-   ResizeObserver implementation
-   Responsive layout detection
-   Compact mode switching

#### `useScrollListener.ts`

Scroll position listener:

-   Automatically update current page number
-   Viewport center detection
-   Smooth scrolling for page switching

### Sub-components

#### `Toolbar/index.tsx`

Main toolbar component:

-   Responsive layout (wide/compact mode)
-   Integrates all sub-controls
-   Unified styles and interactions

#### `PageNavigation/index.tsx`

Page navigation control:

-   Previous/next page buttons
-   Page number input field
-   Total page count display

#### `ZoomControls/index.tsx`

Zoom control component:

-   Zoom in/out buttons
-   Zoom ratio input
-   Percentage formatting

#### `ActionDropdown/index.tsx`

Action dropdown menu:

-   Compact mode only
-   All features integrated
-   Smart toggle control

#### `PdfViewer/index.tsx`

PDF document viewer:

-   Document rendering logic
-   Page lazy loading
-   Error state handling

## Advantages Analysis

### 1. Improved Maintainability

-   **Reduced code lines**: Main component reduced from 697 lines to 164 lines
-   **Clear responsibilities**: Each module has clear functions, easy to understand and modify
-   **Error isolation**: Issues can be quickly located to specific modules

### 2. Enhanced Reusability

-   **Hook reuse**: Custom hooks can be reused in other components
-   **Component reuse**: Sub-components can be used independently or in combination
-   **Logic reuse**: Business logic separated from UI, facilitating cross-component reuse

### 3. Improved Testability

-   **Unit testing**: Each hook and component can be tested independently
-   **Simplified mocking**: Dependency injection makes mocking simpler
-   **Test coverage**: Smaller modules make it easier to achieve high test coverage

### 4. Increased Development Efficiency

-   **Parallel development**: Teams can develop different modules simultaneously
-   **Debugging convenience**: Problem scope narrowed, debugging more efficient
-   **Hot reload**: Module-level modifications have smaller impact scope

### 5. Performance Optimization

-   **On-demand rendering**: Only changed modules re-render
-   **Lazy loading**: PDF pages loaded on demand
-   **Memory optimization**: More fine-grained state management

## Usage Example

```tsx
import DelightfulPdfRender from "./DelightfulPdfRender"

// Basic usage (fully functional)
;<DelightfulPdfRender file="path/to/document.pdf" height="800px" initialScale={1.2} />

// Can also use sub-components independently
import { usePdfState, usePdfActions } from "./DelightfulPdfRender/hooks"
import Toolbar from "./DelightfulPdfRender/components/Toolbar"

function CustomPdfViewer() {
	const pdfState = usePdfState({ initialScale: 1.0, file: "test.pdf" })
	const pdfActions = usePdfActions({
		/* props */
	})

	return <Toolbar {...pdfState} {...pdfActions} />
}
```

## Migration Guide

The refactored component is **fully backward compatible**, existing usage requires no modifications:

```tsx
// Usage remains identical before and after refactoring
<DelightfulPdfRender
	file={pdfFile}
	showToolbar={true}
	height="600px"
	onLoadSuccess={handleSuccess}
	onLoadError={handleError}
/>
```

All existing props, event callbacks, and features remain unchanged, only the internal implementation is more modular and maintainable.

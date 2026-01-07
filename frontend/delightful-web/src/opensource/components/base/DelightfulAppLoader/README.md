# DelightfulAppLoader Magic Application Loader Component

`DelightfulAppLoader` is a component for loading and displaying micro-frontend applications, providing application loading state management, error handling, and loading animations.

## Properties

| Property  | Type                 | Default | Description                                                |
| --------- | -------------------- | ------- | ---------------------------------------------------------- |
| appMeta   | AppMeta              | -       | Micro-app metadata, including name, entry URL, etc.        |
| onLoad    | () => void           | -       | Callback function when application loads successfully      |
| onError   | (error: any) => void | -       | Callback function when application fails to load           |
| fallback  | ReactNode            | -       | Content to display while loading, default is loading animation |
| errorView | ReactNode            | -       | Content to display when loading fails                      |

## Basic Usage

```tsx
import { DelightfulAppLoader } from '@/components/base/DelightfulAppLoader';

// Basic usage
const appMeta = {
  name: 'my-micro-app',
  entry: 'https://example.com/micro-app/',
  basename: '/my-app'
};

<DelightfulAppLoader
  appMeta={appMeta}
  onLoad={() => console.log('Application loaded successfully')}
  onError={(error) => console.error('Application failed to load', error)}
/>

// Custom loading and error states
<DelightfulAppLoader
  appMeta={appMeta}
  fallback={<div>Loading application...</div>}
  errorView={<div>Application failed to load, please refresh and try again</div>}
/>

// Use in layout
<div style={{ width: '100%', height: '100vh' }}>
  <DelightfulAppLoader appMeta={appMeta} />
</div>
```

## Features

1. **Micro-frontend Support**: Designed specifically for loading micro-frontend applications, supports inter-app communication
2. **State Management**: Built-in application loading state management, automatically handles loading and error states
3. **Graceful Degradation**: Provides error view when loading fails, enhancing user experience
4. **Loading Animation**: Built-in loading animation providing visual feedback
5. **Sandbox Isolation**: Supports micro-app sandbox isolation, preventing style and global variable conflicts between applications

## When to Use

-   When you need to load micro-frontend applications in the main application
-   When you need to manage micro-app loading states and error handling
-   When you need to provide good user experience during application loading
-   When you need to integrate third-party applications into existing systems
-   When you need to build scalable micro-frontend architecture

The DelightfulAppLoader component simplifies the loading and management process of micro-frontend applications, provides comprehensive state handling and user experience, making it an ideal choice for building micro-frontend architecture.

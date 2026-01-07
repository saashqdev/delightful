# UploadAction Upload Action Component

UploadAction is a low-level component for handling file upload interactions. It encapsulates the core logic of file selection, provides a hidden file input, and exposes a method to trigger file selection. It can be used with custom upload buttons or drag-and-drop areas.

## Properties

| Property     | Type                                     | Default | Description                                                     |
| ------------ | ---------------------------------------- | ------- | --------------------------------------------------------------- |
| multiple     | boolean                                  | false   | Whether multiple file selection is supported                    |
| handler      | (trigger: () => void) => React.ReactNode | -       | Renders the trigger element; receives a `trigger` function      |
| onFileChange | (files: File[]) => void                  | -       | Callback after file selection; receives the selected file array |

## Basic Usage

```tsx
import UploadAction from '@/opensource/components/base/UploadAction';

// Basic usage — custom button triggers upload
const handleFileChange = (files: File[]) => {
  console.log('Selected files:', files);
  // Handle file upload logic
};

<UploadAction
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <button onClick={trigger}>Select Files</button>
  )}
/>

// Supports multiple file uploads
<UploadAction
  multiple
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <button onClick={trigger}>Select Multiple Files</button>
  )}
/>

// Use with other components
import { Button } from 'antd';

<UploadAction
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <Button type="primary" onClick={trigger}>
      Upload Files
    </Button>
  )}
/>
```

## Features

-   **Flexible trigger**: Customize the upload trigger element via the `handler` prop
-   **Hidden native input**: Hides the unstyled native file input
-   **Multiple files**: Enable multiple selection via the `multiple` prop
-   **Simplified handling**: Automatically handles selection and passes files via callback
-   **Reusable**: Suitable as a foundational block across different upload scenarios

## Use Cases

-   Implementing custom upload buttons
-   File selection in drag-and-drop upload areas
-   Upload UIs that hide the native file input
-   As a foundational building block for more complex upload components
-   Any interaction requiring file selection

The UploadAction component focuses on the core logic of file selection without styles or visuals, allowing flexible integration with custom UI elements and providing a consistent file upload experience.

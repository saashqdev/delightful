# UploadAction Upload Action Component

UploadAction is a low-level component for handling file upload interactions. It encapsulates the core logic of file selection, provides a hidden file input box and a method to trigger file selection, and can be used with various custom upload buttons or drag-and-drop areas.

## Properties

| Property Name | Type                                     | Default | Description                                                      |
| ------------- | ---------------------------------------- | ------- | ---------------------------------------------------------------- |
| multiple      | boolean                                  | false   | Whether to support multi-file selection                          |
| handler       | (trigger: () => void) => React.ReactNode | -       | Used to render the element that triggers upload, receives a trigger function as a parameter |
| onFileChange  | (files: File[]) => void                  | -       | Callback function after file selection, receives array of selected files |

## Basic Usage

```tsx
import UploadAction from '@/opensource/components/base/UploadAction';

// Basic usage - custom button to trigger upload
const handleFileChange = (files: File[]) => {
  console.log('Selected files:', files);
  // Handle file upload logic
};

<UploadAction
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <button onClick={trigger}>Select File</button>
  )}
/>

// Support for multi-file upload
<UploadAction
  multiple
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <button onClick={trigger}>Select Multiple Files</button>
  )}
/>

// Use in combination with other components
import { Button } from 'antd';

<UploadAction
  onFileChange={handleFileChange}
  handler={(trigger) => (
    <Button type="primary" onClick={trigger}>
      Upload File
    </Button>
  )}
/>
```

## Features

-   **Flexible trigger method**: Customize the element that triggers file upload through the handler property
-   **Hide native file input**: Hides the unsightly native file input box
-   **Multi-file support**: Multi-file selection can be enabled through the multiple property
-   **Simplified file handling**: Automatically handles file selection events and provides selected files through callback functions
-   **Reusability**: Can be reused in different upload scenarios

## Use Cases

-   Implementation of custom upload buttons
-   File selection functionality for drag-and-drop upload areas
-   Upload interface that requires hiding the native file input box
-   Basic building block for more complex upload components
-   Any interactive scenario that requires file selection functionality

UploadAction component focuses on the core logic of file selection, without style and visual elements, which makes it flexible to use with various custom interface elements to provide a consistent file upload experience for the application.

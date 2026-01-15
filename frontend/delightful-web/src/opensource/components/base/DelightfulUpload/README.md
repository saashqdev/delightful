# DelightfulUpload Delightful Upload Component

`DelightfulUpload` is a file upload component that provides straightforward upload functionality. It supports single and multiple file uploads and can be integrated with other components like buttons or drag-and-drop areas.

## Properties

| Property     | Type                      | Default | Description                                 |
| ------------ | ------------------------- | ------- | ------------------------------------------- |
| multiple     | boolean                   | false   | Whether multiple file uploads are supported |
| onFileChange | (files: FileList) => void | -       | Callback after files are selected           |
| children     | ReactNode                 | -       | Content for a custom upload button or area  |
| accept       | string                    | -       | Accepted file types, e.g., "image/\*"       |
| disabled     | boolean                   | false   | Whether upload is disabled                  |

## Basic Usage

```tsx
import { DelightfulUpload } from '@/components/base/DelightfulUpload';
import DelightfulButton from '@/components/base/DelightfulButton';
import DelightfulIcon from '@/components/base/DelightfulIcon';
import { IconFileUpload } from '@tabler/icons-react';

// Basic usage
const handleFileChange = (files) => {
  console.log('Selected files:', files);
};

<DelightfulUpload onFileChange={handleFileChange}>
  <DelightfulButton
    icon={<DelightfulIcon component={IconFileUpload} />}
  >
    Upload Files
  </DelightfulButton>
</DelightfulUpload>

// Multiple file upload
<DelightfulUpload
  multiple
  onFileChange={handleFileChange}
>
  <DelightfulButton>Upload multiple files</DelightfulButton>
</DelightfulUpload>

// Restrict file types
<DelightfulUpload
  accept="image/*"
  onFileChange={handleFileChange}
>
  <DelightfulButton>Upload images only</DelightfulButton>
</DelightfulUpload>

// Custom upload area
<DelightfulUpload onFileChange={handleFileChange}>
  <div style={{
    border: '2px dashed #ccc',
    padding: '20px',
    textAlign: 'center',
    cursor: 'pointer'
  }}>
    <p>Click or drag files here to upload</p>
  </div>
</DelightfulUpload>
```

## Features

1. **Easy to use**: Clean API that integrates easily into various scenarios
2. **Flexible customization**: Supports customizing the appearance of upload buttons or areas
3. **Complete functionality**: Supports single and multiple uploads; can restrict file types
4. **Works with other components**: Combines well with buttons, icons, and more

## When to Use

-   When users need to upload files
-   When you need a custom appearance for upload buttons or areas
-   When you need to restrict accepted file types
-   When handling single or multiple file uploads
-   When integrating file uploads into forms

The DelightfulUpload component provides a simple yet flexible file upload solution, suitable for scenarios that require file upload functionality.

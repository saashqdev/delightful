# DelightfulImagePreview — Advanced Image Viewer

`DelightfulImagePreview` is a feature-rich image viewer offering zoom, rotate, drag, comparison, and more — ideal for detailed image inspection scenarios.

## Props

| Prop              | Type                            | Default | Description                         |
| ----------------- | ------------------------------- | ------- | ------------------------------------ |
| src               | string                          | -       | Image source URL                     |
| onNext            | () => void                      | -       | Callback for next image              |
| onPrev            | () => void                      | -       | Callback for previous image          |
| nextDisabled      | boolean                         | false   | Disable the next button              |
| prevDisabled      | boolean                         | false   | Disable the previous button          |
| rootClassName     | string                          | -       | Custom class name for root container |
| hasCompare        | boolean                         | false   | Enable image comparison               |
| viewType          | CompareViewType                 | -       | Comparison view type                 |
| onChangeViewType  | (type: CompareViewType) => void | -       | Callback when comparison view changes|
| onLongPressStart  | () => void                      | -       | Long-press start (comparison mode)   |
| onLongPressEnd    | () => void                      | -       | Long-press end (comparison mode)     |
| ...HTMLAttributes | -                               | -       | Supports all HTML `img` attributes   |

### CompareViewType Enum

| Value      | Description          |
| ---------- | -------------------- |
| PULL       | Drag-to-compare mode |
| LONG_PRESS | Long-press compare   |

## Basic Usage

```tsx
import { DelightfulImagePreview } from '@/components/base/DelightfulImagePreview';

// Basic usage
<DelightfulImagePreview>
  <img src="/path/to/image.jpg" alt="Preview image" />
</DelightfulImagePreview>

// Image preview with navigation
<DelightfulImagePreview
  onNext={handleNextImage}
  onPrev={handlePrevImage}
  nextDisabled={isLastImage}
  prevDisabled={isFirstImage}
>
  <img src={currentImage} alt="Preview image" />
</DelightfulImagePreview>

// With image comparison
<DelightfulImagePreview
  hasCompare={true}
  viewType={CompareViewType.PULL}
  onChangeViewType={handleViewTypeChange}
  onLongPressStart={handleLongPressStart}
  onLongPressEnd={handleLongPressEnd}
>
  <div className="image-container">
    <img src="/path/to/original-image.jpg" alt="Original" />
    <img src="/path/to/compared-image.jpg" alt="Compared" />
  </div>
</DelightfulImagePreview>

// Custom styles
<DelightfulImagePreview rootClassName="custom-preview-container">
  <img
    src="/path/to/image.jpg"
    alt="Preview image"
    className="custom-image"
  />
</DelightfulImagePreview>
```

## Features

1. **Toolbar controls**: Zoom, rotate, reset, and more
2. **Drag to move**: Move the image via mouse drag
3. **Wheel zoom**: Use mouse wheel to zoom
4. **Image comparison**: Drag and long-press comparison modes
5. **Image navigation**: Navigate forward/backward in image sets
6. **Responsive design**: Adapts to various container sizes
7. **Dark theme**: Toolbar adapts to dark mode
8. **Long image handling**: Detects and handles long images suitably

## When to Use

- Inspect image details thoroughly
- Perform operations like zooming and rotating
- Compare two images (e.g., original vs processed)
- Browse multiple images within a collection
- Provide professional image preview functionality
- View high-resolution image details

The `DelightfulImagePreview` component delivers a professional image viewing experience, suitable for galleries, photo editors, product showcases, and more.

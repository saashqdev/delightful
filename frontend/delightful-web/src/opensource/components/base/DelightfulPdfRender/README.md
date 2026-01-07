# DelightfulPdfRender

PDF preview component built on `react-pdf`, supporting both File objects and URL rendering with a full set of interactions.

## Features

- ✅ Supports local files (File objects) and remote URLs
- ✅ Full toolbar: paging, zoom, rotate, download, fullscreen, etc.
- ✅ Keyboard shortcuts
- ✅ Responsive design
- ✅ Error handling and loading states
- ✅ TypeScript support
- ✅ Custom styling (based on antd-style)
- ✅ Internationalization (Chinese/English)

## Basic Usage

```tsx
import DelightfulPdfRender from './components/base/DelightfulPdfRender'

function App() {
  const [file, setFile] = useState<File | string | null>(null)
  
  return (
    <DelightfulPdfRender
      file={file}
      height={600}
      showToolbar
      enableKeyboard
      onLoadSuccess={(pdf) => console.log('Loaded successfully', pdf)}
      onLoadError={(error) => console.error('Load failed', error)}
    />
  )
}
```

## API

### Props

| Prop          | Description                                             | Type                    | Default  |
| ------------- | ------------------------------------------------------- | ----------------------- | -------- |
| file          | PDF source; can be a File object or URL string         | `File \| string \| null` | -        |
| showToolbar   | Whether to display the toolbar                         | `boolean`               | `true`   |
| initialScale  | Initial zoom scale                                     | `number`                | `1.0`    |
| minScale      | Minimum zoom scale                                     | `number`                | `0.5`    |
| maxScale      | Maximum zoom scale                                     | `number`                | `3.0`    |
| scaleStep     | Zoom step                                              | `number`                | `0.1`    |
| height        | Container height                                       | `string \| number`      | `600`    |
| width         | Container width                                        | `string \| number`      | `'100%'` |
| enableKeyboard| Enable keyboard shortcuts                              | `boolean`               | `true`   |
| onLoadError   | Callback on load failure                               | `(error: Error) => void`| -        |
| onLoadSuccess | Callback on successful load                            | `(pdf: any) => void`    | -        |

## Keyboard Shortcuts

| Shortcut   | Action             |
| ---------- | ------------------ |
| `←` / `→`  | Previous / Next    |
| `+` / `-`  | Zoom in / out      |
| `Ctrl+0`   | Reset zoom         |
| `F11`      | Toggle fullscreen  |

## Toolbar

- **Paging controls**: Previous, Next, jump via page number input
- **Zoom controls**: Zoom in, zoom out, set zoom percentage
- **Rotate**: Rotate 90° clockwise/counterclockwise
- **Document actions**: Reload, download PDF
- **Display**: Fullscreen preview

## File Sources

### Local File
```tsx
const handleFileUpload = (file: File) => {
  setFile(file)
}

// In an Upload component
<Upload beforeUpload={handleFileUpload}>
  <Button>Upload PDF</Button>
</Upload>
```

### Remote URL
```tsx
const pdfUrl = 'https://example.com/document.pdf'
setFile(pdfUrl)
```

## Styling

The component uses `antd-style` for styling; customize via CSS-in-JS:

```tsx
const useCustomStyles = createStyles(({ token }) => ({
  customContainer: {
    border: `2px solid ${token.colorPrimary}`,
    borderRadius: '12px',
  }
}))
```

## Requirements

- React 18+
- antd 5+
- react-pdf 9+
- antd-style 3+

## Notes

1. **PDF.js Worker**: Automatically configured; no extra setup needed
2. **CORS**: Cross-origin PDFs require proper server CORS headers
3. **File size**: Large PDFs may affect performance; consider optimization
4. **Browser support**: Relies on modern browser PDF rendering capabilities

## Error Handling

组件提供了完整的错误处理机制：

```tsx
<DelightfulPdfRender
  file={file}
  onLoadError={(error) => {
    console.error('PDF load failed:', error)
    // Show a user-friendly error message
    message.error('Failed to load PDF. Check file format or network.')
  }}
/>
```

## Internationalization

Supports Chinese and English using `react-i18next`.

### Setup

Ensure your i18n config includes the `component` namespace:

```typescript
// src/assets/locales/create.ts
ns: ["translation", "common", "interface", "message", "flow", "delightfulFlow", "component"]
```

### Language Files

- Chinese: `src/assets/locales/zh_CN/component.json`
- English: `src/assets/locales/en_US/component.json`

### Switch Language

```tsx
import { useTranslation } from "react-i18next"

function App() {
  const { i18n } = useTranslation()
  
  const switchLanguage = (lang: string) => {
    i18n.changeLanguage(lang)
  }
  
  return (
    <div>
      <Button onClick={() => switchLanguage("zh_CN")}>Chinese</Button>
      <Button onClick={() => switchLanguage("en_US")}>English</Button>
      <DelightfulPdfRender file={file} />
    </div>
  )
}
```

### Supported Text

All UI text supports i18n, including:

- Toolbar button tooltips
- Page navigation info
- Error and status messages
- Dropdown options
- Placeholder text
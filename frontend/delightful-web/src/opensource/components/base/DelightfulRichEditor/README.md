# DelightfulRichEditor — Rich Text Editor Component

`DelightfulRichEditor` is a TipTap-based rich text editor component that offers rich features such as text formatting, image insertion, emoji, and mentions.

## Props

| Property          | Type                           | Default | Description                                          |
| ----------------- | ------------------------------ | ------- | ---------------------------------------------------- |
| showToolBar       | boolean                        | true    | Whether to show the toolbar                          |
| placeholder       | string                         | -       | Placeholder text for the editor                      |
| content           | Content                        | -       | Initial editor content                               |
| editorProps       | UseEditorOptions               | -       | TipTap editor configuration options                  |
| onEnter           | (editor: Editor) => void       | -       | Callback when the Enter key is pressed               |
| enterBreak        | boolean                        | false   | Whether to remove the Enter key’s default line break |
| contentProps      | HTMLAttributes<HTMLDivElement> | -       | HTML attributes for the editor content area          |
| ...HTMLAttributes | -                              | -       | Supports all HTML div element attributes             |

## Basic Usage

```tsx
import { DelightfulRichEditor } from '@/components/base/DelightfulRichEditor';
import { useRef } from 'react';
import type { DelightfulRichEditorRef } from '@/components/base/DelightfulRichEditor';

// Basic usage
<DelightfulRichEditor
  placeholder="Please enter content..."
  style={{ height: '300px' }}
/>

// With initial content
<DelightfulRichEditor
  content="<p>This is initial content</p>"
  style={{ height: '300px' }}
/>

// Hide toolbar
<DelightfulRichEditor
  showToolBar={false}
  placeholder="Editor without toolbar"
  style={{ height: '200px' }}
/>

// Use ref to access the editor instance
const editorRef = useRef<DelightfulRichEditorRef>(null);

<DelightfulRichEditor
  ref={editorRef}
  placeholder="Editor controlled by ref"
  style={{ height: '300px' }}
/>

// Get editor content
const getContent = () => {
  const html = editorRef.current?.editor?.getHTML();
  console.log('Editor content:', html);
};

// Listen for content updates
<DelightfulRichEditor
  editorProps={{
    onUpdate: ({ editor }) => {
      console.log('Content updated:', editor.getHTML());
    }
  }}
  style={{ height: '300px' }}
/>

// Customize Enter key behavior
<DelightfulRichEditor
  enterBreak={true}
  onEnter={(editor) => {
    console.log('Enter key pressed');
    // Perform custom action
  }}
  style={{ height: '200px' }}
/>
```

## Features

1. **Rich text formatting**: Bold, italic, headings, font size, text alignment, and more
2. **Image handling**: Upload, paste, drag-and-drop images with preview and management
3. **Emoji support**: Built-in emoji picker for easy insertion
4. **Mentions**: Support for @-mentioning users or other entities
5. **Customizable toolbar**: Show or hide the toolbar to fit your scenario
6. **Placeholder support**: Display a custom placeholder when the editor is empty
7. **Custom Enter behavior**: Configure Enter key behavior for special interactions

## When To Use

-   When your app needs rich text editing functionality
-   When users should be able to format text and insert images/media
-   When emoji and mentions are required for social interactions
-   When you need a feature-rich yet clean editor UI
-   When you need to customize editor behavior for specific interactions

The DelightfulRichEditor component brings professional rich text editing capabilities to your application, suitable for comment systems, content creation, email editing, and many other scenarios.

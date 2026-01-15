# DelightfulMarkdown - Magic Markdown Render Component

`DelightfulMarkdown` is a powerful Markdown rendering component based on react-markdown, supporting code highlighting, LaTeX formulas, HTML content, and many other extended features.

## Properties

| Property Name      | Type    | Default Value | Description                                         |
| ------------------ | ------- | ------------- | --------------------------------------------------- |
| content            | string  | -             | Markdown content to render                          |
| allowHtml          | boolean | true          | Whether to allow rendering HTML content             |
| enableLatex        | boolean | true          | Whether to enable LaTeX formula support             |
| isSelf             | boolean | false         | Whether it's content sent by self (affects styling) |
| hiddenDetail       | boolean | false         | Whether to hide detailed content                    |
| isStreaming        | boolean | false         | Whether it's streaming render mode                  |
| ...MarkdownOptions | -       | -             | Support all react-markdown options                  |

## Basic Usage

```tsx
import { DelightfulMarkdown } from '@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown';

// Basic usage
<DelightfulMarkdown content="# Title\nThis is plain text" />

// Enable LaTeX formulas
<DelightfulMarkdown
  content="Einstein's mass-energy equation: $E=mc^2$"
  enableLatex
/>

// Allow HTML content
<DelightfulMarkdown
  content="This is a <span style='color:red'>red</span> text"
  allowHtml
/>

// Streaming render (suitable for typewriter effect)
<DelightfulMarkdown
  content="Content being generated..."
  isStreaming
/>

// Custom components
<DelightfulMarkdown
  content="# Custom Title"
  components={{
    h1: ({ node, ...props }) => <h1 style={{ color: 'blue' }} {...props} />
  }}
/>

// Using custom plugins
<DelightfulMarkdown
  content="content"
  remarkPlugins={[myCustomPlugin]}
  rehypePlugins={[myCustomRehypePlugin]}
/>
```

## Features

1. **Rich Format Support**: Supports standard Markdown syntax, including headings, lists, tables, code blocks, etc.
2. **Code Highlighting**: Built-in code syntax highlighting functionality
3. **LaTeX Formulas**: Supports mathematical formula rendering
4. **HTML Content**: Can safely render HTML content
5. **Streaming Render**: Supports smooth rendering of streaming content
6. **Custom Components**: Can customize rendering methods for various Markdown elements
7. **Plugin System**: Supports remark and rehype plugin extensions

## Built-in Components

DelightfulMarkdown has built-in optimized components for rendering specific Markdown elements:

-   `A`: Optimized link component, supports safe opening of external links
-   `Code`: Code block and inline code component, supports syntax highlighting
-   `Pre`: Code block container component, supports code copying functionality
-   `Sup`: Superscript component

## When to Use

-   When you need to render Markdown formatted content
-   When you need to display rich text content in your application
-   When you need to support code highlighting and mathematical formulas
-   When you need to render streaming content (such as chatbot replies)

The DelightfulMarkdown component makes your Markdown content display more beautiful and feature-rich, suitable for use in various scenarios that require rich text display.

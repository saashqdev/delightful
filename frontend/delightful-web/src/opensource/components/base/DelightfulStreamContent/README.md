# DelightfulStreamContent Magic Streaming Content Component

DelightfulStreamContent is a component for presenting streaming content. It simulates a typing effect by revealing text character-by-character, providing a dynamic reading experience. This component is especially useful for chatbot replies, code generation, and other scenarios requiring progressive content display.

## Properties

| Property | Type                              | Default | Description                                 |
| -------- | --------------------------------- | ------- | ------------------------------------------- |
| content  | string                            | -       | The text content to stream                  |
| children | (text: string) => React.ReactNode | -       | Optional render function to customize output |

## Basic Usage

```tsx
import DelightfulStreamContent from '@/components/base/DelightfulStreamContent';

// Basic usage — show plain text
<DelightfulStreamContent content="This is a streaming text content that appears character by character, simulating typing." />

// Use a custom render function
<DelightfulStreamContent content="This is **Markdown-formatted** text.">
  {(text) => <ReactMarkdown>{text}</ReactMarkdown>}
</DelightfulStreamContent>

// Use in a chat UI
<div className="chat-message">
  <div className="avatar">
    <img src="/bot-avatar.png" alt="Bot" />
  </div>
  <div className="message-content">
    <DelightfulStreamContent content={botResponse} />
  </div>
</div>
```

## Features

-   **Typing effect**: Simulates real typing; reveals content character-by-character
-   **Streaming updates**: Supports incremental updates for streaming API responses
-   **Custom rendering**: Customize output via the `children` render function
-   **Smooth transitions**: Preserves smooth visuals when content grows
-   **Lightweight**: Simple implementation with minimal performance overhead

## Use Cases

-   Displaying AI chatbot responses
-   Showing output from code generators
-   Step-by-step presentation of tutorials and guides
-   Dynamic storytelling and narrative displays
-   Any progressive content display that needs to capture attention

The DelightfulStreamContent component simulates typing to deliver engaging, lively content presentation—ideal for scenarios that benefit from a real-time interaction feel.

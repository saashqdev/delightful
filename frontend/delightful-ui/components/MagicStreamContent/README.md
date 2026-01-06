# DelightfulStreamContent Magic Stream Content Component

DelightfulStreamContent is a component for displaying streaming content that simulates typing effects and displays text content character by character, providing users with a dynamic reading experience. This component is particularly suitable for scenes like chatbot replies and code generation that require progressive content display.

## Properties

| Property | Type                              | Default | Description                                     |
| -------- | --------------------------------- | ------- | ----------------------------------------------- |
| content  | string                            | -       | Text content to be displayed in streaming mode |
| children | (text: string) => React.ReactNode | -       | Optional render function for custom rendering  |

## Basic Usage

```tsx
import DelightfulStreamContent from '@/components/base/DelightfulStreamContent';

// Basic usage - display text directly
<DelightfulStreamContent content="This is streaming text content that appears character by character, simulating a typing effect." />

// Using custom render function
<DelightfulStreamContent content="This is text **with Markdown formatting**.">
  {(text) => <ReactMarkdown>{text}</ReactMarkdown>}
</DelightfulStreamContent>

// Usage in a chat interface
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

-   **Typing effect**: Simulates real typing process, displays content character by character
-   **Streaming updates**: Supports incremental content updates, suitable for streaming API responses
-   **Custom rendering**: Customize content rendering through the children function
-   **Smooth transitions**: Maintains smooth visual effects when new content is added
-   **Lightweight**: Clean component implementation with minimal performance overhead

## Use Cases

-   AI chatbot reply display
-   Code generator output display
-   Step-by-step presentation of tutorials and guidance content
-   Dynamic display of stories and narrative content
-   Any progressive content display that needs to attract user attention

DelightfulStreamContent component provides a more vivid and engaging content display method for applications through simulating typing effects, especially suitable for scenarios that need to create a sense of real-time interaction.

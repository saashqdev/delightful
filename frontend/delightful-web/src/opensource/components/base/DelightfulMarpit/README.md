# DelightfulMarpit Enhanced Slide Component

DelightfulMarpit is a slide rendering component based on Marpit and Reveal.js, used to convert Markdown-formatted content into interactive slide presentations. This component supports rich slide features such as transition animations and theme settings.

## Properties

| Property | Type   | Default | Description                    |
| -------- | ------ | ------- | ------------------------------ |
| content  | string | -       | Slide content in Markdown format |

## Basic Usage

```tsx
import DelightfulMarpit from "@/components/base/DelightfulMarpit"

// Basic usage
const slideContent = `
---
theme: default
---

# First Slide
This is the content of the first slide

---

# Second Slide
- Item 1
- Item 2
- Item 3

---

# Thank You
`

;<DelightfulMarpit content={slideContent} />
```

## Markdown Syntax

DelightfulMarpit uses Marpit syntax to define slides:

1. Use `---` to separate different slides
2. Global themes and styles can be set before the first `---`
3. Supports standard Markdown syntax such as headings, lists, code blocks, etc.
4. HTML and CSS can be used for more complex layouts and style customization

Example:

````markdown
---
theme: default
---

# Slide Title

Content paragraph

---

## List Example

-   Item 1
-   Item 2
    -   Sub-item A
    -   Sub-item B

---

## Code Example

```javascript
function hello() {
	console.log("Hello, world!")
}
```
````

## Features

-   **Markdown Support**: Create slides using simple Markdown syntax
-   **Interactive Display**: Provides interactive slide browsing experience based on Reveal.js
-   **Theme Customization**: Supports custom themes and styles
-   **Automatic Cleanup**: Automatically cleans up resources when component unmounts
-   **Responsive Design**: Adapts to containers of different sizes

## Use Cases

-   Displaying presentations within applications
-   Interactive display of educational and training materials
-   Product demos and feature introductions
-   Presenting meeting and report content
-   Any scenario requiring conversion of Markdown content to slides

DelightfulMarpit provides a simple yet powerful way for applications to convert text content into professional slide presentations, especially suitable for scenarios that require frequent content updates or data-driven presentation generation.

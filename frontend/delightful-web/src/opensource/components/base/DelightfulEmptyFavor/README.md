# DelightfulEmptyFavor — Empty Favorites State Component

DelightfulEmptyFavor is a component for displaying empty favorites state. When a user's favorites list is empty, it shows a friendly message and icon, improving user experience.

## Props

| Prop | Type   | Default | Description                                              |
| ---- | ------ | ------- | -------------------------------------------------------- |
| text | string | -       | Custom text to display; uses default i18n text if not provided |

## Basic Usage

```tsx
import DelightfulEmptyFavor from '@/components/base/DelightfulEmptyFavor';

// Basic usage — uses default text
<DelightfulEmptyFavor />

// Custom text
<DelightfulEmptyFavor text="You haven't added any favorites yet" />

// Use in conditional rendering
{favoritesList.length === 0 && <DelightfulEmptyFavor />}
```

## Features

-   **Friendly empty state message**: Provides visual feedback and avoids blank pages
-   **Internationalization**: Default text supports multiple languages
-   **Minimal design**: Simple icon and text combination
-   **Custom text**: Support for custom display text
-   **Lightweight**: Simple implementation with no extra dependencies

## Use Cases

-   Display when favorites or likes list is empty
-   Prompt when user hasn't added any content yet
-   Show when search results are empty
-   Any scenario requiring an "no data" state

The `DelightfulEmptyFavor` component improves user experience when facing empty lists by providing a visually appealing empty state, encouraging users to add content to their favorites.

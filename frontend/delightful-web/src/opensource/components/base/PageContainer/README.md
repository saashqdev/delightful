# PageContainer Page Container Component

`PageContainer` is a container component used to wrap page content, providing unified page layout, title bar, and close functionality.

## Properties

| Property     | Type       | Default | Description                                 |
| ------------ | ---------- | ------- | ------------------------------------------- |
| icon         | ReactNode  | -       | Icon before the page title                  |
| closeable    | boolean    | false   | Whether to show the close button            |
| onClose      | () => void | -       | Callback function when close button clicked |
| className    | string     | -       | Custom class name for the container         |
| ...CardProps | -          | -       | Supports all Ant Design Card properties     |

## Basic Usage

```tsx
import { PageContainer } from '@/components/base/PageContainer';
import { IconHome } from '@tabler/icons-react';

// Basic usage
<PageContainer title="Page Title">
  <div>Page content</div>
</PageContainer>

// Page with icon
<PageContainer
  title="Home"
  icon={<IconHome size={20} />}
>
  <div>Home content</div>
</PageContainer>

// Closeable page
<PageContainer
  title="Detail Page"
  closeable
  onClose={() => console.log('Page closed')}
>
  <div>Detail page content</div>
</PageContainer>

// Custom header style
<PageContainer
  title="Custom Header"
  headStyle={{ background: '#f0f2f5' }}
>
  <div>Page content</div>
</PageContainer>

// Use in layout
<Layout>
  <Layout.Sider>Sidebar</Layout.Sider>
  <Layout.Content>
    <PageContainer title="Main Content Area">
      <div>Main content</div>
    </PageContainer>
  </Layout.Content>
</Layout>

// Nested usage
<PageContainer title="Outer Page">
  <div style={{ padding: '20px' }}>
    <PageContainer title="Inner Page">
      <div>Inner content</div>
    </PageContainer>
  </div>
</PageContainer>
```

## Features

1. **Unified Layout**: Provides a unified page layout structure
2. **Responsive Design**: Automatically adapts to different screen sizes
3. **Theme Adaptation**: Automatically adapts to light/dark themes
4. **Fixed Title Bar**: The title bar stays fixed at the top when scrolling
5. **Close Function**: Can add a close button, convenient for navigation in multi-page applications

## When to Use

-   When you need to provide a unified layout structure for pages
-   When pages need a title bar and close functionality
-   When creating multiple pages with consistent appearance in your application
-   When the page title bar needs to remain visible while scrolling
-   When you need to display icons and titles on pages

The PageContainer component makes your page layout more unified and professional, suitable for use in various scenarios that require structured pages.

# TSIcon — Icon Component

`TSIcon` is an IconPark-based icon component providing a rich set of preset icons, with support for custom size, color, and more.

## Props

| Prop   | Type   | Default | Description                                                                 |
| ------ | ------ | ------- | --------------------------------------------------------------------------- |
| type   | string | -       | Icon type (required). Names start with `ts-`, e.g., `ts-folder`, `ts-file` |
| size   | string | "24"    | Icon size                                                                  |
| color  | string | -       | Icon color                                                                 |
| stroke | string | -       | Icon stroke thickness                                                      |
| fill   | string | -       | Icon fill color                                                            |
| spin   | string | -       | Rotate icon when set to `true`                                             |
| ...    | -      | -       | Supports other IconPark icon props                                         |

## Basic Usage

```tsx
import TSIcon from '@/components/base/TSIcon';

// Basic usage
<TSIcon type="ts-folder" />

// Custom size
<TSIcon type="ts-file" size="32" />

// Custom color
<TSIcon type="ts-download" color="#1677ff" />

// Spinning icon
<TSIcon type="ts-loading" spin="true" />

// Combined usage
<div style={{ display: 'flex', gap: '8px' }}>
  <TSIcon type="ts-folder" size="20" />
  <span>Folder</span>
</div>
```

## Available Icons

`TSIcon` supports many icon types; all names start with `ts-`, including but not limited to:

-   File: `ts-folder`, `ts-file`, `ts-pdf-file`, `ts-image-file`, `ts-word-file`, etc.
-   Actions: `ts-download`, `ts-upload`, `ts-search`, `ts-edit`, `ts-delete`, etc.
-   UI: `ts-menu`, `ts-home`, `ts-setting`, `ts-user`, etc.
-   Status: `ts-loading`, `ts-success`, `ts-error`, `ts-warning`, etc.

See the component definition for the complete icon list.

## Features

1. **Rich icon set**: Large set of presets covering common scenarios
2. **Consistent style**: Unified design language across icons
3. **Flexible customization**: Custom size, color, rotation, and more
4. **Easy to use**: Simple API; specify the icon type and go

## When to Use

-   Use icons within UI views
-   Maintain a consistent icon style across the app
-   Use domain-specific icons (e.g., file type icons)
-   Customize size and color for icons
-   Use animated icons (e.g., spinning loader)

The `TSIcon` component offers rich icon choices and flexible customization, ideal for building a consistent visual experience.

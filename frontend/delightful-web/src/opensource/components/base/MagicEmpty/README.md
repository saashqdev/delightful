# DelightfulEmpty 魔法空状态组件

`DelightfulEmpty` 是一个基于 Ant Design Empty 组件的简化版空状态组件，提供了国际化支持和简洁的默认样式。

## 属性

| 属性名        | 类型 | 默认值 | 说明                             |
| ------------- | ---- | ------ | -------------------------------- |
| ...EmptyProps | -    | -      | 支持所有 Ant Design Empty 的属性 |

## 基础用法

```tsx
import { DelightfulEmpty } from '@/components/base/DelightfulEmpty';

// 基础用法
<DelightfulEmpty />

// 自定义描述文本（会覆盖国际化文本）
<DelightfulEmpty description="没有找到数据" />

// 自定义图片
<DelightfulEmpty image="/path/to/custom-image.png" />

// 在列表或表格中使用
<div style={{ textAlign: 'center', padding: '20px 0' }}>
  <DelightfulEmpty />
</div>

// 带操作按钮
<DelightfulEmpty>
  <button>创建新内容</button>
</DelightfulEmpty>
```

## 特点

1. **国际化支持**：自动使用 i18n 翻译的"无数据"文本
2. **简洁样式**：默认使用 Empty.PRESENTED_IMAGE_SIMPLE 作为图片，更加简洁
3. **易于使用**：无需额外配置，开箱即用
4. **完全可定制**：支持所有 Ant Design Empty 组件的属性

## 何时使用

-   当页面或容器中没有数据时
-   当搜索或筛选结果为空时
-   当列表、表格或结果集为空时
-   当需要提示用户创建首个内容时

DelightfulEmpty 组件让你的空状态展示更加简洁和国际化，适合在各种场景下使用。

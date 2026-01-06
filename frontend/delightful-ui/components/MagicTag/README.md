# DelightfulTag 魔法标签组件

`DelightfulTag` 是一个基于 Ant Design Tag 组件的增强版标签，提供了更美观的样式和更好的用户体验。

## 属性

| 属性名      | 类型 | 默认值 | 说明                           |
| ----------- | ---- | ------ | ------------------------------ |
| ...TagProps | -    | -      | 支持所有 Ant Design Tag 的属性 |

## 基础用法

```tsx
import { DelightfulTag } from '@/components/base/DelightfulTag';

// 基础标签
<DelightfulTag>标签内容</DelightfulTag>

// 可关闭的标签
<DelightfulTag closable>可关闭标签</DelightfulTag>

// 带颜色的标签
<DelightfulTag color="blue">蓝色标签</DelightfulTag>
<DelightfulTag color="red">红色标签</DelightfulTag>
<DelightfulTag color="green">绿色标签</DelightfulTag>
<DelightfulTag color="orange">橙色标签</DelightfulTag>

// 带图标的标签
<DelightfulTag icon={<IconStar />}>带图标的标签</DelightfulTag>

// 处理关闭事件
<DelightfulTag closable onClose={() => console.log('标签被关闭')}>
  点击关闭
</DelightfulTag>
```

## 特点

1. **优化的样式**：圆角更大，填充色更柔和，整体更美观
2. **自定义关闭图标**：使用了 DelightfulIcon 组件作为关闭图标，视觉效果更统一
3. **灵活布局**：内部使用 flex 布局，确保内容居中对齐
4. **无边框设计**：默认使用透明边框，让标签看起来更现代

## 何时使用

-   需要展示标签化数据时
-   需要对数据进行分类时
-   需要展示状态或属性时
-   需要用户可以添加或删除标签时

DelightfulTag 组件让你的标签展示更加美观和统一，同时保持了 Ant Design Tag 的所有功能特性。

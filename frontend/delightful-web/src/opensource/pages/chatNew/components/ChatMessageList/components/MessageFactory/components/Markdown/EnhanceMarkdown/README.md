# DelightfulMarkdown 魔法 Markdown 渲染component

`DelightfulMarkdown` 是一个功能强大的 Markdown 渲染component，based on react-markdown，支持代码高亮、LaTeX 公式、HTML 内容等多种扩展功能。

## property

| property名             | class型    | 默认值 | description                             |
| ------------------ | ------- | ------ | -------------------------------- |
| content            | string  | -      | 要渲染的 Markdown 内容           |
| allowHtml          | boolean | true   | 是否允许渲染 HTML 内容           |
| enableLatex        | boolean | true   | 是否启用 LaTeX 公式支持          |
| isSelf             | boolean | false  | 是否为自己发送的内容（影响样式） |
| hiddenDetail       | boolean | false  | 是否隐藏详细内容                 |
| isStreaming        | boolean | false  | 是否为流式渲染模式               |
| ...MarkdownOptions | -       | -      | 支持所有 react-markdown 的option   |

## 基础用法

```tsx
import { DelightfulMarkdown } from '@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown';

// 基础用法
<DelightfulMarkdown content="# 标题\n这是一段普通文本" />

// 启用 LaTeX 公式
<DelightfulMarkdown
  content="爱因斯坦质能方程：$E=mc^2$"
  enableLatex
/>

// 允许 HTML 内容
<DelightfulMarkdown
  content="这是一个<span style='color:red'>红色</span>文本"
  allowHtml
/>

// 流式渲染（适用于打字机效果）
<DelightfulMarkdown
  content="正在生成的内容..."
  isStreaming
/>

// 自定义component
<DelightfulMarkdown
  content="# 自定义标题"
  components={{
    h1: ({ node, ...props }) => <h1 style={{ color: 'blue' }} {...props} />
  }}
/>

// 使用自定义插件
<DelightfulMarkdown
  content="内容"
  remarkPlugins={[myCustomPlugin]}
  rehypePlugins={[myCustomRehypePlugin]}
/>
```

## 特点

1. **丰富的format支持**：支持标准 Markdown 语法，包括标题、list、table、代码块等
2. **代码高亮**：内置代码语法高亮功能
3. **LaTeX 公式**：支持数学公式渲染
4. **HTML 内容**：可以安全地渲染 HTML 内容
5. **流式渲染**：支持流式内容的平滑渲染
6. **自定义component**：可以自定义各种 Markdown 元素的渲染方式
7. **插件系统**：支持 remark 和 rehype 插件扩展功能

## 内置component

DelightfulMarkdown 内置了多个optimization的component用于渲染特定的 Markdown 元素：

-   `A`：optimization的链接component，支持外部链接安全打开
-   `Code`：代码块和行内代码component，支持语法高亮
-   `Pre`：代码块容器component，支持复制代码功能
-   `Sup`：上标component

## 何时使用

-   需要渲染 Markdown format内容时
-   需要在应用中展示富文本内容时
-   需要支持代码高亮和数学公式时
-   需要流式渲染内容（如聊天机器人回复）时

DelightfulMarkdown component让你的 Markdown 内容展示更加美观和功能丰富，适合在各种需要富文本展示的场景下使用。

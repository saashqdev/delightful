# DelightfulMarkdown 魔法 Markdown rendercomponent

`DelightfulMarkdown` yes一个functionalitystronglarge的 Markdown rendercomponent，based on react-markdown，support代码high亮、LaTeX formula、HTML content等many种extensionfunctionality。

## property

| property名             | class型    | defaultvalue | description                             |
| ------------------ | ------- | ------ | -------------------------------- |
| content            | string  | -      | 要render的 Markdown content           |
| allowHtml          | boolean | true   | yesnoallowrender HTML content           |
| enableLatex        | boolean | true   | yesnoenable LaTeX formulasupport          |
| isSelf             | boolean | false  | yesno为自己send的content（影响样式） |
| hiddenDetail       | boolean | false  | yesnohidedetailedcontent                 |
| isStreaming        | boolean | false  | yesno为流式renderpattern               |
| ...MarkdownOptions | -       | -      | supportall react-markdown 的option   |

## basic用法

```tsx
import { DelightfulMarkdown } from '@/opensource/pages/chatNew/components/ChatMessageList/components/MessageFactory/components/Markdown/EnhanceMarkdown';

// basic用法
<DelightfulMarkdown content="# title\n这yes一段普通文本" />

// enable LaTeX formula
<DelightfulMarkdown
  content="爱因斯坦质能equation：$E=mc^2$"
  enableLatex
/>

// allow HTML content
<DelightfulMarkdown
  content="这yes一个<span style='color:red'>红色</span>文本"
  allowHtml
/>

// 流式render（适用in打字机效果）
<DelightfulMarkdown
  content="isgenerate的content..."
  isStreaming
/>

// customcomponent
<DelightfulMarkdown
  content="# customtitle"
  components={{
    h1: ({ node, ...props }) => <h1 style={{ color: 'blue' }} {...props} />
  }}
/>

// 使用customplugin
<DelightfulMarkdown
  content="content"
  remarkPlugins={[myCustomPlugin]}
  rehypePlugins={[myCustomRehypePlugin]}
/>
```

## 特点

1. **丰富的formatsupport**：supportstandard Markdown 语法，package括title、list、table、代码块等
2. **代码high亮**：内置代码语法high亮functionality
3. **LaTeX formula**：support数学formularender
4. **HTML content**：can安all地render HTML content
5. **流式render**：support流式content的smoothrender
6. **customcomponent**：cancustom各种 Markdown 元素的render方式
7. **pluginsystem**：support remark 和 rehype pluginextensionfunctionality

## 内置component

DelightfulMarkdown 内置了multipleoptimization的component用inrender特定的 Markdown 元素：

-   `A`：optimization的linkcomponent，supportoutsidelink安allopen
-   `Code`：代码块和行内代码component，support语法high亮
-   `Pre`：代码块容器component，supportcopy代码functionality
-   `Sup`：top标component

## 何time使用

-   needrender Markdown formatcontenttime
-   needatapplication中展示富文本contenttime
-   needsupport代码high亮和数学formulatime
-   need流式rendercontent（如聊天机器人回复）time

DelightfulMarkdown component让你的 Markdown content展示更add美观和functionality丰富，适合at各种need富文本展示的scenario下使用。

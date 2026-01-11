# EnhanceMarkdown component安全test套件

## 概述

本test套件专门为 `EnhanceMarkdown` component设计，用于防范各种 XSS（跨站脚本）攻击。该componenthandleuser输入的 Markdown 内容，存在潜在的安全风险，因此需要全面的安全test来确保渲染安全性。

## 安全风险分析

### 主要风险点

1. **HTML 注入**：component支持 `allowHtml` property，可能渲染恶意 HTML
2. **Script 执行**：恶意脚本label可能被执行
3. **eventhandle器**：HTML eventproperty可能被利用
4. **JavaScript 协议**：`javascript:` 协议的 URL 可能被执行
5. **LaTeX 注入**：LaTeX 功能可能存在注入风险
6. **自定义component**：自定义componentconfiguration可能被恶意利用
7. **流式渲染**：流式内容update过程中的安全风险

## test用例分class

### 1. Script Tag XSS Prevention (脚本label XSS 防护)

```typescript
// 基础脚本注入
'<script>window.xssAttack = true;</script>Hello World'

// 代码块中的脚本
'```html\n<script>window.xssAttack2 = true;</script>\n```'

// 带property的脚本label
'<script type="text/javascript" src="malicious.js">window.xssAttack3 = true;</script>'
```

### 2. Event Handler XSS Prevention (eventhandle器 XSS 防护)

```typescript
// 点击event
'<div onclick="window.xssClick = true;">Click me</div>'

// loadevent
'<img src="invalid.jpg" onload="window.xssLoad = true;" alt="test" />'

// errorevent
'<img src="invalid.jpg" onerror="window.xssError = true;" alt="test" />'

// 其他eventhandle器
['onmouseover', 'onmouseout', 'onfocus', 'onblur', 'onkeypress', 'onsubmit']
```

### 3. JavaScript Protocol XSS Prevention (JavaScript 协议 XSS 防护)

```typescript
// 链接中的 JavaScript 协议
'[Click me](javascript:window.xssJsProtocol = true;)'

// 图片中的 JavaScript 协议
'<img src="javascript:window.xssJsImg = true;" alt="test" />'

// Data URL 中的 JavaScript
'<a href="data:text/html,<script>window.xssDataUrl = true;</script>">Click</a>'
```

### 4. HTML Entity Encoding XSS Prevention (HTML 实体encoding XSS 防护)

```typescript
// HTML 实体encoding的脚本label
'&lt;script&gt;window.xssEntityScript = true;&lt;/script&gt;'

// 十六进制encoding
'&#x3C;script&#x3E;window.xssHex = true;&#x3C;/script&#x3E;'

// 十进制encoding
'&#60;script&#62;window.xssDecimal = true;&#60;/script&#62;'
```

### 5. CSS Injection XSS Prevention (CSS 注入 XSS 防护)

```typescript
// CSS 表达式
'<div style="background: expression(window.xssCss = true);">Test</div>'

// CSS 中的 JavaScript URL
'<div style="background-image: url(javascript:window.xssCssJs = true);">Test</div>'
```

### 6. Advanced Attack Vectors (高级攻击vector)

```typescript
// Unicode 脚本label
'<\u0073cript>window.xssUnicode = true;</\u0073cript>'

// 嵌套脚本label
'<scr<script>ipt>window.xssNested = true;</scr</script>ipt>'

// 大小写混合
'<ScRiPt>window.xssCase = true;</ScRiPt>'

// SVG 中的脚本
'<svg><script>window.xssSvg = true;</script></svg>'

// 零宽字符绕过
'<scr\u200Bipt>window.xssZeroWidth = true;</scr\u200Bipt>'
```

### 7. Polyglot XSS Attacks (多语言 XSS 攻击)

```typescript
// 多语言载荷
'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'window.xssPolyglot=true\'>'

// 上下文破坏
'"></script><script>window.xssContextBreak = true;</script>'
```

### 8. Filter Evasion Techniques (过滤器绕过技术)

```typescript
// 注释绕过
'<scr<!---->ipt>window.xssComment = true;</scr<!---->ipt>'

// 换行绕过
'<scr\nipt>window.xssNewline = true;</scr\nipt>'

// 制表符绕过
'<scr\tipt>window.xssTab = true;</scr\tipt>'
```

### 9. Markdown-specific Attack Vectors (Markdown 特定攻击vector)

```typescript
// 恶意引用链接
`[Click here][malicious]
[malicious]: javascript:window.xssReference = true;`

// 代码块中的 HTML
'```html\n<script>window.xssCodeBlock = true;</script>\n```'

// 内联代码中的脚本
'This is `<script>window.xssInlineCode = true;</script>` inline code'
```

### 10. LaTeX Injection Security (LaTeX 注入安全)

```typescript
// LaTeX 中的链接
'$\\href{javascript:window.xssLatex=true}{Click}$'

// LaTeX command注入
'$$\\begin{document}\\href{javascript:window.xssLatexCmd=true}{text}\\end{document}$$'
```

## 安全configurationtest

### allowHtml=false 安全test

确保当 `allowHtml` settings为 `false` 时，HTML 内容被正确转义：

```typescript
{
  content: '<script>window.xssNoHtml = true;</script><b>Bold text</b>',
  allowHtml: false
}
```

### 流式渲染安全test

确保在流式渲染过程中的内容update不会引入安全风险：

```typescript
{
  content: '<script>window.xssStream = true;</script>Streaming content',
  isStreaming: true
}
```

### 自定义component安全test

test自定义componentconfiguration不会被恶意利用：

```typescript
{
  components: {
    script: MaliciousComponent
  }
}
```

## performance和资源安全test

### 内存耗尽防护

```typescript
// 超大内容
const largeContent = '<script>window.xssLarge = true;</script>'.repeat(10000)

// 深度嵌套
let nestedContent = '<script>window.xssNested = true;</script>'
for (let i = 0; i < 100; i++) {
  nestedContent = `<div>${nestedContent}</div>`
}
```

### 递归结构防护

```typescript
// 递归 Markdown 结构
let recursiveMarkdown = '<script>window.xssRecursive = true;</script>'
for (let i = 0; i < 50; i++) {
  recursiveMarkdown = `> ${recursiveMarkdown}`
}
```

## error handling安全test

### 敏感information泄露防护

确保error handling过程中不会泄露敏感information：

```typescript
const problematicContent = '<script>throw new Error("XSS attempt: " + document.cookie);</script>'
```

### 无效 Unicode 序列handle

```typescript
const invalidUnicode = '\uD800<script>window.xssInvalidUnicode = true;</script>\uDFFF'
```

## CSP (Content Security Policy) 合规性test

### 内联样式check

确保component不会create违反 CSP 的内联样式：

```typescript
const contentWithInlineStyles = '<div style="background: red; color: blue;">Test</div>'
```

### 动态脚本元素check

确保component不会create动态脚本元素：

```typescript
const maliciousContent = '<script>window.xssCSP = true;</script>'
// validate页面中不存在包含恶意代码的脚本元素
```

## test执行guide

### 运行test

```bash
# 运行基础安全test
npm test -- __tests__/security.test.tsx

# 运行高级安全test
npm test -- __tests__/advanced-security.test.tsx

# 运行configuration安全test
npm test -- __tests__/security-config.test.tsx
```

### testcoverage

建议这些安全test应该覆盖以下场景：

1. **正常flow**：确保正常的 Markdown 内容能够正确渲染
2. **边界情况**：空内容、null/undefined 内容
3. **恶意输入**：各种 XSS 攻击vector
4. **configuration组合**：不同的propertyconfiguration组合
5. **performance极限**：大量数据和深度嵌套
6. **error handling**：exception情况的安全handle

## 安全建议

### 开发建议

1. **默认安全**：默认情况下应该禁用 HTML 渲染
2. **输入validate**：对所有user输入进行严格validate
3. **输出encoding**：确保所有输出都经过适当的encoding
4. **CSP 策略**：实施严格的内容安全策略
5. **定期审计**：定期进行安全审计和test

### 生产部署建议

1. **监控告警**：settings安全event监控和告警
2. **日志记录**：记录所有可疑的输入和渲染尝试
3. **versionupdate**：及时update依赖库以fix安全漏洞
4. **permission控制**：实施适当的userpermission控制

## 结论

这个安全test套件提供了全面的 XSS 攻击防护test，涵盖了从基础的脚本注入到高级的多语言攻击vector。通过这些test，可以确保 `EnhanceMarkdown` component在各种攻击场景下都能保持安全性。

定期运行这些test，并根据新发现的攻击vector及时updatetest用例，是维护component安全性的重要措施。 
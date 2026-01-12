# EnhanceMarkdown component安alltest套件

## overview

本test套件专门for `EnhanceMarkdown` componentdesign，用in防范各种 XSS（跨站script）攻击。该componenthandleuserinput的 Markdown content，exist潜at的安all风险，thusneedcomprehensive的安alltest来确保render安all性。

## 安all风险analyze

### main风险点

1. **HTML 注入**：componentsupport `allowHtml` property，mightrender恶意 HTML
2. **Script execute**：恶意scriptlabelmight被execute
3. **eventhandle器**：HTML eventpropertymight被利用
4. **JavaScript 协议**：`javascript:` 协议的 URL might被execute
5. **LaTeX 注入**：LaTeX functionalitymightexist注入风险
6. **customcomponent**：customcomponentconfigurationmight被恶意利用
7. **流式render**：流式contentupdate过程中的安all风险

## testuse case分class

### 1. Script Tag XSS Prevention (scriptlabel XSS 防护)

```typescript
// basicscript注入
'<script>window.xssAttack = true;</script>Hello World'

// 代码块中的script
'```html\n<script>window.xssAttack2 = true;</script>\n```'

// 带property的scriptlabel
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
// link中的 JavaScript 协议
'[Click me](javascript:window.xssJsProtocol = true;)'

// 图片中的 JavaScript 协议
'<img src="javascript:window.xssJsImg = true;" alt="test" />'

// Data URL 中的 JavaScript
'<a href="data:text/html,<script>window.xssDataUrl = true;</script>">Click</a>'
```

### 4. HTML Entity Encoding XSS Prevention (HTML 实体encoding XSS 防护)

```typescript
// HTML 实体encoding的scriptlabel
'&lt;script&gt;window.xssEntityScript = true;&lt;/script&gt;'

// ten六进制encoding
'&#x3C;script&#x3E;window.xssHex = true;&#x3C;/script&#x3E;'

// ten进制encoding
'&#60;script&#62;window.xssDecimal = true;&#60;/script&#62;'
```

### 5. CSS Injection XSS Prevention (CSS 注入 XSS 防护)

```typescript
// CSS expression
'<div style="background: expression(window.xssCss = true);">Test</div>'

// CSS 中的 JavaScript URL
'<div style="background-image: url(javascript:window.xssCssJs = true);">Test</div>'
```

### 6. Advanced Attack Vectors (advanced攻击vector)

```typescript
// Unicode scriptlabel
'<\u0073cript>window.xssUnicode = true;</\u0073cript>'

// 嵌套scriptlabel
'<scr<script>ipt>window.xssNested = true;</scr</script>ipt>'

// case混合
'<ScRiPt>window.xssCase = true;</ScRiPt>'

// SVG 中的script
'<svg><script>window.xssSvg = true;</script></svg>'

// 零wide字符绕过
'<scr\u200Bipt>window.xssZeroWidth = true;</scr\u200Bipt>'
```

### 7. Polyglot XSS Attacks (many语言 XSS 攻击)

```typescript
// many语言载荷
'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'window.xssPolyglot=true\'>'

// top下文破坏
'"></script><script>window.xssContextBreak = true;</script>'
```

### 8. Filter Evasion Techniques (filter器绕过technology)

```typescript
// comment绕过
'<scr<!---->ipt>window.xssComment = true;</scr<!---->ipt>'

// 换行绕过
'<scr\nipt>window.xssNewline = true;</scr\nipt>'

// 制表符绕过
'<scr\tipt>window.xssTab = true;</scr\tipt>'
```

### 9. Markdown-specific Attack Vectors (Markdown 特定攻击vector)

```typescript
// 恶意引用link
`[Click here][malicious]
[malicious]: javascript:window.xssReference = true;`

// 代码块中的 HTML
'```html\n<script>window.xssCodeBlock = true;</script>\n```'

// 内联代码中的script
'This is `<script>window.xssInlineCode = true;</script>` inline code'
```

### 10. LaTeX Injection Security (LaTeX 注入安all)

```typescript
// LaTeX 中的link
'$\\href{javascript:window.xssLatex=true}{Click}$'

// LaTeX command注入
'$$\\begin{document}\\href{javascript:window.xssLatexCmd=true}{text}\\end{document}$$'
```

## 安allconfigurationtest

### allowHtml=false 安alltest

确保when `allowHtml` settingsfor `false` time，HTML content被correct转义：

```typescript
{
  content: '<script>window.xssNoHtml = true;</script><b>Bold text</b>',
  allowHtml: false
}
```

### 流式render安alltest

确保at流式render过程中的contentupdate不会引入安all风险：

```typescript
{
  content: '<script>window.xssStream = true;</script>Streaming content',
  isStreaming: true
}
```

### customcomponent安alltest

testcustomcomponentconfiguration不会被恶意利用：

```typescript
{
  components: {
    script: MaliciousComponent
  }
}
```

## performance和resource安alltest

### memory耗尽防护

```typescript
// 超largecontent
const largeContent = '<script>window.xssLarge = true;</script>'.repeat(10000)

// depth嵌套
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

## error handling安alltest

### sensitiveinformation泄露防护

确保error handling过程中不会泄露sensitiveinformation：

```typescript
const problematicContent = '<script>throw new Error("XSS attempt: " + document.cookie);</script>'
```

### invalid Unicode sequencehandle

```typescript
const invalidUnicode = '\uD800<script>window.xssInvalidUnicode = true;</script>\uDFFF'
```

## CSP (Content Security Policy) 合规性test

### 内联样式check

确保component不会create违反 CSP 的内联样式：

```typescript
const contentWithInlineStyles = '<div style="background: red; color: blue;">Test</div>'
```

### 动态script元素check

确保component不会create动态script元素：

```typescript
const maliciousContent = '<script>window.xssCSP = true;</script>'
// validatepage中not existinclude恶意代码的script元素
```

## testexecuteguide

### runtest

```bash
# runbasic安alltest
npm test -- __tests__/security.test.tsx

# runadvanced安alltest
npm test -- __tests__/advanced-security.test.tsx

# runconfiguration安alltest
npm test -- __tests__/security-config.test.tsx
```

### testcoverage

suggestion这些安alltestshould覆盖by下scenario：

1. **normalflow**：确保normal的 Markdown content能够correctrender
2. **边界情况**：nullcontent、null/undefined content
3. **恶意input**：各种 XSS 攻击vector
4. **configurationgroup合**：different的propertyconfigurationgroup合
5. **performance极限**：large amountdata和depth嵌套
6. **error handling**：exception情况的安allhandle

## 安allsuggestion

### 开发suggestion

1. **default安all**：default情况下shoulddisable HTML render
2. **inputvalidate**：对alluserinput进行严格validate
3. **outputencoding**：确保alloutput都经过适when的encoding
4. **CSP 策略**：实施严格的content安all策略
5. **定期审计**：定期进行安all审计和test

### 生产deploysuggestion

1. **monitor告警**：settings安alleventmonitor和告警
2. **log记录**：记录all可疑的input和rendertry
3. **versionupdate**：及timeupdatedependency库byfix安all漏洞
4. **permissioncontrol**：实施适when的userpermissioncontrol

## 结论

这个安alltest套件提供了comprehensive的 XSS 攻击防护test，涵盖了frombasic的script注入toadvanced的many语言攻击vector。through这些test，can确保 `EnhanceMarkdown` componentat各种攻击scenario下都能keep安all性。

定期run这些test，并根据新发现的攻击vector及timeupdatetestuse case，ismaintaincomponent安all性的heavy要措施。 
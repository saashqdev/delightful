# EnhanceMarkdown component security test suite

## overview

This test suite is specifically for `EnhanceMarkdown` component design, used to prevent various XSS (cross-site script) attacks. This component handles user input Markdown content, which exists potential security risks, thus need comprehensive security test to ensure render security.

## Security risk analysis

### main risk points

1. **HTML injection**: component support `allowHtml` property, might render malicious HTML
2. **Script execute**: malicious script label might be executed
3. **event handler**: HTML event property might be exploited
4. **JavaScript protocol**: `javascript:` protocol URL might be executed
5. **LaTeX injection**: LaTeX functionality might exist injection risk
6. **custom component**: custom component configuration might be maliciously exploited
7. **streaming render**: streaming content update process security risk

## test use case classification

### 1. Script Tag XSS Prevention (script label XSS prevention)

```typescript
// basic script injection
'<script>window.xssAttack = true;</script>Hello World'

// code block with script
'```html\n<script>window.xssAttack2 = true;</script>\n```'

// script label with property
'<script type="text/javascript" src="malicious.js">window.xssAttack3 = true;</script>'
```

### 2. Event Handler XSS Prevention (event handler XSS prevention)

```typescript
// click event
'<div onclick="window.xssClick = true;">Click me</div>'

// load event
'<img src="invalid.jpg" onload="window.xssLoad = true;" alt="test" />'

// error event
'<img src="invalid.jpg" onerror="window.xssError = true;" alt="test" />'

// other event handlers
['onmouseover', 'onmouseout', 'onfocus', 'onblur', 'onkeypress', 'onsubmit']
```

### 3. JavaScript Protocol XSS Prevention (JavaScript protocol XSS prevention)

```typescript
// JavaScript protocol in link
'[Click me](javascript:window.xssJsProtocol = true;)'

// JavaScript protocol in image
'<img src="javascript:window.xssJsImg = true;" alt="test" />'

// JavaScript in Data URL
'<a href="data:text/html,<script>window.xssDataUrl = true;</script>">Click</a>'
```

### 4. HTML Entity Encoding XSS Prevention (HTML entity encoding XSS prevention)

```typescript
// HTML entity encoding of script label
'&lt;script&gt;window.xssEntityScript = true;&lt;/script&gt;'

// hexadecimal encoding
'&#x3C;script&#x3E;window.xssHex = true;&#x3C;/script&#x3E;'

// decimal encoding
'&#60;script&#62;window.xssDecimal = true;&#60;/script&#62;'
```

### 5. CSS Injection XSS Prevention (CSS injection XSS prevention)

```typescript
// CSS expression
'<div style="background: expression(window.xssCss = true);">Test</div>'

// JavaScript URL in CSS
'<div style="background-image: url(javascript:window.xssCssJs = true);">Test</div>'
```

### 6. Advanced Attack Vectors (advanced attack vector)

```typescript
// Unicode script label
'<\u0073cript>window.xssUnicode = true;</\u0073cript>'

// nested script label
'<scr<script>ipt>window.xssNested = true;</scr</script>ipt>'

// case mixing
'<ScRiPt>window.xssCase = true;</ScRiPt>'

// script in SVG
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

// codeblockin的 HTML
'```html\n<script>window.xssCodeBlock = true;</script>\n```'

// 内联codein的script
'This is `<script>window.xssInlineCode = true;</script>` inline code'
```

### 10. LaTeX Injection Security (LaTeX 注入安all)

```typescript
// LaTeX in的link
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

### streamingrender安alltest

确保atstreamingrender过程in的contentupdate不会引入安all风险：

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

## performanceandresource安alltest

### memory耗尽防护

```typescript
// extra largecontent
const largeContent = '<script>window.xssLarge = true;</script>'.repeat(10000)

// depth嵌套
let nestedContent = '<script>window.xssNested = true;</script>'
for (let i = 0; i < 100; i++) {
  nestedContent = `<div>${nestedContent}</div>`
}
```

### 递归structure防护

```typescript
// 递归 Markdown structure
let recursiveMarkdown = '<script>window.xssRecursive = true;</script>'
for (let i = 0; i < 50; i++) {
  recursiveMarkdown = `> ${recursiveMarkdown}`
}
```

## error handling安alltest

### sensitiveinformation泄露防护

确保error handling过程in不会泄露sensitiveinformation：

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

### dynamicscript元素check

确保component不会createdynamicscript元素：

```typescript
const maliciousContent = '<script>window.xssCSP = true;</script>'
// validatepageinnot existinclude恶意code的script元素
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

recommendations这些安alltestshouldoverrideby下scenario：

1. **normalflow**：确保normal的 Markdown content能够correctrender
2. **边界情况**：emptycontent、empty/undefined content
3. **恶意input**：各种 XSS 攻击vector
4. **configurationgroup合**：different的propertyconfigurationgroup合
5. **performance极限**：large number ofdataanddepth嵌套
6. **error handling**：exception情况的安allhandle

## 安allrecommendations

### 开发recommendations

1. **default安all**：default情况下shoulddisable HTML render
2. **inputvalidate**：对alluserinput进行严格validate
3. **outputencoding**：确保alloutput都经过适when的encoding
4. **CSP 策略**：实施严格的content安all策略
5. **定期审计**：定期进行安all审计andtest

### 生产deployrecommendations

1. **monitor告警**：settings安alleventmonitorand告警
2. **log记录**：记录all可疑的inputandrendertry
3. **versionupdate**：及timeupdatedependency库byfix安all漏洞
4. **permissioncontrol**：实施适when的userpermissioncontrol

## conclusion

这个安alltest套件提供了comprehensive的 XSS 攻击防护test，涵盖了frombasic的script注入toadvanced的many语言攻击vector。through这些test，can确保 `EnhanceMarkdown` componentat各种攻击scenario下都能keep安all性。

定期run这些test，并based on新发现的攻击vector及timeupdatetestuse case，ismaintaincomponent安all性的heavy要措施。 
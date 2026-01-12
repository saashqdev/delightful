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

// Zero-width character bypass
'<scr\u200Bipt>window.xssZeroWidth = true;</scr\u200Bipt>'
```

### 7. Polyglot XSS Attacks (Polyglot XSS Attacks)

```typescript
// Polyglot payload
'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'window.xssPolyglot=true\'>'

// Context breaking
'"></script><script>window.xssContextBreak = true;</script>'
```

### 8. Filter Evasion Techniques (Filter Evasion Techniques)

```typescript
// Comment bypass
'<scr<!---->ipt>window.xssComment = true;</scr<!---->ipt>'

// Newline bypass
'<scr\nipt>window.xssNewline = true;</scr\nipt>'

// Tab character bypass
'<scr\tipt>window.xssTab = true;</scr\tipt>'
```

### 9. Markdown-specific Attack Vectors (Markdown-specific Attack Vectors)

```typescript
// Malicious reference link
`[Click here][malicious]
[malicious]: javascript:window.xssReference = true;`

// HTML in code block
'```html\n<script>window.xssCodeBlock = true;</script>\n```'

// Script in inline code
'This is `<script>window.xssInlineCode = true;</script>` inline code'
```

### 10. LaTeX Injection Security (LaTeX Injection Security)

```typescript
// LaTeX link
'$\\href{javascript:window.xssLatex=true}{Click}$'

// LaTeX command injection
'$$\\begin{document}\\href{javascript:window.xssLatexCmd=true}{text}\\end{document}$$'
```

## Security Configuration Tests

### allowHtml=false Security Test

Ensure that when `allowHtml` is set to `false`, HTML content is correctly escaped:

```typescript
{
  content: '<script>window.xssNoHtml = true;</script><b>Bold text</b>',
  allowHtml: false
}
```

### Streaming Render Security Test

Ensure that content updates during the streaming render process do not introduce security risks:

```typescript
{
  content: '<script>window.xssStream = true;</script>Streaming content',
  isStreaming: true
}
```

### Custom Component Security Test

Test that custom component configuration cannot be maliciously exploited:

```typescript
{
  components: {
    script: MaliciousComponent
  }
}
```

## Performance and Resource Security Tests

### Memory Exhaustion Protection

```typescript
// Extra large content
const largeContent = '<script>window.xssLarge = true;</script>'.repeat(10000)

// Deep nesting
let nestedContent = '<script>window.xssNested = true;</script>'
for (let i = 0; i < 100; i++) {
  nestedContent = `<div>${nestedContent}</div>`
}
```

### Recursive Structure Protection

```typescript
// Recursive Markdown structure
let recursiveMarkdown = '<script>window.xssRecursive = true;</script>'
for (let i = 0; i < 50; i++) {
  recursiveMarkdown = `> ${recursiveMarkdown}`
}
```

## Error Handling Security Tests

### Sensitive Information Leak Protection

Ensure that sensitive information is not leaked during error handling:

```typescript
const problematicContent = '<script>throw new Error("XSS attempt: " + document.cookie);</script>'
```

### invalid Unicode sequencehandle

```typescript
const invalidUnicode = '\uD800<script>window.xssInvalidUnicode = true;</script>\uDFFF'
```

## CSP (Content Security Policy) Compliance Tests

### Inline Style Check

Ensure that the component does not create inline styles that violate CSP:

```typescript
const contentWithInlineStyles = '<div style="background: red; color: blue;">Test</div>'
```

### Dynamic Script Element Check

Ensure that the component does not create dynamic script elements:

```typescript
const maliciousContent = '<script>window.xssCSP = true;</script>'
// Validate that the page does not contain script elements with malicious code
```

## Test Execution Guide

### Run Tests

```bash
# Run basic security tests
npm test -- __tests__/security.test.tsx

# Run advanced security tests
npm test -- __tests__/advanced-security.test.tsx

# Run configuration security tests
npm test -- __tests__/security-config.test.tsx
```

### Test Coverage

Recommend these security tests should cover the following scenarios:

1. **Normal flow**: Ensure normal Markdown content can be correctly rendered
2. **Edge cases**: Empty content, null/undefined content
3. **Malicious input**: Various XSS attack vectors
4. **Configuration combinations**: Different property configuration combinations
5. **Performance limits**: Large amounts of data and deep nesting
6. **Error handling**: Secure handling of exception situations

## Security Recommendations

### Development Recommendations

1. **Default security**: Should disable HTML rendering by default
2. **Input validation**: Strictly validate all user input
3. **Output encoding**: Ensure all output goes through appropriate encoding
4. **CSP policy**: Implement strict Content Security Policy
5. **Regular audits**: Conduct regular security audits and tests

### Production Deployment Recommendations

1. **Monitoring alerts**: Set up security event monitoring and alerts
2. **Log recording**: Record all suspicious input and render attempts
3. **Version updates**: Timely update dependency libraries to fix security vulnerabilities
4. **Permission control**: Implement appropriate user permission control

## Conclusion

This security test suite provides comprehensive XSS attack protection tests, covering from basic script injection to advanced polyglot attack vectors. Through these tests, we can ensure the `EnhanceMarkdown` component maintains security under various attack scenarios.

Regularly running these tests and updating test cases based on newly discovered attack vectors are important measures to maintain component security. 
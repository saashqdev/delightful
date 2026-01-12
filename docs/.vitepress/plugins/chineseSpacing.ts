/**
 * 中Englishbetween添add空格的VitePressplugin
 * atrendertime自动at中Englishbetween添add空格，不modify原始mdfile
 */

// matchChinese字符的正则expression
const chineseRegex = /[\u4e00-\u9fa5]/
// matchEnglish字母的正则expression
const englishRegex = /[a-zA-Z0-9]/

/**
 * at中Englishbetween添add空格
 * @param text needhandle的文本
 * @returns handleback的文本
 */
function addSpaceBetweenChineseAndEnglish(text: string): string {
  if (!text) return text
  
  // noChineseorEnglish，直接return
  if (!chineseRegex.test(text) || !englishRegex.test(text)) return text
  
  // atChinese和Englishbetween添add空格
  return text
    // atChineseback面add空格(ifback面isEnglish)
    .replace(/([\u4e00-\u9fa5])([a-zA-Z0-9])/g, '$1 $2')
    // atEnglishback面add空格(ifback面isChinese)
    .replace(/([a-zA-Z0-9])([\u4e00-\u9fa5])/g, '$1 $2')
}

/**
 * createVitePress markdown-itplugin
 */
export function chineseSpacingPlugin(md: any) {
  // save原始的render器
  const originalRender = md.renderer.rules.text
  
  // heavy写text标记的rendermethod
  md.renderer.rules.text = (tokens: any[], idx: number, options: any, env: any, self: any) => {
    // getwhen前文本content
    const content = tokens[idx].content
    
    // handle文本，添add空格
    const processed = addSpaceBetweenChineseAndEnglish(content)
    
    // replace原始content
    tokens[idx].content = processed
    
    // invoke原始render器completerender
    return originalRender(tokens, idx, options, env, self)
  }
  
  // handle内联label中的文本
  const originalInlineRender = md.renderer.rules.inline
  if (originalInlineRender) {
    md.renderer.rules.inline = (tokens: any[], idx: number, options: any, env: any, self: any) => {
      const token = tokens[idx]
      
      if (token.children) {
        token.children.forEach((child: any) => {
          if (child.type === 'text') {
            child.content = addSpaceBetweenChineseAndEnglish(child.content)
          }
        })
      }
      
      return originalInlineRender(tokens, idx, options, env, self)
    }
  }
}

/**
 * VitePressplugin入口
 */
export default function chineseSpacing() {
  return {
    name: 'vitepress-plugin-chinese-spacing',
    enforce: 'pre' as const, // 明确指定for 'pre' type
    
    // extensionVitePressconfiguration
    config(config: any) {
      // createmarkdownconfigurationifnot exist
      if (!config.markdown) {
        config.markdown = {}
      }
      
      // 确保config.markdown.configisone个function
      const originalMarkdownConfig = config.markdown.config || (() => {})
      
      // extensionmarkdownconfiguration
      config.markdown.config = (md: any) => {
        // application原始configuration
        originalMarkdownConfig(md)
        
        // 添add我们的plugin
        md.use(chineseSpacingPlugin)
      }
    }
  }
} 
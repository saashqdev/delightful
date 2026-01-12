/**
 * VitePress plugin to add spaces between Chinese and English
 * automatically add spaces between Chinese and English at render time, without modifying original md files
 */

// regex to match Chinese characters
const chineseRegex = /[\u4e00-\u9fa5]/
// regex to match English letters
const englishRegex = /[a-zA-Z0-9]/

/**
 * add spaces between Chinese and English
 * @param text text that needs to be handled
 * @returns handled text
 */
function addSpaceBetweenChineseAndEnglish(text: string): string {
  if (!text) return text
  
  // no Chinese or English, directly return
  if (!chineseRegex.test(text) || !englishRegex.test(text)) return text
  
  // add spaces between Chinese and English
  return text
    // add space after Chinese (if the back is English)
    .replace(/([\u4e00-\u9fa5])([a-zA-Z0-9])/g, '$1 $2')
    // add space after English (if the back is Chinese)
    .replace(/([a-zA-Z0-9])([\u4e00-\u9fa5])/g, '$1 $2')
}

/**
 * create VitePress markdown-it plugin
 */
export function chineseSpacingPlugin(md: any) {
  // save original renderer
  const originalRender = md.renderer.rules.text
  
  // overwrite text tag render method
  md.renderer.rules.text = (tokens: any[], idx: number, options: any, env: any, self: any) => {
    // get current text content
    const content = tokens[idx].content
    
    // handle text, add spaces
    const processed = addSpaceBetweenChineseAndEnglish(content)
    
    // replace original content
    tokens[idx].content = processed
    
    // invoke original renderer to complete rendering
    return originalRender(tokens, idx, options, env, self)
  }
  
  // handle text in inline labels
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
 * VitePress plugin entry point
 */
export default function chineseSpacing() {
  return {
    name: 'vitepress-plugin-chinese-spacing',
    enforce: 'pre' as const, // explicitly specify as 'pre' type
    
    // extend VitePress configuration
    config(config: any) {
      // create markdown configuration if not exist
      if (!config.markdown) {
        config.markdown = {}
      }
      
      // ensure config.markdown.config is a function
      const originalMarkdownConfig = config.markdown.config || (() => {})
      
      // extend markdown configuration
      config.markdown.config = (md: any) => {
        // apply original configuration
        originalMarkdownConfig(md)
        
        // add our plugin
        md.use(chineseSpacingPlugin)
      }
    }
  }
} 
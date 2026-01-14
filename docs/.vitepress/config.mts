import { defineConfig } from 'vitepress'
import en from './locales/en'
import zh from './locales/zh'

// Function to add spaces between Chinese and English characters
function addSpaceBetweenChineseAndEnglish(text: string): string {
  if (!text) return text
  
  // Add spaces between Chinese and English characters
  return text
    // Add space after Chinese if followed by English
    .replace(/([\u4e00-\u9fa5])([a-zA-Z0-9])/g, '$1 $2')
    // Add space after English if followed by Chinese
    .replace(/([a-zA-Z0-9])([\u4e00-\u9fa5])/g, '$1 $2')
}

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Delightful Docs",
  description: "The New Generation Enterprise-level AI Application Innovation Engine",
  locales: {
    root: en,
    zh: zh
  },
  ignoreDeadLinks: true,
  // Markdown configuration
  markdown: {
    config: (md) => {
      // Save original renderer
      const originalRender = md.renderer.rules.text || ((tokens, idx, options, env, self) => {
        return self.renderToken(tokens, idx, options)
      })
      
      // Rewrite text marker rendering method
      md.renderer.rules.text = (tokens, idx, options, env, self) => {
        // Get current text content
        const content = tokens[idx].content
        
        // Process text, add spaces
        const processed = addSpaceBetweenChineseAndEnglish(content)
        
        // Replace original content
        tokens[idx].content = processed
        
        // Call original renderer to complete rendering
        return originalRender(tokens, idx, options, env, self)
      }
      
      // Process text in inline tags
      if (md.renderer.rules.inline) {
        const originalInlineRender = md.renderer.rules.inline
        
        md.renderer.rules.inline = (tokens, idx, options, env, self) => {
          const token = tokens[idx]
          
          if (token.children) {
            token.children.forEach((child) => {
              if (child.type === 'text') {
                child.content = addSpaceBetweenChineseAndEnglish(child.content)
              }
            })
          }
          
          return originalInlineRender(tokens, idx, options, env, self)
        }
      }
    }
  },
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    socialLinks: [
      { icon: 'github', link: 'https://github.com/saashqdev/delightful/delightful' }
    ],
    search: {
      provider: 'local'
    },
    outline: {
      level: 'deep',
    }
  }
}) 
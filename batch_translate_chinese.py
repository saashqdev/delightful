#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Comprehensive Chinese to English translation script.
Handles all file types with context-aware replacements.
"""
import os
import re

TRANSLATIONS = {
    # ChineseSpacing plugin
    "中Englishbetween添add空格的VitePressplugin": "VitePress plugin to add spaces between Chinese and English",
    "atrendertime自动at中Englishbetween添add空格，不modify原始mdfile": "automatically add spaces between Chinese and English at render time, without modifying original md files",
    "matchChinese字符的正则expression": "regex to match Chinese characters",
    "matchEnglish字母的正则expression": "regex to match English letters",
    "at中Englishbetween添add空格": "add spaces between Chinese and English",
    "needhandle的文本": "text that needs to be handled",
    "handleback的文本": "handled text",
    "noChineseorEnglish": "no Chinese or English",
    "直接return": "directly return",
    "atChinese和Englishbetween添add空格": "add spaces between Chinese and English",
    "atChineseback面add空格(ifback面isEnglish)": "add space after Chinese (if the back is English)",
    "atEnglishback面add空格(ifback面isChinese)": "add space after English (if the back is Chinese)",
    "createVitePress markdown-itplugin": "create VitePress markdown-it plugin",
    "save原始的render器": "save original renderer",
    "heavy写text标记的rendermethod": "overwrite text tag render method",
    "getwhen前文本content": "get current text content",
    "handle文本，添add空格": "handle text, add spaces",
    "replace原始content": "replace original content",
    "invoke原始render器completerender": "invoke original renderer to complete rendering",
    
    # Locale config translations
    "输入关键字search...": "Type keywords...",
    "亮色模式": "Light Mode",
    "暗色模式": "Dark Mode",
    "跟随system": "Follow System",
    "微博": "Weibo",
    "知乎": "Zhihu",
    "语雀": "Yuque",
    "展开代码": "Show Code",
    "收起代码": "Hide Code",
    "编辑代码可实时预览": "Edit code with real-time preview",
    "仅 index 可编辑": "Only index file is editable",
    "拷贝到 Sketch": "Copy to Sketch",
    "拷贝for Sketch Group": "Copy as Sketch Group",
    "拷贝for Sketch Symbol": "Copy as Sketch Symbol",
    "如何paste到 SKetch？": "How to paste to Sketch?",
    "在 CodeSandbox 中open": "Open in CodeSandbox",
    "在 StackBlitz 中open": "Open in StackBlitz",
    "在独立page中open": "Open in separate page",
    "page未找到": "PAGE NOT FOUND",
    "returnHome": "Back to homepage",
    "property名": "Name",
    "描述": "Description",
    "class型": "Type",
    "defaultvalue": "Default",
    "必选": "(required)",
    "实验性": "Experimental",
    "废弃": "Deprecated",
    "必须enable apiParser 才能使用自动 API feature": "apiParser must be enabled to use auto-generated API",
    "property定义正在解析中，稍等片刻...": "Properties definition is resolving, wait a moment...",
    "未找到 {id} component的property定义": "Properties definition not found for {id} component",
    "documentation": "Doc",
    "最backupdatetime：": "Last updated: ",
    "帮助improvement此documentation": "Improve this documentation",
    "topone篇": "PREV",
    "bottomone篇": "NEXT",
    "未找到相关内容": "No content was found",
    "load中...": "Loading...",
    "侧边菜单": "Sidebar",
    
    # Flow Material Panel
    "懒loadSubGroupcomponent，onlywhencomponent进入视口time才renderinside容": "Lazy load SubGroup component, only render inside when component enters viewport",
    "Whencomponent进入视口time": "When component enters viewport",
    "标记foralreadyload，避免re复render": "Mark as already loaded, avoid re-rendering",
    "componentalreadyloadbackcan解除观察": "component already loaded, can unobserve",
    "提front100pxstartload": "start loading 100px ahead",
    "visibletimetrigger": "visible threshold",
    "useuseCallbackoptimizationrenderMaterialItemfunction，避免不必要ofrenewcreate": "use useCallback to optimize rendering Material Item function, avoid unnecessary new creation",
    "use解构赋valuegetschemainof property": "use destructuring to get schema in property",
    "createCHSitem固定ofkey，avoid each timerendergeneratenewstring": "create item fixed key, avoid generating new string every render",
    "直接returnMaterialItemFncomponent，传递必要ofprops": "directly return MaterialItemFn component, pass necessary props",
    "useuseMemooptimizationnodelistrender，只atnodeListorMaterialItemFn变化timerenewcalculate": "use useMemo to optimize node list rendering, only recalculate when nodeList or MaterialItemFn changes",
    "cachenodeListof结果，avoid each timerendertime都createnew引用": "cache nodeList result, avoid creating new reference every render time",
    "动态ofnodelist": "dynamic node list",
    "getdividegroupnodelist": "get divided group node list",
    "Filter出hasnodedataofdividegrouplist，并往里边塞nodeofschema": "Filter out divided group list with node data, and put node schema inside",
    
    # Additional common patterns
    "Whether from endpoint menu": "Whether from endpoint menu",
    "Item passed from upper layer": "Item passed from upper layer",
    "Use React.memo to wrap PanelMaterial component, avoiding unnecessary re-renders": "Use React.memo to wrap PanelMaterial component, avoiding unnecessary re-renders",
}

def translate_content(content):
    """Apply translations to content"""
    # Sort by length (longest first) to avoid partial replacements
    sorted_trans = sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True)
    
    for chinese, english in sorted_trans:
        if chinese in content:
            content = content.replace(chinese, english)
    
    return content

def process_file(filepath):
    """Process a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            original = f.read()
        
        translated = translate_content(original)
        
        if translated != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(translated)
            return True
        return False
    except Exception as e:
        print(f"Error: {filepath} - {e}")
        return False

def main():
    """Main entry point"""
    base = os.getcwd()
    
    # List of all files with Chinese that need translation
    files_to_process = [
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\components\LazySubGroup\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\hooks\useMaterial.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\components\VirtualNodeList.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\MaterialItem\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useMaterialPanel.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\MaterialItem\hooks\useAddItem.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useTab.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useMaterialSearch.ts",
        r"frontend\delightful-flow\.dumi\tmp\dumi\locales\config.ts",
        r"docs\.vitepress\plugins\chineseSpacing.ts",
    ]
    
    count = 0
    for file_rel in files_to_process:
        filepath = os.path.join(base, file_rel)
        if os.path.exists(filepath):
            if process_file(filepath):
                count += 1
                print(f"✓ {file_rel}")
    
    print(f"\n✅ Translated {count} files")

if __name__ == '__main__':
    main()

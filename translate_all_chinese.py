#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Smart translation script to convert all remaining Chinese to English.
Uses context-aware replacements based on code patterns.
"""
import os
import re
import glob
from pathlib import Path

# Comprehensive Chinese to English translation dictionary
TRANSLATIONS = {
    # Component/UI related
    "懒loadSubGroupcomponent": "Lazy load SubGroup component",
    "onlywhencomponent进入视口time才renderinside容": "only render when component enters viewport",
    "进入视口time": "enters viewport",
    "标记foralreadyload": "Mark as already loaded",
    "避免re复render": "avoid re-rendering",
    "componentalreadyloadbackcan解除观察": "component already loaded, can unobserve",
    "提front100pxstartload": "start loading 100px ahead",
    "visibletimetrigger": "visible time trigger",
    "Whencomponent进入视口time": "When component enters viewport",
    
    # Material panel related
    "Whether from endpoint menu": "Whether from endpoint menu",
    "Item passed from upper layer": "Item passed from upper layer",
    "Use React.memo to wrap PanelMaterial component, avoiding unnecessary re-renders": "Use React.memo to wrap PanelMaterial component, avoiding unnecessary re-renders",
    "useuseCallbackoptimizationrenderMaterialItemfunction": "Use useCallback to optimize rendering Material Item function",
    "避免不必要ofrenewcreate": "avoid unnecessary new creation",
    "use解构赋valuegetschemainof property": "use destructuring to get schema in property",
    "createCHSitem固定ofkey": "create item fixed key",
    "avoid each timerendergeneratenewstring": "avoid generating new string every time rendering",
    "直接returnMaterialItemFncomponent": "directly return MaterialItemFn component",
    "传递必要ofprops": "pass necessary props",
    "useuseMemooptimizationnodelistrender": "use useMemo to optimize node list rendering",
    "只atnodeListorMaterialItemFn变化timerenewcalculate": "only recalculate when nodeList or MaterialItemFn changes",
    
    # Cache and performance
    "cachenodeListof结果": "cache nodeList result",
    "avoid each timerendertime都createnew引用": "avoid creating new reference every render time",
    "动态ofnodelist": "dynamic node list",
    "getdividegroupnodelist": "get divided group node list",
    "Filter出hasnodedataofdividegrouplist": "Filter out divided group list with node data",
    "并往里边塞nodeofschema": "and put node schema inside",
    
    # General comments
    "Whencomponent": "When component",
    "标记": "Mark",
    "避免": "avoid",
    "提前": "advance",
    "start": "start",
    "load": "load",
    "trigger": "trigger",
    "visible": "visible",
    "进入": "enter",
    "视口": "viewport",
    "组件": "component",
    "动态": "dynamic",
    "缓存": "cache",
    "结果": "result",
    "时间": "time",
    "创建": "create",
    "引用": "reference",
    "每次": "every time",
    "重新": "re-",
    "渲染": "render",
    
    # Performance optimization
    "performance": "performance",
    "optimization": "optimization",
    "unnecessary": "unnecessary",
    "re-render": "re-render",
    "avoid": "avoid",
    "unnecessary re-render": "unnecessary re-render",
    
    # Material search and filter
    "keyword": "keyword",
    "filter": "filter",
    "search": "search",
    
    # Node and schema related
    "schema": "schema",
    "node": "node",
    "nodeTypes": "nodeTypes",
    "nodeList": "nodeList",
    "id": "id",
    "label": "label",
    "data": "data",
    
    # Common patterns from malformed translations
    "ofparameter": "of parameter",
    "prefixfor": "prefix for",
    "fornull": "for null",
    "willreturnof": "will return",
    "thenreturnof": "then return",
    "更具": "more",
    "使得": "making",
    "发生器": "generator",
    "group合": "generator",
    "的": " the ",
    
    # Markdown and documentation
    "## ": "## ",
    "### ": "### ",
    "#### ": "#### ",
    
    # Extension and HTML
    "popup": "popup",
    "manifest": "manifest",
    "extension": "extension",
}

def smart_translate_chinese(content):
    """
    Intelligently translate Chinese content in code files.
    Applies longest patterns first to avoid partial replacements.
    """
    # Add context-specific translations based on file type
    translations = TRANSLATIONS.copy()
    
    # Sort by length (longest first) to avoid partial replacements
    sorted_pairs = sorted(translations.items(), key=lambda x: len(x[0]), reverse=True)
    
    for chinese, english in sorted_pairs:
        if chinese in content:
            content = content.replace(chinese, english)
    
    return content

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            original = f.read()
        
        translated = smart_translate_chinese(original)
        
        if translated != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(translated)
            return True
        return False
    except Exception as e:
        print(f"Error in {filepath}: {e}")
        return False

def main():
    """Main processing"""
    base_dir = os.getcwd()
    
    # Files with Chinese that need translation
    files_to_translate = [
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\components\LazySubGroup\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\hooks\useMaterial.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\components\VirtualNodeList.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\MaterialItem\index.tsx",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useMaterialPanel.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\components\PanelMaterial\MaterialItem\hooks\useAddItem.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useTab.ts",
        r"frontend\delightful-flow\src\DelightfulFlow\components\FlowMaterialPanel\hooks\useMaterialSearch.ts",
    ]
    
    count = 0
    for file in files_to_translate:
        filepath = os.path.join(base_dir, file)
        if os.path.exists(filepath) and translate_file(filepath):
            count += 1
            print(f"✓ {file}")
    
    print(f"\nTranslated {count} files")

if __name__ == '__main__':
    main()

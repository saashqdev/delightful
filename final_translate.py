#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# More comprehensive translations
translations = {
    # Test data and mock data translations
    "位置": "position",
    "新的": "new",
    "绘制": "draw",
    "绘制竖线": "draw vertical line",
    "鼠标位置at原始坐标系中的位置": "mouse position in original coordinate system",
    "鼠标位置相for画布的坐标": "mouse position relative to canvas",
    "replace为default图片": "replace with default image",
    "防止死": "prevent dead",
    "一个简单的树结构": "a simple tree structure",
    "不yes自环": "not a self loop",
    "个": "item",
    "使用": "use",
    "倍": "times",
    "stop": "stop",
    "memory使用": "memory usage",
    "切换": "toggle",
    "切换为": "switch to",
    "和边": "and edges",
    "图": "graph",
    "定期测量memory": "measure memory periodically",
    "whenfront": "current",
    "data": "data",
    "数量": "count",
    "无": "none",
    "来确保render已": "to ensure rendered",
    "浏览器不support": "browser not supported",
    "清除": "clear",
    "render": "render",
    "generate": "generate",
    "的": "of",
    "monitor": "monitor",
    "监测": "measure",
    "until达to指定数量": "until reaching specified count",
    "确保all": "ensure all",
    "简单": "simple",
    "边数不超过might的最largeconnection数": "edge count not exceeding max possible connections",
    "边数量": "edge count",
    "边的数量yes": "edge count is",
    "都haveconnection": "all connected",
    "isolation": "isolation",
    "额外的随机边": "extra random edges",
    "对比": "compare",
    "yesno发生了变化": "whether changed",
    "估计的each": "estimated per",
    "使用virtual scrolling": "use virtual scroll",
    "定义一个threshold": "define a threshold",
    "actual使用timemightneedcustom更合适的compare": "may need custom comparison in actual use",
    "when": "when",
    "数量超过这个thresholdtimeenablevirtual scrolling": "enable virtual scroll when count exceeds threshold",
    "数量超过threshold": "count exceeds threshold",
    "yesnoneed使用virtual scrolling": "whether virtual scroll needed",
    "depthcomparemight nothigh效": "deep comparison may not be efficient",
    "group": "group",
    "adjustment": "adjust",
    "这里仅compare引用": "only compare reference here",
    "need根据actual": "need based on actual",
    "项的height": "item height",
    "清null": "clear",
    "close": "close",
    "初始": "initial",
    "初始边": "initial edges",
    "enable": "enable",
    "基本": "basic",
    "basicfunctionality": "basic functionality",
    "定义": "define",
    "已选中": "selected",
    "拖拽": "drag",
    "control面板": "control panel",
    "新": "new",
    "添add": "add",
    "点击callback": "click callback",
    "用in": "for",
    "的基本交互和functionality": "basic interaction and functionality",
    "disable": "disable",
    "manage": "manage",
    "网格吸附": "grid snap",
    "custom": "custom",
    "connection边的callback": "edge connection callback",
    "选中": "select",
    "by避免不必要的heavy新": "to avoid unnecessary",
    "decrease不必要的heavy新render": "reduce unnecessary re-rendering",
    "则标记为已render并": "mark as rendered and",
    "package装": "wrap",
    "单独提取render项": "extract render items separately",
    "只haveat面板expand且已": "only when panel expanded and",
    "只compare关键": "only compare key",
    "它": "it",
    "并使用": "and use",
    "并添addcustomcompare": "and add custom comparison",
    "collapse面板变更": "collapse panel change",
    "datatime才render": "render only when data",
    "yes首次expand": "is first expansion",
    "render子项": "render child items",
    "cache": "cache",
    "追踪子面板的expand": "track child panel expansion",
    "避免不必要的render": "avoid unnecessary rendering",
    "避免every timeexpand都heavy新": "avoid re-rendering every expansion",
    
    # Common phrases in comments
    "// when": "// When",
    "// if": "// If",
    "// 则": "// Then",
    "// else": "// Otherwise",
    "// create": "// Create",
    "// generate": "// Generate",
    "// 定义": "// Define",
    "// initialize": "// Initialize",
    "// calculation": "// Calculate",
    "// get": "// Get",
    "// set": "// Set",
    "// update": "// Update",
    "// 添add": "// Add",
    "// delete": "// Delete",
    "// 移除": "// Remove",
    "// check": "// Check",
    "// verify": "// Verify",
    "// compare": "// Compare",
    "// filter": "// Filter",
    "// transform": "// Transform",
    "// sort": "// Sort",
    "// search": "// Search",
    "// 查询": "// Query",
    "// listen": "// Listen",
    "// handle": "// Handle",
    "// trigger": "// Trigger",
    "// clean": "// Clean",
    "// release": "// Release",
    "// render": "// Render",
    "// 卸载": "// Unmount",
    "// hide": "// Hide",
    "// show": "// Show",
    "// enable": "// Enable",
    "// disable": "// Disable",
    
    # Boolean/common patterns
    "yesno": "whether",
    "variable": "variable",
    "parameter": "parameter",
    "property": "property",
    "method": "method",
    "function": "function",
    "type": "type",
    "value": "value",
    "state": "state",
    "event": "event",
    "listener": "listener",
    "callback": "callback",
    "async": "async",
    "sync": "sync",
    "concurrent": "concurrent",
    "sequence": "sequence",
    "parallel": "parallel",
}

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations (prioritize longer matches first)
        sorted_translations = sorted(translations.items(), key=lambda x: len(x[0]), reverse=True)
        for chinese, english in sorted_translations:
            while chinese in content:
                content = content.replace(chinese, english)
                change_count += 1
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            filename = filepath.split('\\')[-1]
            print(f"✓ {filename} ({change_count} replacements)")
            return True
        return False
    except Exception as e:
        print(f"✗ Error in {filepath}: {e}")
        return False

def main():
    """Main function"""
    base_dir = "c:\\Users\\kubew\\magic\\frontend\\delightful-flow\\src\\DelightfulFlow"
    
    count = 0
    for pattern in ['**/*.ts', '**/*.tsx']:
        for filepath in glob.glob(os.path.join(base_dir, pattern), recursive=True):
            if translate_file(filepath):
                count += 1
    
    print(f"\n✅ Total files translated: {count}")

if __name__ == '__main__':
    main()

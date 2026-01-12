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
    "鼠标位置在原始坐标系中的位置": "mouse position in original coordinate system",
    "鼠标位置相对于画布的坐标": "mouse position relative to canvas",
    "替换为默认图片": "replace with default image",
    "防止死": "prevent dead",
    "一个简单的树结构": "a simple tree structure",
    "不是自环": "not a self loop",
    "个": "item",
    "使用": "use",
    "倍": "times",
    "停止": "stop",
    "内存使用": "memory usage",
    "切换": "toggle",
    "切换为": "switch to",
    "和边": "and edges",
    "图": "graph",
    "定期测量内存": "measure memory periodically",
    "当前": "current",
    "数据": "data",
    "数量": "count",
    "无": "none",
    "来确保渲染已": "to ensure rendered",
    "浏览器不支持": "browser not supported",
    "清除": "clear",
    "渲染": "render",
    "生成": "generate",
    "的": "of",
    "监控": "monitor",
    "监测": "measure",
    "直到达到指定数量": "until reaching specified count",
    "确保所有": "ensure all",
    "简单": "simple",
    "边数不超过可能的最大连接数": "edge count not exceeding max possible connections",
    "边数量": "edge count",
    "边的数量是": "edge count is",
    "都有连接": "all connected",
    "隔离": "isolation",
    "额外的随机边": "extra random edges",
    "对比": "compare",
    "是否发生了变化": "whether changed",
    "估计的每个": "estimated per",
    "使用虚拟滚动": "use virtual scroll",
    "定义一个阈值": "define a threshold",
    "实际使用时可能需要自定义更合适的比较": "may need custom comparison in actual use",
    "当": "when",
    "数量超过这个阈值时启用虚拟滚动": "enable virtual scroll when count exceeds threshold",
    "数量超过阈值": "count exceeds threshold",
    "是否需要使用虚拟滚动": "whether virtual scroll needed",
    "深度比较可能不高效": "deep comparison may not be efficient",
    "组": "group",
    "调整": "adjust",
    "这里仅比较引用": "only compare reference here",
    "需要根据实际": "need based on actual",
    "项的高度": "item height",
    "清空": "clear",
    "关闭": "close",
    "初始": "initial",
    "初始边": "initial edges",
    "启用": "enable",
    "基本": "basic",
    "基础功能": "basic functionality",
    "定义": "define",
    "已选中": "selected",
    "拖拽": "drag",
    "控制面板": "control panel",
    "新": "new",
    "添加": "add",
    "点击回调": "click callback",
    "用于": "for",
    "的基本交互和功能": "basic interaction and functionality",
    "禁用": "disable",
    "管理": "manage",
    "网格吸附": "grid snap",
    "自定义": "custom",
    "连接边的回调": "edge connection callback",
    "选中": "select",
    "以避免不必要的重新": "to avoid unnecessary",
    "减少不必要的重新渲染": "reduce unnecessary re-rendering",
    "则标记为已渲染并": "mark as rendered and",
    "包装": "wrap",
    "单独提取渲染项": "extract render items separately",
    "只有在面板展开且已": "only when panel expanded and",
    "只比较关键": "only compare key",
    "它": "it",
    "并使用": "and use",
    "并添加自定义比较": "and add custom comparison",
    "折叠面板变更": "collapse panel change",
    "数据时才渲染": "render only when data",
    "是首次展开": "is first expansion",
    "渲染子项": "render child items",
    "缓存": "cache",
    "追踪子面板的展开": "track child panel expansion",
    "避免不必要的渲染": "avoid unnecessary rendering",
    "避免每次展开都重新": "avoid re-rendering every expansion",
    
    # Common phrases in comments
    "// 当": "// When",
    "// 如果": "// If",
    "// 则": "// Then",
    "// 否则": "// Otherwise",
    "// 创建": "// Create",
    "// 生成": "// Generate",
    "// 定义": "// Define",
    "// 初始化": "// Initialize",
    "// 计算": "// Calculate",
    "// 获取": "// Get",
    "// 设置": "// Set",
    "// 更新": "// Update",
    "// 添加": "// Add",
    "// 删除": "// Delete",
    "// 移除": "// Remove",
    "// 检查": "// Check",
    "// 验证": "// Verify",
    "// 比较": "// Compare",
    "// 过滤": "// Filter",
    "// 转换": "// Transform",
    "// 排序": "// Sort",
    "// 搜索": "// Search",
    "// 查询": "// Query",
    "// 监听": "// Listen",
    "// 处理": "// Handle",
    "// 触发": "// Trigger",
    "// 清理": "// Clean",
    "// 释放": "// Release",
    "// 渲染": "// Render",
    "// 卸载": "// Unmount",
    "// 隐藏": "// Hide",
    "// 显示": "// Show",
    "// 启用": "// Enable",
    "// 禁用": "// Disable",
    
    # Boolean/common patterns
    "是否": "whether",
    "变量": "variable",
    "参数": "parameter",
    "属性": "property",
    "方法": "method",
    "函数": "function",
    "类型": "type",
    "值": "value",
    "状态": "state",
    "事件": "event",
    "监听器": "listener",
    "回调": "callback",
    "异步": "async",
    "同步": "sync",
    "并发": "concurrent",
    "序列": "sequence",
    "并行": "parallel",
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

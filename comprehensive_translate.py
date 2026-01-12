#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Comprehensive translation mapping
translations = {
    # General comments
    "// handle点击": "// Handle clicking",
    "// 需要手动": "// Need to manually",
    "// 此处": "// Here",
    "// 如果": "// If",
    "// 则": "// Then",
    "// 当": "// When",
    "// 确保": "// Ensure",
    "// 检查": "// Check",
    "// 获取": "// Get",
    "// 返回": "// Return",
    "// 创建": "// Create",
    "// 设置": "// Set",
    "// 更新": "// Update",
    "// 删除": "// Delete",
    "// 添加": "// Add",
    "// 移除": "// Remove",
    "// 触发": "// Trigger",
    "// 监听": "// Listen to",
    "// 处理": "// Handle",
    "// 判断": "// Determine",
    "// 验证": "// Validate",
    "// 计算": "// Calculate",
    "// 初始化": "// Initialize",
    "// 重置": "// Reset",
    "// 清空": "// Clear",
    "// 显示": "// Show",
    "// 隐藏": "// Hide",
    "// 禁用": "// Disable",
    "// 启用": "// Enable",
    "// 开始": "// Start",
    "// 结束": "// End",
    "// 完成": "// Complete",
    "// 失败": "// Failed",
    "// 成功": "// Success",
    "// 错误": "// Error",
    "// 警告": "// Warning",
    "// 信息": "// Info",
    "// 调试": "// Debug",
    
    # File/variable related
    "/** 测试": "/** Test",
    "/** 当日志运行error时": "/** When logs run error",
    "/** 日志": "/** Logs",
    "/** 性能": "/** Performance",
    "/** Canvas": "/** Canvas",
    "/** ReactFlow": "/** ReactFlow",
    "/** 材料": "/** Material",
    "/** 面板": "/** Panel",
    "/** 虚拟": "/** Virtual",
    "/** 分组": "/** Group",
    "/** 搜索": "/** Search",
    "/** 选项卡": "/** Tab",
    "/** 弹出": "/** Popup",
    "/** 交互": "/** Interaction",
    "/** 流程": "/** Flow",
    "/** 节点": "/** Node",
    "/** 边": "/** Edge",
    "/** 数据": "/** Data",
    "/** 配置": "/** Config",
    "/** 状态": "/** State",
    "/** 上下文": "/** Context",
    "/** 提供者": "/** Provider",
    "/** 钩子": "/** Hook",
    "/** 工具": "/** Util",
    "/** 帮助": "/** Helper",
    
    # Complex phrases
    "// 模拟数据": "// Mock data",
    "// 测试数据": "// Test data",
    "// 示例数据": "// Example data",
    "// 默认值": "// Default value",
    "// 环境变量": "// Environment variable",
    "// 本地存储": "// Local storage",
    "// 会话存储": "// Session storage",
    "// 全局状态": "// Global state",
    "// 局部状态": "// Local state",
    "// 组件状态": "// Component state",
    "// 页面状态": "// Page state",
    "// 应用状态": "// Application state",
    "// 未实现": "// Not implemented",
    "// 待实现": "// To be implemented",
    "// 需要优化": "// Needs optimization",
    "// 待优化": "// To be optimized",
    "// 兼容性": "// Compatibility",
    "// 跨浏览器": "// Cross-browser",
    "// 响应式": "// Responsive",
    "// 无障碍": "// Accessibility",
    "// 国际化": "// Internationalization",
    "// 本地化": "// Localization",
    
    # Specific translations already done
    "// 点击的是普通node": "// Clicking a regular node",
    "// 如果点击的是loop体内的node": "// If clicking a node inside loop body",
    "// 存在error，则定位到error的nodeid": "// If error exists, locate to error node id",
    "// 全都success，则定位到最后一个node": "// All success, then locate to last node",
    
    # Common Chinese patterns in code
    "未定义": "undefined",
    "空值": "null",
    "真": "true",
    "假": "false",
    "成功": "success",
    "失败": "failed",
    "错误": "error",
    "警告": "warning",
    
    # String literals
    '"点击"': '"Click"',
    '"选择"': '"Select"',
    '"删除"': '"Delete"',
    '"编辑"': '"Edit"',
    '"确定"': '"Confirm"',
    '"取消"': '"Cancel"',
    '"保存"': '"Save"',
    '"加载中"': '"Loading"',
    '"完成"': '"Complete"',
    '"失败"': '"Failed"',
    '"成功"': '"Success"',
    '"错误"': '"Error"',
    '"警告"': '"Warning"',
}

def clean_translation_key(key):
    """Ensure translation keys don't have problematic escaping"""
    return key

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations (exact match first, then more flexible)
        for chinese, english in translations.items():
            if chinese in content:
                content = content.replace(chinese, english)
                change_count += 1
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✓ {filepath.split(chr(92))[-1]} ({change_count} translations)")
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
    
    print(f"\nTotal files translated: {count}")

if __name__ == '__main__':
    main()

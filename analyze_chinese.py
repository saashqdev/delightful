#!/usr/bin/env python3
"""
Smart Chinese-to-English translation generator for tool files.
Extracts all unique Chinese strings and generates English translations.
"""

import re
import os
from collections import defaultdict

# Translation rules for common Chinese patterns
PHRASE_TRANSLATIONS = {
    # Common command/tool patterns
    '命令': 'command',
    '工具': 'tool',
    '参数': 'parameter',
    '执行': 'execute/execution',
    '失败': 'failed',
    '成功': 'successful',
    '错误': 'error',
    '警告': 'warning',
    '日志': 'log',
    
    # File/path patterns
    '文件': 'file',
    '路径': 'path',
    '目录': 'directory',
    '工作区': 'workspace',
    
    # Operation patterns
    '操作': 'operation',
    '备注': 'remark',
    '结果': 'result',
    '描述': 'description',
    '内容': 'content',
    
    # Data patterns
    '数据': 'data',
    '对象': 'object',
    '列表': 'list',
    '字典': 'dictionary',
    '字段': 'field',
    '类型': 'type',
}

def extract_chinese_strings(file_path):
    """Extract all unique Chinese strings from a file."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extract all Chinese character groups
        chinese_groups = re.findall(r'[\u4e00-\u9fff]+', content)
        return sorted(set(chinese_groups))
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return []

def generate_translation(chinese_text):
    """Generate English translation for Chinese text based on patterns."""
    # Check for exact phrase matches
    for cn_phrase, en_phrase in PHRASE_TRANSLATIONS.items():
        if cn_phrase in chinese_text:
            return f"[{en_phrase}]"
    
    # If no match, return a placeholder for manual review
    return f"[TRANSLATE: {chinese_text}]"

def process_all_files():
    """Process all remaining tool files and generate translation mappings."""
    root = r'c:\Users\kubew\magic\backend\super-magic\app\tools'
    
    remaining_files = [
        'shell_exec.py',
        'summarize.py',
        'thinking.py',
        'use_browser.py',
        'visual_understanding.py',
        'web_search.py',
        'workspace_guard_tool.py',
        'write_to_file.py',
        'yfinance_tool.py',
        'replace_in_file.py',
        'markitdown_plugins/__init__.py',
        'markitdown_plugins/csv_plugin.py',
        'markitdown_plugins/excel_plugin.py',
        'markitdown_plugins/pdf_plugin.py',
        'use_browser_operations/__init__.py',
        'use_browser_operations/base.py',
        'use_browser_operations/content.py',
        'use_browser_operations/interaction.py',
        'use_browser_operations/navigation.py',
        'use_browser_operations/operations_registry.py',
    ]
    
    file_translations = {}
    
    for file_rel in remaining_files:
        file_path = os.path.join(root, file_rel)
        if os.path.exists(file_path):
            chinese_strings = extract_chinese_strings(file_path)
            if chinese_strings:
                file_translations[file_rel] = chinese_strings
    
    return file_translations

if __name__ == '__main__':
    print("Analyzing all remaining files for Chinese content...")
    print("=" * 70)
    
    translations = process_all_files()
    
    total_strings = 0
    for file_name, strings in sorted(translations.items()):
        total_strings += len(strings)
        print(f"\n{file_name} ({len(strings)} unique strings):")
        for s in strings[:10]:  # Show first 10
            print(f"  - '{s}'")
        if len(strings) > 10:
            print(f"  ... and {len(strings) - 10} more")
    
    print("\n" + "=" * 70)
    print(f"Total files needing translation: {len(translations)}")
    print(f"Total unique Chinese strings: {total_strings}")

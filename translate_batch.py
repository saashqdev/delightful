#!/usr/bin/env python3
"""
Improved batch translation script using comprehensive Chinese-to-English mappings.
Handles various formatting and context variations.
"""

import os
import re

# Comprehensive translation mappings
TRANSLATIONS = {
    # Shell exec
    'Shell命令执行参数': 'Shell Command Execution Parameters',
    '要执行的 shell 命令': 'Shell command to execute',
    '指定工作目录': 'Specify working directory', 
    '执行 shell 命令': 'Execute shell command',
    '命令执行失败': 'Command execution failed',
    '命令输出': 'Command output',
    '执行超时': 'Execution timeout',
    '进程已被杀死': 'Process killed',
    
    # Summarize  
    '文本摘要参数': 'Text Summary Parameters',
    '需要摘要的文本内容': 'Text content to summarize',
    '摘要长度': 'Summary length',
    '最大 Token 数': 'Maximum token count',
    '生成摘要': 'Generate summary',
    '摘要生成失败': 'Summary generation failed',
    
    # Thinking
    '深度思考参数': 'Deep Thinking Parameters',
    '思考内容': 'Thinking content',
    '进行深度思考': 'Perform deep thinking',
    '深度思考完成': 'Deep thinking completed',
    
    # Common UI/workflow  
    '工作目录': 'working directory',
    '文件路径': 'file path',
    '执行成功': 'execution successful',
    '执行失败': 'execution failed',
    '参数': 'parameters',
    '描述': 'description',
    '错误': 'error',
    '日志': 'log',
    '结果': 'result',
    '操作': 'action',
    '备注': 'remark',
    
    # Write to file
    '文件写入参数': 'File Write Parameters',
    '要写入的文件路径': 'File path to write to',
    '写入的内容': 'Content to write',
    '创建或覆盖文件': 'Create or overwrite file',
    '文件写入成功': 'File written successfully',
    '文件写入失败': 'File write failed',
    
    # Search/Browse
    '网络搜索参数': 'Web Search Parameters',
    '搜索关键词': 'Search keywords',
    '搜索结果': 'Search results',
    '浏览器操作参数': 'Browser Operation Parameters',
    
    # Visual
    '视觉理解参数': 'Visual Understanding Parameters',
    '图像': 'image',
    '描述': 'description',
    
    # YFinance
    '财务数据参数': 'Financial Data Parameters',
    '股票代码': 'stock symbol',
    '时间范围': 'time period',
    
    # Workspace guard
    '工作空间保护': 'Workspace Protection',
    '禁止操作': 'Forbidden operation',
    '路径不在工作区内': 'Path is outside workspace',
    
    # Replace in file
    '文件内容替换参数': 'File Content Replacement Parameters',
    '要修改的文件': 'File to modify',
    '差异内容': 'Diff content',
    '替换失败': 'Replacement failed',
    '替换成功': 'Replacement successful',
}

def translate_chinese_text(file_path):
    """Translate Chinese text in a file using comprehensive mappings."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Apply each translation
        for chinese, english in TRANSLATIONS.items():
            content = content.replace(chinese, english)
        
        # Only write if content changed
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            # Check for remaining Chinese
            remaining_chinese = bool(re.search(r'[\u4e00-\u9fff]', content))
            return remaining_chinese
        else:
            # Check if file still has Chinese (means our mapping didn't catch it)
            return bool(re.search(r'[\u4e00-\u9fff]', content))
            
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return True

# Process all files
root = r'c:\Users\kubew\magic\backend\super-magic\app\tools'
files = [
    'shell_exec.py', 'summarize.py', 'thinking.py', 'use_browser.py',
    'visual_understanding.py', 'web_search.py', 'workspace_guard_tool.py',
    'write_to_file.py', 'yfinance_tool.py', 'replace_in_file.py',
    'markitdown_plugins/__init__.py', 'markitdown_plugins/csv_plugin.py',
    'markitdown_plugins/excel_plugin.py', 'markitdown_plugins/pdf_plugin.py',
    'use_browser_operations/__init__.py', 'use_browser_operations/base.py',
    'use_browser_operations/content.py', 'use_browser_operations/interaction.py',
    'use_browser_operations/navigation.py', 'use_browser_operations/operations_registry.py',
]

print("Applying comprehensive translations...")
print("=" * 60)

translated = 0
remaining = 0

for file in files:
    file_path = os.path.join(root, file)
    if os.path.exists(file_path):
        if translate_chinese_text(file_path):
            print(f"⚠ {file}: Still has Chinese (needs more mappings)")
            remaining += 1
        else:
            print(f"✓ {file}: Translated")
            translated += 1
    else:
        print(f"✗ {file}: Not found")

print("=" * 60)
print(f"Translated: {translated}, Still need translation: {remaining}")

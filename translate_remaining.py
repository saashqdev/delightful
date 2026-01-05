#!/usr/bin/env python3
"""
Batch translation script to convert remaining Chinese text to English in tool files.
This script automates the translation of common Chinese phrases found in the tool files.
"""

import os
import re
from pathlib import Path

# Common translation mappings for Chinese text found in tools
TRANSLATION_MAP = {
    # Comments and docstrings
    '"""Shell命令执行参数"""': '"""Shell command execution parameters"""',
    '要执行的 shell 命令': 'Shell command to execute',
    '指定工作目录': 'Specify working directory',
    '命令输出': 'Command output',
    '命令执行失败': 'Command execution failed',
    '执行 shell 命令': 'Execute shell command',
    
    # Summarize
    '文本摘要参数': 'Text summary parameters',
    '需要摘要的文本内容': 'Text content to summarize',
    '摘要长度': 'Summary length',
    '最大token数': 'Maximum token count',
    '生成摘要': 'Generate summary',
    
    # Thinking
    '深度思考参数': 'Deep thinking parameters',
    '思考内容': 'Thinking content',
    '进行深度思考': 'Perform deep thinking',
    
    # Common patterns
    '工作目录': 'working directory',
    '文件路径': 'file path',
    '执行失败': 'execution failed',
    '执行成功': 'execution successful',
    '参数': 'parameters',
    '描述': 'description',
    '错误': 'error',
    '日志': 'log',
    '结果': 'result',
}

def translate_file(file_path):
    """Translate Chinese text in a Python file."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Check if file has Chinese text
        if not re.search(r'[\u4e00-\u9fff]', content):
            print(f"✓ {os.path.relpath(file_path)}: No Chinese text found")
            return False
        
        # Apply translations
        for cn_text, en_text in TRANSLATION_MAP.items():
            if cn_text in content:
                content = content.replace(cn_text, en_text)
        
        # Write back
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        # Check if Chinese remains
        if re.search(r'[\u4e00-\u9fff]', content):
            print(f"⚠ {os.path.relpath(file_path)}: Still has Chinese text")
            return True  # Still needs manual translation
        else:
            print(f"✓ {os.path.relpath(file_path)}: Translated successfully")
            return False
            
    except Exception as e:
        print(f"✗ {os.path.relpath(file_path)}: Error - {e}")
        return True

# Files to translate
files_to_translate = [
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\shell_exec.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\summarize.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\thinking.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\use_browser.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\visual_understanding.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\web_search.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\workspace_guard_tool.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\write_to_file.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\yfinance_tool.py',
    r'c:\Users\kubew\magic\backend\super-magic\app\tools\replace_in_file.py',
]

if __name__ == '__main__':
    print("Starting batch translation of tool files...")
    print("=" * 60)
    
    needs_manual = []
    for file_path in files_to_translate:
        if os.path.exists(file_path):
            if translate_file(file_path):
                needs_manual.append(file_path)
        else:
            print(f"✗ {file_path}: File not found")
    
    print("=" * 60)
    if needs_manual:
        print(f"\nFiles requiring manual translation ({len(needs_manual)}):")
        for f in needs_manual:
            print(f"  - {os.path.relpath(f)}")
    else:
        print("\n✓ All files translated successfully!")

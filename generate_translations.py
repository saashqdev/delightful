#!/usr/bin/env python3
"""
Comprehensive Chinese-to-English translation script for remaining tool files.
Generates multi_replace_string_in_file operations.
"""

import re
import os
import json
from typing import List, Dict, Tuple

def extract_chinese_lines(file_path, context_lines=3):
    """Extract lines containing Chinese text with context."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        replacements = []
        
        for i, line in enumerate(lines):
            if re.search(r'[\u4e00-\u9fff]', line):
                start = max(0, i - context_lines)
                end = min(len(lines), i + context_lines + 1)
                
                old_string = ''.join(lines[start:end])
                
                # Create English version by translating the line
                new_lines = lines[start:end].copy()
                new_lines[i - start] = translate_line(new_lines[i - start])
                new_string = ''.join(new_lines)
                
                replacements.append({
                    'file': file_path,
                    'old': old_string,
                    'new': new_string,
                    'line_num': i + 1
                })
        
        return replacements
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return []

def translate_line(line):
    """Translate Chinese text in a line to English."""
    # Common translations
    translations = {
        '命令执行工具': 'Shell command execution tool',
        '命令': 'command',
        '执行': 'execute',
        '参数': 'parameter',
        '失败': 'failed',
        '工作目录': 'working directory',
        '超时': 'timeout',
        '秒': 'seconds',
        '使用': 'use/usage',
        '注意': 'note',
        '场景': 'use case',
        '文件和目录': 'files and directories',
        '系统命令': 'system command',
        '管理进程': 'process management',
        '运行脚本': 'run scripts',
        '安全': 'safe/security',
        '白名单': 'whitelist',
        '有害命令': 'harmful commands',
        '危险': 'dangerous',
        '删除': 'deletion',
        '额外确认': 'additional confirmation',
        '支持': 'support',
        '复合命令': 'composite commands',
        '尽量': 'try to',
        '只执行': 'only execute',
        '避免执行': 'avoid executing',
        '注释': 'comment',
        '文件': 'file',
        '对象': 'object',
        '内容': 'content',
        '结果': 'result',
        '错误': 'error',
        '日志': 'log',
        '字段': 'field',
        '类型': 'type',
        '说明': 'description',
        '记录': 'record',
        '检查': 'check',
        '验证': 'validate',
        '处理': 'process/handle',
        '获取': 'get',
        '设置': 'set',
        '返回': 'return',
        '异常': 'exception',
        '警告': 'warning',
        '信息': 'information',
        '代码': 'code',
        '运行': 'run',
        '脚本': 'script',
        '环境': 'environment',
        '变量': 'variable',
        '路径': 'path',
        '目录': 'directory',
        '权限': 'permission',
        '进程': 'process',
        '输出': 'output',
        '输入': 'input',
        '配置': 'configuration',
        '操作': 'operation',
        '完成': 'completed',
        '状态': 'status',
    }
    
    new_line = line
    for cn, en in translations.items():
        new_line = new_line.replace(cn, en)
    
    return new_line

def main():
    root = r'c:\Users\kubew\magic\backend\super-magic\app\tools'
    
    files = [
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
    ]
    
    print("Generating translation data for remaining files...")
    
    for file in files:
        file_path = os.path.join(root, file)
        if os.path.exists(file_path):
            replacements = extract_chinese_lines(file_path)
            if replacements:
                print(f"\n{file}: {len(replacements)} Chinese line(s) found")
                
                # Show first few replacements
                for r in replacements[:2]:
                    print(f"  Line {r['line_num']}: {r['old'][:50]}...")

if __name__ == '__main__':
    main()

#!/usr/bin/env python3
"""
Comprehensive automated translation for all remaining Chinese text.
Processes all files and replaces Chinese with English using comprehensive mappings.
"""

import os
import re
from pathlib import Path

# Comprehensive translation dictionary
TRANSLATIONS = {
    # Common phrases and sentences
    '将复合命令拆分为单个命令列表': 'Split composite command into list of individual commands',
    '可能包含分隔符的命令字符串': 'Command string that may contain separators',
    '拆分后的命令列表': 'List of split commands',
    '先保存分隔符用于临时替换': 'First save separators for temporary replacement',
    '临时替换引号内的内容以防止错误拆分': 'Temporarily replace content within quotes to prevent incorrect splitting',
    '使用正则表达式拆分命令': 'Split commands using regex',
    '这个正则表达式匹配命令分隔符': 'This regex matches command separators',
    '恢复原始引号内容': 'Restore original quote content',
    '恢复所有临时替换': 'Restore all temporary replacements',
    '检查命令是否在白名单中': 'Check if command is in whitelist',
    '检查是否是复合命令': 'Check if it is a composite command',
    '验证所有命令是否安全': 'Validate all commands are safe',
    '命令不在安全白名单中': 'Command not in safe whitelist',
    '包含不允许的分隔符': 'Contains disallowed separators',
    '执行命令': 'Execute command',
    '创建子进程': 'Create subprocess',
    '等待进程完成': 'Wait for process to complete',
    '超时': 'timeout',
    '进程被杀死': 'Process killed',
    '命令执行失败': 'Command execution failed',
    '退出码': 'exit code',
    '工具上下文': 'tool context',
    '执行参数': 'execution parameters',
    '工具执行结果': 'tool execution result',
    '包含摘要结果的工具结果': 'Tool result containing summary result',
    '获取参数': 'Get parameters',
    '记录摘要请求': 'Log summary request',
    '读取文件内容': 'Read file content',
    '无法读取文件': 'Unable to read file',
    '提取文件名用于显示': 'Extract file name for display',
    '调用内部方法处理摘要': 'Call internal method to process summary',
    '摘要生成失败': 'Summary generation failed',
    '返回包含摘要的结果': 'Return result containing summary',
    '获取工具调用前的友好内容': 'Get friendly content before tool call',
    '获取工具调用后的友好动作和备注': 'Get friendly action and remark after tool call',
    '工具名称': 'Tool name',
    '执行耗时': 'Execution time',
    '包含动作和备注的字典': 'Dictionary containing action and remark',
    '截断过长的问题描述': 'Truncate overly long problem description',
    '深度思考分析': 'Deep thinking analysis',
    '开始深入思考问题': 'Starting deep thinking on problem',
    '关于': 'About',
    '所以': 'Therefore',
    '流程': 'Process',
    '步骤': 'Step',
    '第': 'Number',
    
    # Single word/short phrase translations
    '命令白名单配置': 'Command whitelist configuration',
    '文件和目录操作': 'File and directory operations',
    '进程管理': 'Process management',
    '系统信息': 'System information',
    '网络工具': 'Network tools',
    '包管理': 'Package management',
    '压缩和解压': 'Compression and extraction',
    '文本处理': 'Text processing',
    '环境变量': 'Environment variables',
    '允许的命令分隔符': 'Allowed command separators',
    '相关': 'related',
    '命令': 'command',
    '执行': 'execute/execution',
    '参数': 'parameter',
    '结果': 'result',
    '错误': 'error',
    '警告': 'warning',
    '信息': 'information',
    '描述': 'description',
    '注释': 'comment',
    '文件': 'file',
    '路径': 'path',
    '目录': 'directory',
    '内容': 'content',
    '操作': 'operation',
    '备注': 'remark',
    '失败': 'failed',
    '成功': 'successful',
    '完成': 'completed',
    '处理': 'process',
    '获取': 'get',
    '设置': 'set',
    '检查': 'check',
    '验证': 'validate',
    '对象': 'object',
    '工具': 'tool',
    '上下文': 'context',
    '包含': 'containing',
    '摘要': 'summary',
    '最大': 'maximum',
    '长度': 'length',
    '字符数': 'number of characters',
    '问题': 'problem',
    '思考': 'thinking',
    '目标': 'target',
    '结论': 'conclusion',
    '解决方案': 'solution',
    '行动计划': 'action plan',
    '决策建议': 'decision recommendation',
    '需要': 'need',
    '应该': 'should',
    '明确': 'clearly',
    '表述': 'state',
    '核心': 'core',
    '疑问': 'question',
    '分析': 'analysis',
    '背景': 'background',
    '观察': 'observation',
    '推理过程': 'reasoning process',
    '列表': 'list',
    '标题': 'title',
    '详细': 'detailed',
    '并': 'and',
    '或': 'or',
    '如': 'such as',
    '模型': 'model',
    '生成': 'generate',
    '读取': 'read',
    '记录': 'record/log',
    '请求': 'request',
    '返回': 'return',
    '调用': 'call',
    '方法': 'method',
    '动作': 'action',
    '名称': 'name',
    '耗时': 'time',
    '字典': 'dictionary',
}

def translate_file(file_path):
    """Translate Chinese text in a file."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if not re.search(r'[\u4e00-\u9fff]', content):
            return True, "No Chinese"
        
        original_content = content
        
        # Apply translations in order of decreasing length (longer phrases first)
        for chinese, english in sorted(TRANSLATIONS.items(), key=lambda x: -len(x[0])):
            if chinese in content:
                content = content.replace(chinese, english)
        
        # Write back
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        # Check if Chinese remains
        if re.search(r'[\u4e00-\u9fff]', content):
            return False, "Still has Chinese"
        else:
            return True, "Translated"
            
    except Exception as e:
        return False, f"Error: {e}"

def main():
    root = Path(r'c:\Users\kubew\magic\backend\super-magic\app\tools')
    
    # Get all Python files
    files_to_check = []
    for file in root.rglob('*.py'):
        files_to_check.append(file)
    
    print(f"Processing {len(files_to_check)} Python files...")
    print("=" * 70)
    
    translated = 0
    still_has_chinese = 0
    errors = 0
    
    for file_path in sorted(files_to_check):
        rel_path = file_path.relative_to(root.parent)
        success, message = translate_file(file_path)
        
        if success:
            if message == "Translated":
                print(f"✓ {rel_path}: Translated")
                translated += 1
        else:
            if "Still has Chinese" in message:
                print(f"⚠ {rel_path}: {message}")
                still_has_chinese += 1
            else:
                print(f"✗ {rel_path}: {message}")
                errors += 1
    
    print("=" * 70)
    print(f"Translated: {translated}")
    print(f"Still need work: {still_has_chinese}")
    if errors > 0:
        print(f"Errors: {errors}")

if __name__ == '__main__':
    main()

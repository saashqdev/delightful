#!/usr/bin/env python3
"""
Automated Chinese-to-English translation for remaining Python files.
Uses simple replace operations to translate common patterns.
"""

import os
import re

def translate_file(file_path):
    """Translate Chinese text in a file."""
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Common exact phrase replacements
    replacements = {
        # Generic patterns
        '"""Python Execute': '"""Python Execution',
        '"""Python执行': '"""Python execution',
        '执行': 'execute/execution',
        '参数': 'parameter',
        '结果': 'result',
        '返回': 'return',
        '错误': 'error',
        '日志': 'log',
        '警告': 'warning',
        '信息': 'information',
        '描述': 'description',
        '注释': 'comment',
        '字段': 'field',
        '类型': 'type',
        '文件': 'file',
        '路径': 'path',
        '目录': 'directory',
        '内容': 'content',
        '操作': 'operation',
        '备注': 'remark',
        '命令': 'command',
        '脚本': 'script',
        '运行': 'run',
        '完成': 'completed',
        '处理': 'process',
        '获取': 'get',
        '设置': 'set',
        '检查': 'check',
        '验证': 'validate',
        '对象': 'object',
        '环境': 'environment',
        '变量': 'variable',
        '权限': 'permission',
        '进程': 'process',
        '输出': 'output',
        '输入': 'input',
        '配置': 'configuration',
        '状态': 'status',
        '成功': 'successful',
        '失败': 'failed',
        '异常': 'exception',
        '工作': 'work',
        '空间': 'space',
        '白名单': 'whitelist',
        '有害': 'harmful',
        '危险': 'dangerous',
        '支持': 'support',
        '复合': 'composite',
        '超时': 'timeout',
        '秒': 'seconds',
        '列表': 'list',
        '字典': 'dictionary',
        '代码': 'code',
        '生成': 'generate',
        '安全': 'security',
        '摘要': 'summary',
        '工具': 'tool',
        '思考': 'thinking',
        '浏览': 'browser',
        '搜索': 'search',
        '财务': 'financial',
        '数据': 'data',
        '网络': 'network',
        '访问': 'access',
        '跳转': 'navigate',
        '点击': 'click',
        '滚动': 'scroll',
        '提交': 'submit',
        '表单': 'form',
        '输入框': 'input box',
        '选择': 'select',
        '按钮': 'button',
        '文本': 'text',
        '图像': 'image',
        '视觉': 'visual',
        '理解': 'understanding',
        '生成摘要': 'generate summary',
        '长篇': 'lengthy',
        '文章': 'article',
        '论文': 'paper',
        '会议': 'conference',
        '记录': 'record',
        '新闻': 'news',
        '核心': 'core',
        '信息': 'information',
        '主要': 'main',
        '观点': 'viewpoint',
        '要求': 'requirement',
        '基于': 'based on',
        '保留': 'preserve',
        '关键': 'key',
        '场景': 'scenario',
        '包括': 'include',
        '但不限于': 'but not limited to',
        '示例': 'example',
        '调用': 'call',
        '上下文': 'context',
        '对象': 'object',
        '包含': 'contain',
        '无法': 'unable',
        '读取': 'read',
        '记录': 'record',
        '请求': 'request',
        '最大': 'maximum',
        '模型': 'model',
        '深度': 'deep',
        '思考': 'thinking',
        '内容': 'content',
        '进行': 'perform',
        '页面': 'page',
        '浏览器': 'browser',
        '操作': 'operation',
        '导航': 'navigation',
        '交互': 'interaction',
        '指令': 'instruction',
        '执行': 'execute',
        '步骤': 'step',
        '流程': 'flow',
        '管理': 'manage',
        '保存': 'save',
        '加载': 'load',
        '删除': 'delete',
        '编辑': 'edit',
        '查看': 'view',
        '创建': 'create',
        '修改': 'modify',
        '替换': 'replace',
        '搜索': 'search',
        '查找': 'find',
        '过滤': 'filter',
        '排序': 'sort',
        '分组': 'group',
        '统计': 'statistics',
        '计数': 'count',
        '求和': 'sum',
        '平均': 'average',
        '最大值': 'maximum',
        '最小值': 'minimum',
        '范围': 'range',
        '时间': 'time',
        '日期': 'date',
        '时区': 'timezone',
        '格式': 'format',
        '编码': 'encoding',
        '解码': 'decoding',
        '压缩': 'compress',
        '解压': 'decompress',
        '加密': 'encrypt',
        '解密': 'decrypt',
        '验证': 'validate',
        '校验': 'check',
        '签名': 'signature',
        '令牌': 'token',
        '密钥': 'key',
        '证书': 'certificate',
        '配置': 'configuration',
        '设置': 'setting',
        '选项': 'option',
        '标志': 'flag',
        '默认': 'default',
        '自定义': 'customize',
    }
    
    new_content = content
    for cn, en in replacements.items():
        if cn in new_content:
            new_content = new_content.replace(cn, en)
    
    # Check if Chinese remains
    if re.search(r'[\u4e00-\u9fff]', new_content):
        return False  # Still has Chinese
    
    # Write the translated content back
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    return True  # Successfully translated

# Main execution
if __name__ == '__main__':
    root = r'c:\Users\kubew\magic\backend\super-magic\app\tools'
    
    files_to_translate = [
        'thinking.py',
        'use_browser.py',
        'visual_understanding.py',
        'web_search.py',
        'workspace_guard_tool.py',
        'write_to_file.py',
        'yfinance_tool.py',
        'replace_in_file.py',
    ]
    
    print("Starting automated translation...")
    print("=" * 60)
    
    translated = 0
    failed = 0
    
    for file in files_to_translate:
        file_path = os.path.join(root, file)
        if os.path.exists(file_path):
            try:
                if translate_file(file_path):
                    print(f"✓ {file}: Translated successfully")
                    translated += 1
                else:
                    print(f"⚠ {file}: Still has Chinese (needs manual translation)")
                    failed += 1
            except Exception as e:
                print(f"✗ {file}: Error - {e}")
                failed += 1
        else:
            print(f"✗ {file}: Not found")
    
    print("=" * 60)
    print(f"Translated: {translated}/{len(files_to_translate)}")
    if failed > 0:
        print(f"Needs manual work: {failed} files")

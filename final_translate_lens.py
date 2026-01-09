#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Final comprehensive translation script for lens.js"""

import re
import sys

# Comprehensive translation dictionary with all remaining patterns
TRANSLATIONS = {
    # General terms
    '或': ' or ',
    '和': ' and ',
    '的': ' ',
    '与': ' and ',
    '及': ' and ',
    '是': ' is ',
    '为': ' as ',
    '在': ' in ',
    '中': ' in ',
    '上': ' on ',
    '下': ' under ',
    '有': ' has ',
    '用': ' use ',
    '于': ' for ',
    '如': ' like ',
    '以': ' with ',
    '到': ' to ',
    '从': ' from ',
    '而': ' and ',
    '且': ' and ',
    '则': ' then ',
    '即': ' that is ',
    '但': ' but ',
    '也': ' also ',
    '只': ' only ',
    '可': ' can ',
    '将': ' will ',
    '需': ' need ',
    '对': ' to ',
    '后': ' after ',
    '时': ' when ',
    
    # Technical terms
    'check': 'Check',
    'element': 'element',
    'node': 'node',
    'content': 'content',
    'valid': 'valid',
    'list': 'list',
    'item': 'item',
    'link': 'link',
    'image': 'image',
    'heading': 'heading',
    'paragraph': 'paragraph',
    'text': 'text',
    'block': 'block',
    'inline': 'inline',
    'level': 'level',
    'index': 'index',
    'markdown': 'Markdown',
    'result': 'result',
    'context': 'context',
    'child': 'child',
    'parent': 'parent',
    'tag': 'tag',
    'attribute': 'attribute',
    'style': 'style',
    'code': 'code',
    'table': 'table',
    'viewport': 'viewport',
    'filter': 'filter',
    'process': 'process',
    
    # Action verbs
    '处理': 'Process',
    '获取': 'Get',
    '生成': 'Generate',
    '创建': 'Create',
    '确定': 'Determine',
    '检查': 'Check',
    '判断': 'Determine',
    '收集': 'Collect',
    '遍历': 'Traverse',
    '跳过': 'Skip',
    '忽略': 'Ignore',
    '返回': 'Return',
    '添加': 'Add',
    '删除': 'Remove',
    '合并': 'Merge',
    '清理': 'Clean',
    '剥离': 'Strip',
    '格式化': 'Format',
    '计算': 'Calculate',
    '标记': 'Mark',
    '递增': 'Increment',
    '提取': 'Extract',
    '包含': 'contains',
    '满足': 'Satisfy',
    '增加': 'Increase',
    '分发': 'Dispatch',
    '记录': 'Record',
    '拼接': 'Concatenate',
    '聚合': 'Aggregate',
    
    # Common phrases
    '是否': 'whether',
    '如果': 'If',
    '则': 'then',
    '或者': 'or',
    '并且': 'and',
    '虽然': 'although',
    '但是': 'but',
    '因为': 'because',
    '所以': 'so',
    '例如': 'For example',
    '比如': 'such as',
    '等': 'etc',
    '等等': 'etc',
    
    # Document structure
    '段落': 'paragraph',
    '标题': 'heading',
    '列表': 'list',
    '链接': 'link',
    '图片': 'image',
    '图像': 'image',
    '表格': 'table',
    '单元格': 'cell',
    '内容': 'content',
    '文本': 'text',
    '代码': 'code',
    '引用': 'quote',
    '分隔符': 'separator',
    '换行符': 'line break',
    '空白': 'whitespace',
    '缩进': 'indent',
    '标记': 'marker',
    
    # Properties/attributes
    '级别': 'level',
    '索引': 'index',
    '宽度': 'width',
    '高度': 'height',
    '面积': 'area',
    '比例': 'ratio',
    '阈值': 'threshold',
    '尺寸': 'dimensions',
    '维度': 'dimensions',
    '边界': 'boundaries',
    '坐标': 'coordinates',
    '位置': 'position',
    '大小': 'size',
    '长度': 'length',
    '字重': 'font weight',
    '字体': 'font',
    
    # States/conditions
    '有效': 'valid',
    '无效': 'invalid',
    '空': 'empty',
    '非空': 'non-empty',
    '可见': 'visible',
    '隐藏': 'hidden',
    '直接': 'direct',
    '间接': 'indirect',
    '块级': 'block-level',
    '行内': 'inline',
    '嵌套': 'nested',
    '顶层': 'top-level',
    '底层': 'bottom-level',
    
    # Actions
    '分割': 'split',
    '合并': 'merge',
    '拼接': 'join',
    '连接': 'join',
    '组合': 'combine',
    '聚合': 'aggregate',
    '过滤': 'filter',
    '排除': 'exclude',
    '包括': 'include',
    
    # Common comment patterns
    '注意': 'Note',
    '警告': 'Warning',
    '提示': 'Tip',
    '说明': 'Description',
    '参数': 'Parameter',
    '返回值': 'Return value',
    '示例': 'Example',
    '用法': 'Usage',
    '目的': 'Purpose',
    '功能': 'Function',
    '作用': 'Function',
    '逻辑': 'Logic',
    '步骤': 'Steps',
    '过程': 'Process',
    '方法': 'Method',
    '函数': 'Function',
    '优化': 'Optimization',
    '增强': 'Enhancement',
    '改进': 'Improvement',
    '修复': 'Fix',
    '特殊情况': 'Special case',
    '默认值': 'Default value',
    '可选': 'Optional',
    '必须': 'Must',
    '应该': 'Should',
    '可以': 'Can',
    '需要': 'Need',
    '允许': 'Allow',
    '限制': 'Restrict',
    '确保': 'Ensure',
    '保持': 'Keep',
    '避免': 'Avoid',
    '防止': 'Prevent',
    
    # Specific patterns found in comments
    '主要职责': 'Main responsibilities',
    '主入口函数': 'Main entry function',
    '主逻辑': 'Main logic',
    '硬性过滤': 'Hard filters',
    '软性过滤': 'Soft filters',
    '根据': 'Based on',
    '通过': 'Through',
    '使用': 'Use',
    '调用': 'Call',
    '执行': 'Execute',
    '实现': 'Implement',
    '应用': 'Apply',
    '传递': 'Pass',
    '接收': 'Receive',
    '解析': 'Parse',
    '转换': 'Convert',
    '映射': 'Map',
    
    # More specific terms
    '子元素': 'child element',
    '子节点': 'child node',
    '父元素': 'parent element',
    '父节点': 'parent node',
    '兄弟元素': 'sibling element',
    '祖先元素': 'ancestor element',
    '后代元素': 'descendant element',
    '第一行': 'first line',
    '最后一行': 'last line',
    '后续行': 'subsequent lines',
    '前导': 'leading',
    '尾随': 'trailing',
    '首尾': 'leading/trailing',
    '开头': 'start',
    '结尾': 'end',
    '中间': 'middle',
    
    # Context-specific
    '有序列表': 'ordered list',
    '无序列表': 'unordered list',
    '列表项': 'list item',
    '结构化链接': 'structured link',
    '普通链接': 'normal link',
    '图片链接': 'image link',
    '纯文本': 'plain text',
    '计算样式': 'computed style',
    '内联样式': 'inline style',
    '块引用': 'blockquote',
    '代码块': 'code block',
    '行内代码': 'inline code',
    '水平线': 'horizontal line',
    '删除线': 'strikethrough',
    '粗体': 'bold',
    '斜体': 'italic',
    '下划线': 'underline',
    
    # Numbers and counts
    '第一': 'first',
    '第二': 'second',
    '第三': 'third',
    '最小': 'minimum',
    '最大': 'maximum',
    '最少': 'at least',
    '最多': 'at most',
    '唯一': 'unique',
    '单个': 'single',
    '多个': 'multiple',
    '全部': 'all',
    '任意': 'any',
    '每个': 'each',
    '所有': 'all',
    
    # Boolean/logic
    '真': 'true',
    '假': 'false',
    '是': 'yes',
    '否': 'no',
    '成功': 'success',
    '失败': 'failure',
    '正确': 'correct',
    '错误': 'error',
    '通过': 'pass',
    '停止': 'stop',
}

def translate_file(input_file):
    """Translate Chinese strings in the file"""
    try:
        # Read file with UTF-8 encoding
        with open(input_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Apply translations (sorted by length descending to handle longer phrases first)
        for chinese, english in sorted(TRANSLATIONS.items(), key=lambda x: -len(x[0])):
            content = content.replace(chinese, english)
        
        # Write back
        with open(input_file, 'w', encoding='utf-8', newline='') as f:
            f.write(content)
        
        # Count remaining Chinese characters
        chinese_pattern = re.compile(r'[\u4e00-\u9fff]+')
        remaining = len(chinese_pattern.findall(content))
        
        print(f"Translation complete.")
        print(f"Remaining Chinese character sequences: {remaining}")
        
        return True
        
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        return False

if __name__ == '__main__':
    input_file = r'c:\Users\kubew\magic\backend\be-delightful\delightful_use\js\lens.js'
    success = translate_file(input_file)
    sys.exit(0 if success else 1)

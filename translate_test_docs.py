#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Translate remaining test documentation files from Chinese to English
"""

import os
import re

# Translation mappings
translations = {
    # Common patterns
    r'when前': 'Current',
    r'optimization目标': 'Optimization goal',
    r'decrease 50% 预handletime': 'Reduce 50% preprocessing time',
    r'分块': 'chunked',
    r'块': 'block',
    r'size': 'size',
    r'最large': 'maximum',
    r'rendertime': 'render time',
    r'动态': 'dynamic',
    r'目标': 'target',
    r'中期': 'mid-term',
    r'预期收益': 'expected benefits',
    r'virtual化': 'virtualized',
    r'适用scenario': 'applicable scenario',
    r'初始': 'initial',
    r'渐进式': 'progressive',
    r'智能优先级': 'intelligent priority',
    r'根据': 'based on',
    r'视窗': 'viewport',
    r'adjustment': 'adjust',
    r'implement': 'implement',
    r'批次': 'batch',
    r'advanced': 'advanced',
    r'long期规划': 'long-term planning',
    r'预handle': 'preprocess',
    r'可parallel化': 'can be parallelized',
    r'small': 'small',
    r'直接handle': 'process directly',
    r'testshow': 'test shows',
    r'cache策略': 'cache strategy',
    r'test结果': 'test results',
    r'超large': 'extra large',
    r'monitor指标': 'monitoring metrics',
    r'关键performance指标': 'Key Performance Indicators',
    r'实time': 'real-time',
    r'立即实施': 'immediate implementation',
    r'short期实施': 'short-term implementation',
    r'持续追踪': 'continuous tracking',
    r'改善user体验': 'improve user experience',
    r'感知延迟': 'perceived latency',
    r'while': 'although',
    r'表现良好': 'performs well',
    r'为更largedocumentation做ready': 'prepare for larger documents',
    r'集成': 'integration',
    r'handle能力': 'handling capability',
    r'持久化': 'persistent',
    r'decreaseheavy复largedocumentationloadtime': 'reduce repeated large document load time',
    r'analyzetool': 'analysis tool',
    r'自动识别': 'automatically identify',
    r'performance瓶颈': 'performance bottlenecks',
    r'预期': 'expected',
    r'提升': 'improvement',
    r'季度': 'quarterly',
    r'结论': 'conclusion',
    r'component': 'component',
    r'athandle': 'when handling',
    r'时表现出色': 'performs excellently',
    r'远超': 'far exceeds',
    r'main': 'main',
    r'方向应聚焦in': 'direction should focus on',
    r'keepwhen前优秀performance': 'maintain current excellent performance',
    r'through': 'through',
    r'extension更large': 'extend to larger',
    r'support': 'support',
    r'和': 'and',
    r'改善': 'improve',
    r'experience': 'experience',
    r'已有': 'already has',
    r'良好basic': 'good foundation',
    r'optimizationworkshouldyes': 'optimization work should be',
    r'渐进式的improvement而notheavy构': 'incremental improvements rather than reconstruction',
    
    # Performance report translations
    r'analyze概要': 'analysis overview',
    r'based on对': 'based on',
    r'深入analyze': 'in-depth analysis',
    r'本report识别了': 'this report identifies',
    r'影响': 'affecting',
    r'关键因素': 'key factors',
    r'并提供了': 'and provides',
    r'针对性的': 'targeted',
    r'suggestion': 'recommendations',
    r'architecture': 'architecture',
    r'core': 'core',
    r'结构': 'structure',
    r'fontsize': 'font size',
    r'流式': 'streaming',
    r'副作用': 'side effect',
    r'manage': 'management',
    r'光标': 'cursor',
    r'样式handle': 'style handling',
    r'class名handle': 'class name handling',
    r'service': 'service',
    r'瓶颈': 'bottlenecks',
    r'问题': 'issues',
    r'complex的': 'complex',
    r'expression': 'expression',
    r'operation': 'operations',
    r'特别isfor': 'especially for',
    r'large文本块': 'large text blocks',
    r'many次': 'multiple',
    r'string': 'string',
    r'replace和拆分': 'replace and split',
    r'formula': 'formula',
    r'handleneed': 'handling requires',
    r'large amount': 'large number of',
    r'match': 'matches',
    r'涉及': 'involves',
    r'嵌套logic': 'nested logic',
    r'耗time': 'time-consuming',
    r'引用块检测': 'quote block detection',
    r'使用': 'use',
    r'避免': 'avoid',
    r'heavy复handle': 'repeated processing',
    r'more': 'more',
    r'high效的': 'efficient',
    r'简化的': 'simplified',
    r'数学': 'mathematical',
    r'按段落': 'by paragraph',
    r'Hook': 'Hook',
    r'中等影响': 'moderate impact',
    r'中': 'in',
    r'of': 'of',
    r'dependency': 'dependency',
    r'might导致': 'might cause',
    r'过度': 'excessive',
    r'calculation': 'calculations',
    r'覆盖': 'override',
    r'configurationcreatecomplex': 'configuration creates complexity',
    r'every time': 'every time',
    r'props': 'props',
    r'变化都会': 'changes will',
    r'buildconfiguration': 'build configuration',
    r'稳定化': 'stabilize',
    r'将不变的': 'extract unchanging',
    r'提取tocomponentoutside': 'to outside component',
    r'其他不变的': 'other unchanging',
    r'null': 'empty',
    r'array': 'array',
    r'wrap': 'wrap',
    r'frequen': 'frequent',
    r'statusupdate': 'status updates',
    r'导致': 'cause',
    r'动画效果': 'animation effects',
    r'拼接': 'concatenation',
    r'较': 'relatively',
    r'update频率': 'update frequency',
    r'blocking主线程': 'blocking main thread',
    r'批量': 'batch',
    r'长': 'long',
    r'分块': 'chunking',
    r'只': 'only',
    r'visible': 'visible',
    r'part': 'part',
    r'创建': 'creation',
    r'语法': 'syntax',
    r'high亮': 'highlighting',
    r'较slow': 'relatively slow',
    r'精确': 'precise',
    r'懒load': 'lazy loading',
    r'with': 'with',
    r'大型': 'large',
    r'对比': 'comparison',
    r'估算value': 'estimated values',
    r'简单文本': 'simple text',
    r'比例': 'ratio',
    r'具体': 'specific',
    r'实施方案': 'implementation plan',
    r'添add': 'add',
    r'级': 'level',
    r'记忆化': 'memoization',
    r'稳定的': 'stable',
    r'option': 'options',
    r'handlecomplexdocumentation': 'handle complex documents',
    r'增量': 'incremental',
    r'清除before的定time器': 'clear previous timers',
    r'约': 'approximately',
    r'代码': 'code',
    r'sendtoanalyze平台': 'send to analytics platform',
    r'超过50ms的': 'exceeding 50ms',
    r'detected': 'detected',
    r'took': 'took',
    r'get': 'get',
    r'atcomponent中': 'in component',
    r'logic': 'logic',
    r'summary': 'summary',
    r'实施上述': 'implementing above',
    r'方案': 'approach',
    r'canimplement': 'can achieve',
    r'使用': 'usage',
    r'更流畅': 'smoother',
    r'enhancement': 'enhancement',
    r'按照': 'according to',
    r'三个阶段': 'three phases',
    r'逐步实施': 'gradually implement',
    r'并through': 'and through',
    r'validate': 'validate',
    r'效果': 'effectiveness',
    r'重点关注': 'focus on',
    r'阶段': 'phase',
    r'这两个方面': 'these two aspects',
    r'能带来': 'can bring',
    r'最显著的': 'most significant',
}

def translate_file(filepath):
    """Translate a single file"""
    print(f"Translating {filepath}...")
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Apply translations
    for pattern, replacement in translations.items():
        content = re.sub(pattern, replacement, content)
    
    # Save if changed
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
            f.write(content)
        print(f"  ✓ Translated successfully")
        return True
    else:
        print(f"  - No changes needed")
        return False

def main():
    base_path = r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\ChatMessageList\components\MessageFactory\components\Markdown\EnhanceMarkdown\__tests__'
    
    files = [
        os.path.join(base_path, 'performance', 'performance-optimization-guide.md'),
        os.path.join(base_path, 'performance', 'performance-report.md'),
        os.path.join(base_path, 'xss', 'README.md'),
    ]
    
    translated = 0
    for file in files:
        if os.path.exists(file):
            if translate_file(file):
                translated += 1
        else:
            print(f"File not found: {file}")
    
    print(f"\nTranslation complete! {translated}/{len(files)} files updated.")

if __name__ == '__main__':
    main()

#!/usr/bin/env python3
"""
Script to translate all Chinese strings in zh_CN language files to English.
This preserves array keys and only translates the Chinese values.
"""

import os
import re

# Translation mappings for common terms
TRANSLATIONS = {
    # ASR specific
    '成功': 'Success',
    '请求参数无效': 'Invalid request parameters',
    '无访问权限': 'No access permission',
    '访问频率超限': 'Access frequency limit exceeded',
    '访问配额超限': 'Access quota limit exceeded',
    '未找到 ASR 驱动程序，配置类型': 'ASR driver not found, config type',
    '服务器繁忙': 'Server busy',
    '未知错误': 'Unknown error',
    '音频时长过长': 'Audio duration too long',
    '音频文件过大': 'Audio file too large',
    '音频格式无效': 'Invalid audio format',
    '音频静音': 'Audio is silent',
    '音频文件分析失败': 'Audio file analysis failed',
    '无效的音频参数': 'Invalid audio parameters',
    '识别等待超时': 'Recognition wait timeout',
    '识别处理超时': 'Recognition processing timeout',
    '识别错误': 'Recognition error',
    'WebSocket连接失败': 'WebSocket connection failed',
    '音频文件不存在': 'Audio file does not exist',
    '无法打开音频文件': 'Cannot open audio file',
    '读取音频文件失败': 'Failed to read audio file',
    '音频URL格式无效': 'Invalid audio URL format',
    '音频URL不能为空': 'Audio URL cannot be empty',
    '解压失败': 'Decompression failed',
    'JSON解码失败': 'JSON decode failed',
    '无效的配置': 'Invalid configuration',
    '无效的 delightful id': 'Invalid delightful id',
    '不支持的语言': 'Unsupported language',
    '不支持的 ASR 平台': 'Unsupported ASR platform',
    '无法打开音频 URI': 'Cannot open audio URI',
    '无法读取音频 URI': 'Cannot read audio URI',
    
    # Event specific
    '事件投递失败': 'Event delivery failed',
    '事件发布器未找到': 'Event publisher not found',
    '事件交换机未找到': 'Event exchange not found',
    '事件路由键无效': 'Event routing key invalid',
    '事件消费执行失败': 'Event consumer execution failed',
    '事件消费者未找到': 'Event consumer not found',
    '事件消费超时': 'Event consumer timeout',
    '事件消费重试次数超限': 'Event consumer retry limit exceeded',
    '事件消费参数校验失败': 'Event consumer validation failed',
    '事件数据序列化失败': 'Event data serialization failed',
    '事件数据反序列化失败': 'Event data deserialization failed',
    '事件数据校验失败': 'Event data validation failed',
    '事件数据格式无效': 'Event data format invalid',
    '事件队列连接失败': 'Event queue connection failed',
    '事件队列未找到': 'Event queue not found',
    '事件队列已满': 'Event queue full',
    '事件队列无权限': 'Event queue permission denied',
    '事件处理被中断': 'Event processing interrupted',
    '事件处理发生死锁': 'Event processing deadlock',
    '事件处理资源耗尽': 'Event processing resource exhausted',
    '事件处理依赖失败': 'Event processing dependency failed',
    '事件配置无效': 'Event configuration invalid',
    '事件处理器未注册': 'Event handler not registered',
    '事件监听器注册失败': 'Event listener registration failed',
    '事件系统不可用': 'Event system unavailable',
    '事件系统过载': 'Event system overloaded',
    '事件系统维护中': 'Event system maintenance',
    '积分不足': 'Insufficient points',
    '任务待处理': 'Task pending',
    '任务已停止': 'Task stopped',
    '信用额度不足': 'Insufficient credit limit',
}

def translate_chinese_value(chinese_text):
    """Translate a Chinese string to English using the mapping."""
    for chinese, english in TRANSLATIONS.items():
        if chinese in chinese_text:
            chinese_text = chinese_text.replace(chinese, english)
    return chinese_text

def has_chinese(text):
    """Check if text contains Chinese characters."""
    return bool(re.search(r'[\u4e00-\u9fff]+', text))

def process_language_file(filepath):
    """Process a single language file to translate Chinese strings."""
    print(f"Processing: {filepath}")
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Find all string values in the PHP array
    # Pattern to match: 'key' => 'value',
    pattern = r"('[\w_]+'\s*=>\s*)'([^']*(?:''[^']*)*)'"
    
    def replace_value(match):
        key_part = match.group(1)
        value = match.group(2)
        
        if has_chinese(value):
            translated = translate_chinese_value(value)
            return f"{key_part}'{translated}'"
        return match.group(0)
    
    content = re.sub(pattern, replace_value, content)
    
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"  ✓ Translated")
        return True
    else:
        print(f"  - No changes needed")
        return False

def main():
    zh_cn_dir = r'C:\Users\kubew\magic\backend\delightful-service\storage\languages\zh_CN'
    
    if not os.path.exists(zh_cn_dir):
        print(f"Directory not found: {zh_cn_dir}")
        return
    
    translated_count = 0
    total_count = 0
    
    for filename in os.listdir(zh_cn_dir):
        if filename.endswith('.php'):
            filepath = os.path.join(zh_cn_dir, filename)
            total_count += 1
            if process_language_file(filepath):
                translated_count += 1
    
    print(f"\n=== Summary ===")
    print(f"Total files: {total_count}")
    print(f"Files translated: {translated_count}")
    print(f"Files already in English: {total_count - translated_count}")

if __name__ == '__main__':
    main()

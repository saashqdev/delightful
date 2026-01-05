#!/usr/bin/env python3
"""
Comprehensive translation script for migration files - Phase 2.
Handles remaining Chinese text including mixed Chinese-English and sentences.
"""

import re
from pathlib import Path

# Additional translation patterns
ADDITIONAL_TRANSLATIONS = {
    # Mixed Chinese-English patterns
    "组织encoding": "organization encoding",
    "magic_general_agent_topics 的id": "ID of magic_general_agent_topics",
    "magic_general_agent_task 的id": "ID of magic_general_agent_task",
    "文件storage path": "file storage path",
    "外链address": "external link address",
    "索引": "index",
    "创建索引": "create index",
    "工作区id。": "workspace ID",
    "工作区ID": "workspace ID",
    "话题id。": "topic ID",
    "任务id。sandbox服务返回的": "task ID returned by sandbox service",
    "user的问题。": "user's question",
    "user上传的attachment信息。用 jsonformat存储": "user uploaded attachment information stored in JSON format",
    "工作区目录": "workspace directory",
    "chat的会话id": "chat conversation ID",
    "chat的话题id": "chat topic ID",
    "当前任务id": "current task ID",
    "当前task status waiting, running，finished，error": "current task status: waiting, running, finished, error",
    "话题name": "topic name",
    "deleted时间": "deleted time",
    "资源name": "resource name",
    
    # Documentation comments
    "创建资源分享表": "Create resource shares table",
    "运行迁移": "Run migrations",
    "回滚迁移": "Reverse migrations",
    
    # Additional field descriptions
    "的id": "ID",
    "的ID": "ID",
    "的": "'s",
    "表": "table",
    "字段": "field",
    "列": "column",
}

def comprehensive_translate(text):
    """Comprehensive translation with multiple strategies."""
    original = text
    
    # Strategy 1: Direct full-text match
    if text in ADDITIONAL_TRANSLATIONS:
        return ADDITIONAL_TRANSLATIONS[text]
    
    # Strategy 2: Handle Chinese punctuation
    text = text.replace('，', ', ')
    text = text.replace('。', '. ')
    text = text.replace('：', ': ')
    text = text.replace('（', ' (')
    text = text.replace('）', ') ')
    
    # Strategy 3: Replace known phrases (longest first)
    for cn, en in sorted(ADDITIONAL_TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True):
        if cn in text:
            text = text.replace(cn, en)
    
    # Strategy 4: If still contains Chinese, try word-by-word
    if re.search(r'[\u4e00-\u9fff]', text):
        # Common single characters/words
        single_chars = {
            '用': 'for',
            '于': '',
            '和': 'and',
            '或': 'or',
            '与': 'and',
            '等': 'etc',
            '中': 'in',
            '下': 'under',
            '上': 'on',
            '时': 'when',
            '为': 'as',
            '到': 'to',
            '从': 'from',
            '对': 'to',
            '向': 'to',
            '以': 'with',
            '关': 'related to',
            '由': 'by',
            '通过': 'through',
            '根据': 'according to',
            '基于': 'based on',
        }
        
        for cn, en in single_chars.items():
            text = text.replace(cn, en)
    
    # Clean up extra spaces
    text = re.sub(r'\s+', ' ', text).strip()
    
    return text if text != original else original

def process_file_comprehensive(file_path):
    """Process file with comprehensive translation."""
    try:
        content = file_path.read_text(encoding='utf-8')
        original_content = content
        
        # Pattern 1: ->comment('...')
        def replace_single_quote(match):
            comment_text = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                translated = comprehensive_translate(comment_text)
                return f"->comment('{translated}')"
            return match.group(0)
        
        content = re.sub(r"->comment\('([^']+)'\)", replace_single_quote, content)
        
        # Pattern 2: ->comment("...")
        def replace_double_quote(match):
            comment_text = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                translated = comprehensive_translate(comment_text)
                return f'->comment("{translated}")'
            return match.group(0)
        
        content = re.sub(r'->comment\("([^"]+)"\)', replace_double_quote, content)
        
        # Pattern 3: // Chinese comments
        def replace_line_comment(match):
            indent = match.group(1)
            comment_text = match.group(2)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                translated = comprehensive_translate(comment_text)
                return f"{indent}// {translated}"
            return match.group(0)
        
        content = re.sub(r'^(\s*)//\s*([^\n]+)', replace_line_comment, content, flags=re.MULTILINE)
        
        # Pattern 4: /** docblock comments */
        def replace_docblock(match):
            full_match = match.group(0)
            inner_text = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', inner_text):
                lines = inner_text.split('\n')
                translated_lines = []
                for line in lines:
                    if re.search(r'[\u4e00-\u9fff]', line):
                        # Extract the comment part after *
                        if '*' in line:
                            prefix = line[:line.rfind('*') + 1]
                            comment_part = line[line.rfind('*') + 1:].strip()
                            if comment_part:
                                translated_comment = comprehensive_translate(comment_part)
                                translated_lines.append(f"{prefix} {translated_comment}")
                            else:
                                translated_lines.append(line)
                        else:
                            translated_lines.append(comprehensive_translate(line))
                    else:
                        translated_lines.append(line)
                return f"/**{chr(10).join(translated_lines)}\n */"
            return full_match
        
        content = re.sub(r'/\*\*(.*?)\*/', replace_docblock, content, flags=re.DOTALL)
        
        if content != original_content:
            file_path.write_text(content, encoding='utf-8')
            return True
        return False
    except Exception as e:
        print(f"Error processing {file_path.name}: {e}")
        return False

def main():
    """Main entry point."""
    migrations_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module\migrations')
    
    if not migrations_dir.exists():
        print(f"Directory not found: {migrations_dir}")
        return
    
    files = list(migrations_dir.glob('*.php'))
    print(f"Processing {len(files)} migration files...")
    
    translated = 0
    for file_path in files:
        if process_file_comprehensive(file_path):
            translated += 1
            print(f"  ✓ {file_path.name}")
    
    print(f"\nComplete! Translated {translated} files")

if __name__ == "__main__":
    main()

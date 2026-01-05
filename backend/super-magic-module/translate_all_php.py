#!/usr/bin/env python3
"""
Final comprehensive translation for all remaining Chinese in PHP files.
"""

import re
from pathlib import Path

# Extended translation dictionary
EXTENDED_TRANS = {
    # New patterns found
    "分享代码": "share code",
    "访问password": "access password",
    "查看次数": "view count",
    "组织代码": "organization code",
    "添加index": "add index",
    "创建资源分享表": "Create resource shares table",
    "运行迁移": "Run migrations",
    
    # Additional mixed patterns
    "菜单配置": "menu configuration",
    "是否在菜单中显示": "whether displayed in menu",
    "attachment信息": "attachment information",
    "json格式存储": "stored in JSON format",
    "关联消息": "related messages",
    "来源topic": "source topic",
    "来源task": "source task",
    "复制from": "copied from",
    "IM序列号": "IM sequence ID",
    "是否在UI中显示": "whether displayed in UI",
    "UI中显示": "displayed in UI",
    "是否password保护": "whether password protected",
    "password保护": "password protected",
    "是否启用协作": "whether collaboration enabled",
    "协作enabled": "collaboration enabled",
    "默认加入permission": "default join permission",
    "额外attribute": "extra attributes",
    "图标type": "icon type",
    "图标内容": "icon content",
    
    # Common fragments
    "的id": "ID",
    "的ID": "ID",
    "的": "",
    "配置": "configuration",
    "信息": "information",
    "格式": "format",
    "存储": "stored",
    "显示": "displayed",
    "是否": "whether",
    "关联": "associated",
    "来源": "source",
    "复制": "copied",
    "默认": "default",
    "额外": "extra",
    "图标": "icon",
}

def clean_translate(text):
    """Clean and translate text."""
    original = text.strip()
    
    # Direct match
    if original in EXTENDED_TRANS:
        return EXTENDED_TRANS[original]
    
    # Replace Chinese punctuation
    text = original
    text = text.replace('，', ', ')
    text = text.replace('。', '. ')
    text = text.replace('：', ': ')
    text = text.replace('；', '; ')
    text = text.replace('！', '! ')
    text = text.replace('？', '? ')
    text = text.replace('（', ' (')
    text = text.replace('）', ') ')
    text = text.replace('【', ' [')
    text = text.replace('】', '] ')
    
    # Replace patterns (longest first)
    for cn, en in sorted(EXTENDED_TRANS.items(), key=lambda x: len(x[0]), reverse=True):
        if cn in text:
            text = text.replace(cn, en)
    
    # Clean up spacing
    text = re.sub(r'\s+', ' ', text).strip()
    text = re.sub(r'\s+([,;.!?])', r'\1', text)  # Remove space before punctuation
    text = re.sub(r'([,;.!?])\s*([,;.!?])', r'\1\2', text)  # Merge consecutive punctuation
    
    return text if text else original

def process_all_php(file_path):
    """Process all patterns in PHP file."""
    try:
        content = file_path.read_text(encoding='utf-8')
        if not re.search(r'[\u4e00-\u9fff]', content):
            return False
            
        original = content
        
        # Pattern 1: /* ... */ block comments
        def replace_block_comment(match):
            comment = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', comment):
                return f"/*{clean_translate(comment)}*/"
            return match.group(0)
        
        content = re.sub(r'/\*([^*]*(?:\*(?!/)[^*]*)*)\*/', replace_block_comment, content)
        
        # Pattern 2: // line comments
        def replace_line_comment(match):
            indent = match.group(1)
            comment = match.group(2)
            if re.search(r'[\u4e00-\u9fff]', comment):
                return f"{indent}// {clean_translate(comment)}"
            return match.group(0)
        
        content = re.sub(r'^(\s*)//\s*(.+)$', replace_line_comment, content, flags=re.MULTILINE)
        
        # Pattern 3: ->comment('...')
        def replace_comment_single(match):
            comment_text = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                return f"->comment('{clean_translate(comment_text)}')"
            return match.group(0)
        
        content = re.sub(r"->comment\('([^']+)'\)", replace_comment_single, content)
        
        # Pattern 4: ->comment("...")
        def replace_comment_double(match):
            comment_text = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                return f'->comment("{clean_translate(comment_text)}")'
            return match.group(0)
        
        content = re.sub(r'->comment\("([^"]+)"\)', replace_comment_double, content)
        
        # Pattern 5: /** ... */ PHPDoc comments
        def replace_phpdoc(match):
            doc = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', doc):
                lines = doc.split('\n')
                new_lines = []
                for line in lines:
                    if re.search(r'[\u4e00-\u9fff]', line):
                        # Keep the * prefix structure
                        if '*' in line:
                            prefix_end = line.rfind('*') + 1
                            prefix = line[:prefix_end]
                            text = line[prefix_end:].strip()
                            if text:
                                new_lines.append(f"{prefix} {clean_translate(text)}")
                            else:
                                new_lines.append(line)
                        else:
                            new_lines.append(clean_translate(line))
                    else:
                        new_lines.append(line)
                return f"/**{chr(10).join(new_lines)}\n */"
            return match.group(0)
        
        content = re.sub(r'/\*\*(.*?)\*/', replace_phpdoc, content, flags=re.DOTALL)
        
        if content != original:
            file_path.write_text(content, encoding='utf-8')
            return True
        return False
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return False

def main():
    """Main entry point."""
    base_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module')
    
    # Get all PHP files excluding zh_CN language files
    all_php = list(base_dir.rglob('*.php'))
    php_files = [f for f in all_php if 'zh_CN' not in str(f) and 'languages/zh_CN' not in str(f)]
    
    print(f"Processing {len(php_files)} PHP files...")
    
    translated = 0
    for file_path in php_files:
        if process_all_php(file_path):
            translated += 1
            rel_path = file_path.relative_to(base_dir)
            if translated <= 50:  # Only print first 50
                print(f"  ✓ {rel_path}")
            elif translated == 51:
                print(f"  ... and more ...")
    
    print(f"\nComplete! Translated {translated}/{len(php_files)} files")

if __name__ == "__main__":
    main()

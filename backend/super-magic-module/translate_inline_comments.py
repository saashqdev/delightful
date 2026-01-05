#!/usr/bin/env python3
"""
Final pass - translate inline mixed English-Chinese comments.
"""

import re
from pathlib import Path

# Translation for inline comment supplements
INLINE_TRANS = {
    "图片类型": "image types",
    "视频类型": "video types",
    "音频类型": "audio types",
    "文档类型": "document types",
    "文本类型": "text types",
    "代码类型": "code types",
    "压缩文件": "compressed files",
    "其他常见类型": "other common types",
    "避免": "avoid",
    "没有被加载": "not being loaded",
    "i18n文件路径": "i18n file path",
    "错误码范围": "error code range",
    "个可用码": "available codes",
    "分配方案": "allocation plan",
    "个": "",
}

def translate_inline(text):
    """Translate inline comment text."""
    for cn, en in sorted(INLINE_TRANS.items(), key=lambda x: len(x[0]), reverse=True):
        text = text.replace(cn, en)
    
    # Clean up
    text = text.replace('，', ', ')
    text = text.replace('。', '. ')
    text = text.replace('：', ': ')
    text = re.sub(r'\s+', ' ', text).strip()
    
    return text

def process_inline_comments(file_path):
    """Process inline comments with mixed English-Chinese."""
    try:
        content = file_path.read_text(encoding='utf-8')
        if not re.search(r'[\u4e00-\u9fff]', content):
            return False
        
        original = content
        
        # Pattern: // English - Chinese
        def replace_inline(match):
            prefix = match.group(1)
            english_part = match.group(2)
            separator = match.group(3)
            chinese_part = match.group(4)
            
            if re.search(r'[\u4e00-\u9fff]', chinese_part):
                translated = translate_inline(chinese_part)
                # If translation removes all Chinese, just use English part
                if not re.search(r'[\u4e00-\u9fff]', translated):
                    return f"{prefix}// {english_part}"
                else:
                    return f"{prefix}// {english_part}{separator}{translated}"
            return match.group(0)
        
        # Match patterns like: // English - Chinese or // English Chinese
        content = re.sub(
            r'^(\s*)//\s*([A-Za-z0-9\s,\(\)]+?)\s*([-–—])\s*([^\n]+)',
            replace_inline,
            content,
            flags=re.MULTILINE
        )
        
        # Also handle pure Chinese line comments that remain
        def replace_pure_chinese_comment(match):
            indent = match.group(1)
            comment_text = match.group(2)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                translated = translate_inline(comment_text)
                return f"{indent}// {translated}"
            return match.group(0)
        
        content = re.sub(
            r'^(\s*)//\s*([^\n]+)$',
            replace_pure_chinese_comment,
            content,
            flags=re.MULTILINE
        )
        
        if content != original:
            file_path.write_text(content, encoding='utf-8')
            return True
        return False
    except Exception as e:
        print(f"Error: {file_path}: {e}")
        return False

def main():
    """Main entry point."""
    base_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module')
    
    # Get all PHP files excluding zh_CN
    all_php = list(base_dir.rglob('*.php'))
    php_files = [f for f in all_php if 'zh_CN' not in str(f)]
    
    print(f"Processing {len(php_files)} files for inline comments...")
    
    translated = 0
    for file_path in php_files:
        if process_inline_comments(file_path):
            translated += 1
            if translated <= 20:
                print(f"  ✓ {file_path.relative_to(base_dir)}")
    
    print(f"\nTranslated {translated} files with inline comments")

if __name__ == "__main__":
    main()

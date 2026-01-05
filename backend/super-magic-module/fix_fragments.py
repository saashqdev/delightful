#!/usr/bin/env python3
"""
Fix remaining partial translations and Chinese fragments in comments.
"""

import re
from pathlib import Path

# Specific fragment translations
FRAGMENT_TRANS = {
    # Partial sentence fragments
    "但是有可能是访问": "but may be accessed",
    "所以会为": "so it will be",
    "通过分享": "through share",
    "直接调用包含明文密码方法": "directly call method containing plaintext password",
    "在": "in",
    "null": "null",
    
    # More fragments
    "但是": "but",
    "有可能是": "may be",
    "访问": "accessed",
    "所以": "so",
    "会为": "will be",
    "通过": "through",
    "直接": "directly",
    "调用": "call",
    "包含": "containing",
    "明文": "plaintext",
    "密码": "password",
    "方法": "method",
    "分享": "share",
}

def fix_fragments(file_path):
    """Fix remaining Chinese fragments."""
    try:
        content = file_path.read_text(encoding='utf-8')
        if not re.search(r'[\u4e00-\u9fff]', content):
            return False
        
        original = content
        
        # Replace all fragments
        for cn, en in sorted(FRAGMENT_TRANS.items(), key=lambda x: len(x[0]), reverse=True):
            if cn in content:
                content = content.replace(cn, en)
        
        # Clean up any double spaces and formatting
        content = re.sub(r'([,，])\s*([,，])', r',', content)  # Remove double commas
        content = re.sub(r'\s+', ' ', content)  # Normalize spaces (but preserve structure)
        content = re.sub(r'([a-z])\s*,\s*([a-z])', r'\1, \2', content)  # Fix comma spacing
        
        if content != original:
            file_path.write_text(content, encoding='utf-8')
            return True
        return False
    except Exception as e:
        print(f"Error: {file_path}: {e}")
        return False

def main():
    """Main entry."""
    base_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module')
    
    # Get all PHP files
    all_php = list(base_dir.rglob('*.php'))
    php_files = [f for f in all_php if 'zh_CN' not in str(f)]
    
    print(f"Fixing remaining fragments in {len(php_files)} files...")
    
    translated = 0
    for file_path in php_files:
        if fix_fragments(file_path):
            translated += 1
            if translated <= 20:
                print(f"  ✓ {file_path.relative_to(base_dir)}")
    
    print(f"\nFixed {translated} files")
    
    # Final count
    print("\nFinal count...")
    remaining = sum(
        len(re.findall(r'[\u4e00-\u9fff]+', f.read_text(encoding='utf-8', errors='ignore')))
        for f in php_files
    )
    print(f"Remaining Chinese strings: {remaining}")

if __name__ == "__main__":
    main()

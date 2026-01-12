#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Final cleanup translations - targeting remaining single/double character Chinese
cleanup_translations = {
    # Common fragments and single characters
    "避免每次": "avoid each time",
    "发生变化时才重": "re-render only when changed",
    "在分": "at division",
    "通过": "through",
    "和": " and ",
    "差异": "difference",
    "已": "already",
    "只有": "only",
    "增": "increase",
    "唯一": "unique",
    "目": "directory",
    "重": "re",
    "中": "in",
    "计算": "calculate",
    "分": "divide",
    "内": "inside",
    "则": "then",
    "长度为": "length is",
    "为": "for",
    "本": "this",
    "旨在帮助开发者诊断和比较": "intended to help developers diagnose and compare",
    "在不同": "in different",
    "模式下": "in mode",
    "可以针对性地": "can target",
    "大型": "large-scale",
    "效率": "efficiency",
    "可用": "available",
    "原生": "native",
    "支持动态": "support dynamic",
    
    # Common comment starters that weren't caught
    "/** 一": "/** A",
    "// 一": "// A",
    "// 当计算": "// When calculating",
    "// 计算": "// Calculate",
    "// 通过": "// Through",
}

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations (longer matches first)
        sorted_translations = sorted(cleanup_translations.items(), key=lambda x: len(x[0]), reverse=True)
        for chinese, english in sorted_translations:
            if chinese in content:
                content = content.replace(chinese, english)
                change_count += 1
        
        # Remove any remaining isolated Chinese characters in comments/strings (last resort)
        # Only in comment context to avoid breaking code
        content = re.sub(r'//([^/\n]*[\u4e00-\u9fff][^/\n]*)', lambda m: f"// {m.group(1).replace(chr(0x4e00), 'CHS').replace(chr(0x9fff), 'CHE')}", content)
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            filename = filepath.split('\\')[-1]
            print(f"✓ {filename} (cleanup: {change_count})")
            return True
        return False
    except Exception as e:
        print(f"✗ {filepath}: {e}")
        return False

def main():
    """Main function"""
    base_dir = "c:\\Users\\kubew\\magic\\frontend\\delightful-flow\\src\\DelightfulFlow"
    
    count = 0
    for pattern in ['**/*.ts', '**/*.tsx']:
        for filepath in glob.glob(os.path.join(base_dir, pattern), recursive=True):
            if translate_file(filepath):
                count += 1
    
    print(f"\n✅ Cleanup complete: {count} files touched")

if __name__ == '__main__':
    main()

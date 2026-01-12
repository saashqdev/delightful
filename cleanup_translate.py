#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Final cleanup translations - targeting remaining single/double character Chinese
cleanup_translations = {
    # Common fragments and single characters
    "避免every time": "avoid each time",
    "发生变化time才heavy": "re-render only when changed",
    "at分": "at division",
    "through": "through",
    "和": " and ",
    "difference": "difference",
    "已": "already",
    "只has": "only",
    "增": "increase",
    "唯one": "unique",
    "目": "directory",
    "heavy": "re",
    "中": "in",
    "calculation": "calculate",
    "分": "divide",
    "内": "inside",
    "则": "then",
    "long度for": "length is",
    "for": "for",
    "本": "this",
    "旨at帮助开发者诊断和compare": "intended to help developers diagnose and compare",
    "atdifferent": "in different",
    "patternbottom": "in mode",
    "can针对性地": "can target",
    "large型": "large-scale",
    "效率": "efficiency",
    "可用": "available",
    "原生": "native",
    "support动态": "support dynamic",
    
    # Common comment starters that weren't caught
    "/** one": "/** A",
    "// one": "// A",
    "// whencalculation": "// When calculating",
    "// calculation": "// Calculate",
    "// through": "// Through",
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

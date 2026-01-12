#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Translation mapping for remaining comments
translations = {
    # useLoopBodyClick.ts
    "// // 点击的是普通node": "// // Clicking a regular node",
    "// // 如果点击的是loop体内的node, 则loop体内的边都应该是最高层级": "// // If clicking a node inside loop body, all edges in loop body should be highest level",
    
    # useTargetToErrorNode.ts
    "/** 当日志运行error时，定位到error的node */": "/** When logs run into error, locate to the error node */",
    "// 存在error，则定位到error的nodeid": "// If error exists, locate to error node id",
    "// 全都success，则定位到最后一个node": "// All success, then locate to last node",
    
    # Boundary check comments in useFlowEvents.ts (already partially done, complete remaining)
    "// \t\t// 复制父node并update尺寸和位置": "// Copy parent node and update dimensions and position",
    "// \t\t\tposition: { ...parentNode.position }, // 确保 position 是一个新object": "position: { ...parentNode.position }, // Ensure position is a new object",
    "// \t\t\tstyle: { ...parentNode.style }, // 确保 style 是一个新object": "style: { ...parentNode.style }, // Ensure style is a new object",
    "// \t\t// 超过左边界时": "// When exceeding left boundary",
    "// \t\t\tconsole.log(\"超过左边界\")": "console.log(\"Exceeds left boundary\")",
    "// \t\t// 超过右边界时": "// When exceeding right boundary",
    "// \t\t\tconsole.log(\"超过右边界 \",childBounds.width)": "console.log(\"Exceeds right boundary \",childBounds.width)",
    "// \t\t// 超过上边界时，38px为留白的区域": "// When exceeding top boundary, 38px is blank area",
    "// \t\t\tconsole.log(\"超过上边界\")": "console.log(\"Exceeds top boundary\")",
    "// \t\t// 超过下边界时": "// When exceeding bottom boundary",
    "// \t\t\tconsole.log(\"超过下边界\")": "console.log(\"Exceeds bottom boundary\")",
}

# Directories to search
search_dirs = [
    "c:\\Users\\kubew\\magic\\frontend\\delightful-flow\\src\\DelightfulFlow\\components\\FlowDesign\\hooks",
]

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Apply all translations
        for chinese, english in translations.items():
            if chinese in content:
                content = content.replace(chinese, english)
        
        # Only write if changes were made
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✓ Translated: {filepath}")
            return True
        return False
    except Exception as e:
        print(f"✗ Error in {filepath}: {e}")
        return False

def main():
    """Main function"""
    count = 0
    for search_dir in search_dirs:
        # Find all TS and TSX files
        for pattern in ['**/*.ts', '**/*.tsx']:
            for filepath in glob.glob(os.path.join(search_dir, pattern), recursive=True):
                if translate_file(filepath):
                    count += 1
    
    print(f"\nTotal files translated: {count}")

if __name__ == '__main__':
    main()

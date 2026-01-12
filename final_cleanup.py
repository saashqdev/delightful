#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Comprehensive mapping for remaining garbled/partial translations
final_translations = {
    # Common garbled patterns from previous translations
    "itemunique": "unique",
    "ofparameter": "of parameter",
    "prefixfor": "prefix for",
    "for空": "for empty",
    "thenreturnof": "then return of",
    "more_entropybof": "more_entropy of",
    "thenreturnofstring": "then return string",
    "settingsfor": "set to",
    "会在returnof": "will add to return",
    "结尾increase加": "end; add",
    "加额外of": "add extra",
    "（use": "(use",
    "group合": "generator",
    "发生器）": "generator)",
    "使得": "making",
    "更具": "more",
    "unique性": "uniqueness",
    
    # From other files
    "在分": "in division",
    "避免每次": "avoid each",
    "rerender": "re-render",
    "onlyCompare": "only compare",
    "onlyWhen": "only when",
    "markedAsRendered": "marked as rendered",
    "andExtract": "and extract",
    "shouldRender": "should render",
    "shouldOnlyRender": "should only render",
    "whenPanelExpanded": "when panel is expanded",
    "firstExpansion": "first expansion",
    "renderChildItems": "render child items",
    "cache": "cache",
    "trackExpansion": "track expansion",
    "avoidUnnecessary": "avoid unnecessary",
    "avoidRerender": "avoid re-rendering",
    
    # Simple replacements
    "无": "none",
    "有": "has",
    "一": "a",
}

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations in order of length (longest first to avoid partial replacements)
        sorted_trans = sorted(final_translations.items(), key=lambda x: len(x[0]), reverse=True)
        for chinese, english in sorted_trans:
            while chinese in content:
                content = content.replace(chinese, english)
                change_count += 1
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        return False

def main():
    """Main function"""
    base_dir = "c:\\Users\\kubew\\magic\\frontend\\delightful-flow\\src\\DelightfulFlow"
    
    count = 0
    for pattern in ['**/*.ts', '**/*.tsx']:
        for filepath in glob.glob(os.path.join(base_dir, pattern), recursive=True):
            if translate_file(filepath):
                count += 1
    
    print(f"✅ Processed {count} files")

if __name__ == '__main__':
    main()

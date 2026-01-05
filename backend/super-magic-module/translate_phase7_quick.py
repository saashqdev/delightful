#!/usr/bin/env python3
import os
import re
from pathlib import Path

# Focus on high-frequency remaining Chinese terms
TRANSLATIONS = {
    '和': ' and ',
    '或': ' or ',
    '及': ' and ',
    '的': "'s ",
    '是': ' is ',
    '不': ' not ',
    '在': ' in ',
    '从': ' from ',
    '到': ' to ',
    '对': ' for ',
    '中': ' in ',
    '为': ' for ',
    '以': ' by ',
    '与': ' with ',
    '而': ' but ',
    '该': ' this ',
    '这': ' this ',
    '但': ' but ',
    '如': ' if ',
    '等': ' etc ',
    '表': ' table ',
    '字段': ' field ',
    '列': ' column ',
    '数据库': ' database ',
    '表结构': ' table structure ',
    '模式': ' mode ',
    '规划': ' planning ',
    '通用': ' general ',
    '超级麦吉': ' Super Magic ',
    '映射': ' mapping ',
    '关键字': ' keyword ',
    '所有': ' all ',
    '多个': ' multiple ',
    '单个': ' single ',
    '一': ' a ',
    '二': ' binary ',
}

def translate_file(filepath):
    """Translate a single PHP file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Sort by length (longest first) to avoid partial matches
        for chinese, english in sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True):
            content = content.replace(chinese, english)
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        return False

def main():
    os.chdir('.')
    modified = 0
    total = 0
    
    for root, dirs, files in os.walk('.'):
        dirs[:] = [d for d in dirs if d not in ['.git', 'vendor', 'publish', 'node_modules']]
        
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                total += 1
                if translate_file(filepath):
                    modified += 1
    
    print(f"Modified {modified} files out of {total}")

if __name__ == '__main__':
    main()

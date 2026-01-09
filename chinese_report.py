import os
import re
from collections import defaultdict
from pathlib import Path

# Define the workspace root
workspace_root = r'c:\Users\kubew\magic'

# Exclude patterns
exclude_patterns = [
    'translate_zh_CN_languages.py',
    'README.md',
    'README_CN.md', 
    'node_modules',
    'dist',
    '.lock',
    'package-lock.json',
    'yarn.lock',
    'pnpm-lock.yaml'
]

# Chinese character pattern
chinese_pattern = re.compile(r'[\u4e00-\u9fff]+')

# File categories
categories = {
    'frontend_js_ts': [],
    'backend_php': [],
    'backend_python': [],
    'config_files': [],
    'docs': [],
    'other': []
}

def should_exclude(file_path):
    """Check if file should be excluded"""
    for pattern in exclude_patterns:
        if pattern in file_path:
            return True
    return False

def categorize_file(file_path):
    """Categorize file by extension and path"""
    ext = Path(file_path).suffix.lower()
    path_str = str(file_path).lower()
    
    if 'frontend' in path_str and ext in ['.ts', '.tsx', '.js', '.jsx']:
        return 'frontend_js_ts'
    elif 'backend' in path_str and ext == '.php':
        return 'backend_php'
    elif ext == '.py':
        return 'backend_python'
    elif ext in ['.json', '.yaml', '.yml', '.xml', '.toml', '.ini', '.conf']:
        return 'config_files'
    elif 'docs' in path_str or ext == '.md':
        return 'docs'
    else:
        return 'other'

def count_chinese_matches(file_path):
    """Count Chinese character sequences in a file"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            matches = chinese_pattern.findall(content)
            return len(matches)
    except Exception as e:
        return 0

# Walk through the workspace
print('Scanning repository for Chinese strings...')
for root, dirs, files in os.walk(workspace_root):
    # Skip excluded directories
    dirs[:] = [d for d in dirs if d not in ['node_modules', 'dist', '.git', '__pycache__', '.vitepress', 'cache']]
    
    for file in files:
        file_path = os.path.join(root, file)
        
        # Skip excluded files
        if should_exclude(file_path):
            continue
        
        # Count Chinese matches
        match_count = count_chinese_matches(file_path)
        
        if match_count > 0:
            category = categorize_file(file_path)
            rel_path = os.path.relpath(file_path, workspace_root)
            categories[category].append({
                'path': rel_path,
                'count': match_count
            })

# Print summary
print('\n=== CHINESE STRINGS REPORT ===\n')
total_files = sum(len(files) for files in categories.values())
total_matches = sum(sum(f['count'] for f in files) for files in categories.values())

print(f'Total files with Chinese strings: {total_files}')
print(f'Total Chinese string matches: {total_matches}\n')

for category_name, files in categories.items():
    if files:
        category_display = category_name.replace('_', ' ').title()
        print(f'\n## {category_display} ({len(files)} files)')
        print('=' * 60)
        for file_info in sorted(files, key=lambda x: x['count'], reverse=True)[:20]:
            print(f'{file_info["path"]}: {file_info["count"]} matches')

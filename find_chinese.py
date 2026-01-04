import os
import re
from pathlib import Path

# Define the pattern for Chinese characters
chinese_pattern = re.compile(r'[\u4e00-\u9fff]')

# Base directory
base_dir = Path(r'c:\Users\kubew\magic\backend\super-magic')

# Dictionary to store results
files_with_chinese = {}

# Walk through all Python files
for py_file in base_dir.rglob('*.py'):
    try:
        with open(py_file, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            
        if chinese_pattern.search(content):
            # Analyze the content
            lines = content.split('\n')
            has_comments = False
            has_docstrings = False
            has_strings = False
            
            in_docstring = False
            docstring_char = None
            
            for line in lines:
                stripped = line.strip()
                
                # Check for docstrings (triple quotes)
                triple_double = '"""'
                triple_single = "'''"
                
                if triple_double in line or triple_single in line:
                    if triple_double in line:
                        count = line.count(triple_double)
                        if count == 2:
                            if chinese_pattern.search(line):
                                has_docstrings = True
                        elif count == 1:
                            in_docstring = not in_docstring
                            if chinese_pattern.search(line):
                                has_docstrings = True
                    if triple_single in line:
                        count = line.count(triple_single)
                        if count == 2:
                            if chinese_pattern.search(line):
                                has_docstrings = True
                        elif count == 1:
                            in_docstring = not in_docstring
                            if chinese_pattern.search(line):
                                has_docstrings = True
                elif in_docstring:
                    if chinese_pattern.search(line):
                        has_docstrings = True
                
                # Check for comments
                if '#' in line:
                    comment_part = line[line.index('#'):]
                    if chinese_pattern.search(comment_part):
                        has_comments = True
                
                # Check for string literals (simple heuristic)
                # Look for f-strings and regular strings
                if not in_docstring:
                    string_matches = re.finditer(r'["\']([^"\']*)["\']', line)
                    for match in string_matches:
                        if chinese_pattern.search(match.group(1)):
                            has_strings = True
            
            # Store results
            content_types = []
            if has_comments:
                content_types.append('comments')
            if has_docstrings:
                content_types.append('docstrings')
            if has_strings:
                content_types.append('string literals')
            
            rel_path = py_file.relative_to(base_dir.parent)
            files_with_chinese[str(rel_path)] = content_types
    
    except Exception as e:
        pass

# Print results
print(f'Found {len(files_with_chinese)} Python files with Chinese characters:\n')
print('='*80)
for file_path in sorted(files_with_chinese.keys()):
    content_types = files_with_chinese[file_path]
    print(f'\n{file_path}')
    print(f'  Content types: {", ".join(content_types)}')

print('\n' + '='*80)
print(f'\nTotal: {len(files_with_chinese)} files')

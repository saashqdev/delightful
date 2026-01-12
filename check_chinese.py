import os
import re

pattern = re.compile(r'[\u4e00-\u9fff]')
results = []

for root, dirs, files in os.walk('c:/Users/kubew/magic'):
    # Skip certain directories
    if any(skip in root for skip in ['node_modules', '.git', 'dist', 'build', '__pycache__', '.next', 'venv']):
        continue
    
    for file in files:
        if not file.endswith(('.md', '.ts', '.tsx', '.js', '.py')):
            continue
        
        filepath = os.path.join(root, file)
        try:
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                chinese_count = len(pattern.findall(content))
                if chinese_count > 0:
                    results.append((filepath.replace(os.sep, '/'), chinese_count))
        except:
            pass

results.sort(key=lambda x: x[1], reverse=True)

print(f"Total Chinese characters: {sum(c for _, c in results)}")
print(f"Total files with Chinese: {len(results)}\n")

# Separate production files from test/tool files
production_files = []
test_files = []
tool_files = []

for path, count in results:
    if 'translate' in path.lower() or 'batch_' in path:
        tool_files.append((path, count))
    elif '__tests__' in path or '.dumi/tmp' in path:
        test_files.append((path, count))
    else:
        production_files.append((path, count))

print(f"Production files: {len(production_files)} files, {sum(c for _, c in production_files)} chars")
for path, count in production_files[:10]:
    print(f"  {path.split('/')[-1]}: {count}")

print(f"\nTest files: {len(test_files)} files, {sum(c for _, c in test_files)} chars")
for path, count in test_files[:5]:
    print(f"  {path.split('/')[-1]}: {count}")

print(f"\nTool files: {len(tool_files)} files, {sum(c for _, c in tool_files)} chars")
for path, count in tool_files[:5]:
    print(f"  {path.split('/')[-1]}: {count}")

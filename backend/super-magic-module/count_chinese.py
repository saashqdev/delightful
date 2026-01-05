import os
import re

chinese_count = 0
for root, dirs, files in os.walk('.'):
    dirs[:] = [d for d in dirs if d not in ['.git', 'vendor', 'publish', 'node_modules']]
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                    matches = re.findall(r'[\u4e00-\u9fff]+', content)
                    chinese_count += len(matches)
            except:
                pass

print(f"Total Chinese characters/strings remaining: {chinese_count}")

import os
import re
from collections import Counter

# Count remaining Chinese strings
chinese_pattern = re.compile(r'[\u4e00-\u9fff]+')
all_phrases = []

for root, dirs, files in os.walk('.'):
    # Skip vendor and publish directories
    dirs[:] = [d for d in dirs if d not in ['.git', 'vendor', 'publish', 'node_modules']]
    
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                    matches = chinese_pattern.findall(content)
                    all_phrases.extend(matches)
            except:
                pass

# Count frequency
phrase_counts = Counter(all_phrases)
print("Top 80 most frequent remaining Chinese phrases:")
print("=" * 60)
for phrase, count in phrase_counts.most_common(80):
    print(f"{phrase}: {count} occurrences")

print("\n" + "=" * 60)
print(f"Total unique phrases remaining: {len(phrase_counts)}")
print(f"Total Chinese characters found: {len(all_phrases)}")

#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import re
from collections import Counter

# Load the JSON file
with open('frontend/delightful-web/src/opensource/pages/chatNew/components/AiImageStartPage/image_prompt.json', 'r', encoding='utf-8') as f:
    json_data = json.load(f)

# Collect all remaining Chinese characters
all_chinese = []
samples = []

for category in json_data.get('data', []):
    for image_item in category.get('images', []):
        if 'prompt' in image_item:
            prompt = image_item['prompt']
            chinese_chars = re.findall(r'[\u4e00-\u9fff]', prompt)
            if chinese_chars:
                all_chinese.extend(chinese_chars)
                if len(samples) < 20:
                    samples.append((prompt, len(chinese_chars)))

# Count frequency
char_freq = Counter(all_chinese)

print(f"Total remaining Chinese characters: {len(all_chinese)}")
print(f"Unique Chinese characters: {len(char_freq)}")
print(f"\nTop 100 most frequent characters:")

# Write to file to avoid console encoding issues
with open('remaining_chars.txt', 'w', encoding='utf-8') as f:
    f.write(f"Total: {len(all_chinese)}\n")
    f.write(f"Unique: {len(char_freq)}\n\n")
    f.write("Top 100 most frequent:\n")
    for char, count in char_freq.most_common(100):
        f.write(f"{char}: {count}\n")
    
    f.write("\n\nSample prompts with Chinese:\n")
    for prompt, count in samples[:20]:
        f.write(f"\nCount: {count}\n{prompt}\n")

print("Analysis saved to remaining_chars.txt")
print(f"\nMost frequent characters (top 20):")
for i, (char, count) in enumerate(char_freq.most_common(20), 1):
    # Try to print, but catch encoding errors
    try:
        print(f"{i}. '{char}': {count} times")
    except:
        print(f"{i}. [char]: {count} times")

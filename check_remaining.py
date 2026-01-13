import json
import re

# Load JSON
with open('frontend/delightful-web/src/opensource/pages/chatNew/components/AiImageStartPage/image_prompt.json', encoding='utf-8') as f:
    data = json.load(f)

# Collect all unique Chinese characters
chinese_chars = set()
for category in data.get('data', []):
    for item in category.get('images', []):
        prompt = item.get('prompt', '')
        for match in re.findall(r'[\u4e00-\u9fff]+', prompt):
            chinese_chars.update(match)

print(f"Unique Chinese characters found: {''.join(sorted(chinese_chars))}")
print(f"\nTotal unique: {len(chinese_chars)}")

# Show sample prompts with Chinese
sample_count = 0
for category in data.get('data', []):
    for item in category.get('images', []):
        prompt = item.get('prompt', '')
        if re.search(r'[\u4e00-\u9fff]', prompt) and sample_count < 5:
            print(f"\nSample {sample_count + 1}: {prompt[:200]}...")
            sample_count += 1

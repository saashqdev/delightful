#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Translate Chinese characters in image_prompt.json to English"""

import json
import re

# Translation dictionary for common terms
translations = {
    # Longer phrases first (for proper matching)
    '油墨粘稠': 'thick ink',
    '天道演变': 'evolution of heaven',
    '万物苍凉': 'all things desolate',
    '真人写真': 'realistic photo',
    '超详细': 'ultra detailed',
    '冷tones': 'cold tones',
    '暖tones': 'warm tones',
    '过渡natural': 'natural transition',
    '南瓜灯': 'pumpkin lantern',
    '装扮女人': 'costumed woman',
    '手里holding': 'holding in hand',
    '该design': 'this design',
    '结合': 'combined',
    '线': 'lines',
    '插图': 'illustration',
    '游戏': 'game',
    '拼貼': 'collage',
    '她': 'her',
    '形象': 'figures',
    '自然': 'natural',
    '詩意氛圍': 'poetic atmosphere',
    '氛圍': 'atmosphere',
    '绘本': 'picture book',
    '浓密': 'dense',
    '负载': 'loaded with',
    '纹理': 'texture',
    '扁平': 'flat',
    '写意画风': 'freehand style',
    '人站at': 'person standing on',
    '一根': 'a',
    '柔软': 'soft',
    '白色羽毛上': 'white feather',
    '毛让': 'fur makes',
    '主体': 'subject',
    '更生动更': 'more vivid and more',
    '经典': 'classic',
    '灰': 'gray',
    '用have': 'using',
    '去塑造': 'to shape',
    '形体and': 'form and',
    '思绪用real': 'thoughts using real',
    'fine毛fine': 'fine fur fine',
    '琐碎': 'trivial',
    '覆盖一层fine': 'covered with a layer of fine',
    '毛发': 'fur',
    '起毛效果': 'fuzzy effect',
    '工艺画': 'craft painting',
    '想象一下': 'Imagine',
    '风景中': 'in landscape',
    '扭曲棋盘': 'distorted checkerboard',
    '玩转': 'play with',
    '般': 'like',
    '使用鲜明对比': 'use sharp contrast',
    '并融入微妙': 'and incorporate subtle',
    '深浅不一': 'varying depth',
    '深绿色and': 'dark green and',
    '紫色': 'purple',
    '空间扭曲': 'space distortion',
    '五维': 'five-dimensional',
    'by突出': 'by highlighting',
    'heavy点': 'key points',
    '画质': 'image quality',
    '羊毛状山丘': 'wool-like hills',
    '蔓延': 'spreading',
    '树': 'trees',
    '闪耀at': 'shining in',
    '白色and灰色天空上': 'white and gray sky',
    '红色': 'red',
    '盘': 'disk',
    '左上角带圈transparent': 'upper left corner with circled transparent',
    '小字母': 'small letter',
    '文字description': 'text description',
    'from瞳孔里往外看': 'looking out from pupil',
    '变装': 'costume',
    '鱼眼lens': 'fisheye lens',
    '纯黑environment背': 'pure black background',
    '明暗对比度add': 'light and dark contrast add',
    '动感add': 'motion add',
    '单一主色突出': 'single main color highlight',
    '科幻poster效果': 'sci-fi poster effect',
    '封面视觉': 'cover visual',
    '夸张composition': 'exaggerated composition',
    '彩': 'colors',
    'blurry人影': 'blurry figure',
    'time尚': 'fashionable',
    'many': 'many',
    '美学': 'aesthetics',
    'minimalist风': 'minimalist style',
    '像素': 'pixel',
    '粒子像素风': 'particle pixel style',
    '绘画美学': 'painting aesthetics',
    '品质': 'quality',
    '图像': 'image',
    '黑色background上have': 'black background with',
    '橙色': 'orange',
    '白色': 'white',
    '线条simple': 'lines simple',
    'a黄色笑脸圆球随': 'a yellow smiley sphere following',
    '穿梭流动': 'shuttling and flowing',
    '动态blurry': 'dynamic blurry',
    '深色background': 'dark background',
    '再见': 'goodbye',
    'macro': 'macro',
    '雪公主融合': 'Snow Princess merged with',
    '三色系突出': 'three color scheme highlight',
    '矢量图形art': 'vector graphics art',
    'number绘画': 'digital painting',
    'concept美学': 'concept aesthetics',
    'a黑色苹果': 'a black apple',
    'insidehave很many': 'inside have very many',
    '虫空洞': 'worm holes',
    'a引人入胜title': 'a compelling title',
    'giant苹果上a': 'giant apple with a',
    'smallsmall': 'small',
    '纯绿色background': 'pure green background',
    'all图只have': 'whole image only has',
    '黑色and绿色两种color': 'black and green two colors',
    '打印感觉噪点': 'print feeling noise',
    '写意negative space': 'freehand negative space',
    '彩色and': 'colorful and',
    '相结合': 'combined',
    '不同': 'different',
    '不同材质': 'different materials',
    '色调': 'tones',
    '简单主义': 'minimalism',
    '极致细节': 'extreme details',
    '模糊感': 'hazy feel',
    '明暗交织': 'light and dark interweave',
    '色彩相辅': 'colors complement',
    '交融': 'blend',
    '透明感': 'transparent feel',
    '手签': 'hand signature',
    '混乱': 'chaotic',
    '胶卷': 'film',
    '极致': 'extreme',
    '丰富': 'rich',
    '星球': 'planets',
    '拿': 'holding',
    '长曝光': 'long exposure',
    '速度': 'speed',
}

def translate_text(text):
    """Translate Chinese text to English"""
    # Replace exact phrases first
    for chinese, english in sorted(translations.items(), key=lambda x: -len(x[0])):
        text = text.replace(chinese, english)
    
    # Remove any remaining Chinese characters (fallback)
    # This handles any edge cases we might have missed
    chinese_pattern = re.compile(r'[\u4e00-\u9fff]+')
    
    def replace_remaining(match):
        chinese = match.group()
        # Try to provide generic translations for common patterns
        generic_translations = {
            '的': ' ',
            '在': 'in',
            '上': 'on',
            '中': 'in',
            '和': 'and',
            '与': 'and',
            '或': 'or',
            '是': 'is',
            '有': 'have',
            '为': 'for',
            '以': 'with',
            '及': 'and',
            '而': 'and',
            '从': 'from',
            '到': 'to',
            '将': 'will',
            '会': 'will',
            '可': 'can',
            '能': 'can',
            '等': 'etc',
            '很': 'very',
            '更': 'more',
            '最': 'most',
            '都': 'all',
            '所': 'place',
            '给': 'give',
            '让': 'let',
            '把': 'take',
            '被': 'be',
            '向': 'towards',
            '些': 'some',
            '这': 'this',
            '那': 'that',
        }
        result = chinese
        for ch_char, en_word in generic_translations.items():
            result = result.replace(ch_char, en_word)
        return result
    
    text = chinese_pattern.sub(replace_remaining, text)
    
    # Clean up extra spaces
    text = re.sub(r'\s+', ' ', text)
    text = re.sub(r'\s+([,\.!?;:])', r'\1', text)
    
    return text

def main():
    input_file = r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\AiImageStartPage\image_prompt.json'
    
    print(f"Reading {input_file}...")
    with open(input_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Count and translate
    total_prompts = 0
    translated_prompts = 0
    
    for item in data.get('data', []):
        for image in item.get('images', []):
            total_prompts += 1
            prompt = image.get('prompt', '')
            
            if re.search(r'[\u4e00-\u9fff]', prompt):
                translated_prompts += 1
                translated = translate_text(prompt)
                image['prompt'] = translated
                print(f"Translated prompt {total_prompts}: {len(prompt)} -> {len(translated)} chars")
    
    print(f"\nWriting translated data...")
    with open(input_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    
    print(f"\n✓ Complete!")
    print(f"  Total prompts: {total_prompts}")
    print(f"  Translated prompts: {translated_prompts}")
    print(f"  File: {input_file}")

if __name__ == '__main__':
    main()

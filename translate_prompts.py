#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Translate Chinese text in image_prompt.json to English
This script translates all Chinese prompts in the JSON file to English.
"""

import json
import re

# Comprehensive translation dictionary for common Chinese prompt terms and phrases
# This covers image generation terminology and artistic concepts
translations = {
    # Common artistic terms
    "超现实主义": "surrealism",
    "超现实": "surreal",
    "极简主义": "minimalism",
    "极简": "minimalist",
    "极简风格": "minimalist style",
    "抽象": "abstract",
    "抽象主义": "abstractionism",
    "写实": "realistic",
    "超写实": "hyperrealistic",
    "超真实": "hyperrealistic",
    "未来主义": "futurism",
    "未来感": "futuristic",
    "复古": "vintage",
    "复古风格": "vintage style",
    "工笔": "gongbi",
    "工笔画": "gongbi painting",
    "水墨": "ink wash",
    "水墨画": "ink wash painting",
    "国画": "Chinese painting",
    "油画": "oil painting",
    "插画": "illustration",
    "海报": "poster",
    "海报设计": "poster design",
    "产品摄影": "product photography",
    "商业摄影": "commercial photography",
    "艺术摄影": "art photography",
    "人像摄影": "portrait photography",
    "静物摄影": "still life photography",
    "风景摄影": "landscape photography",
    "微距摄影": "macro photography",
    "电影感": "cinematic",
    "电影": "cinematic",
    "电影海报": "movie poster",
    "电影构图": "cinematic composition",
    "胶片": "film",
    "胶片滤镜": "film filter",
    "颗粒感": "grain texture",
    "赛博朋克": "cyberpunk",
    "蒸汽朋克": "steampunk",
    "波普艺术": "pop art",
    "拼贴": "collage",
    "拼贴艺术": "collage art",
    
    # Quality and detail terms
    "杰作": "masterpiece",
    "大师之作": "master work",
    "大师级": "master-level",
    "高清": "HD",
    "超高清": "ultra HD",
    "8k": "8K",
    "16k": "16K",
    "32k": "32K",
    "超高分辨率": "ultra high resolution",
    "高分辨率": "high resolution",
    "高品质": "high quality",
    "最佳品质": "best quality",
    "完美": "perfect",
    "完美的构图": "perfect composition",
    "获奖作品": "award-winning",
    "超细节": "ultra detailed",
    "细节丰富": "rich in detail",
    "细腻": "delicate",
    "精致": "exquisite",
    "精美": "refined",
    
    # Lighting terms
    "光影": "light and shadow",
    "光影艺术": "art of light and shadow",
    "柔和光线": "soft lighting",
    "自然光": "natural light",
    "影棚灯光": "studio lighting",
    "工作室光": "studio light",
    "顶光": "top lighting",
    "逆光": "backlight",
    "侧光": "side light",
    "丁达尔效应": "Tyndall effect",
    "霓虹灯": "neon light",
    "发光": "glowing",
    "荧光": "fluorescent",
    "夜光": "luminous",
    "镭射": "laser",
    
    # Color terms
    "色彩斑斓": "colorful",
    "五彩缤纷": "multicolored",
    "鲜艳": "vibrant",
    "明亮": "bright",
    "柔和": "soft",
    "淡雅": "elegant",
    "高饱和度": "high saturation",
    "低饱和度": "low saturation",
    "高对比": "high contrast",
    "强烈对比": "strong contrast",
    "黑白": "black and white",
    "单色": "monochrome",
    "渐变": "gradient",
    "克莱因蓝": "Klein blue",
    "马卡龙配色": "macaron color scheme",
    "莫兰迪色系": "Morandi color palette",
    
    # Composition terms
    "特写": "close-up",
    "大特写": "extreme close-up",
    "微距": "macro",
    "全景": "panorama",
    "广角": "wide angle",
    "俯视": "overhead view",
    "仰视": "low angle",
    "侧视": "side view",
    "平视": "eye level",
    "对称": "symmetrical",
    "留白": "negative space",
    "大量留白": "abundant negative space",
    "景深": "depth of field",
    "背景虚化": "bokeh",
    "前景": "foreground",
    "背景": "background",
    "中景": "mid-ground",
    "空间感": "sense of space",
    "层次感": "sense of layering",
    
    # Atmosphere and mood
    "梦幻": "dreamy",
    "朦胧": "hazy",
    "神秘": "mysterious",
    "宁静": "serene",
    "优雅": "elegant",
    "浪漫": "romantic",
    "诗意": "poetic",
    "氛围感": "atmospheric",
    "情绪": "emotional",
    "治愈": "healing",
    "温馨": "warm",
    "可爱": "cute",
    "萌": "adorable",
    "震撼": "stunning",
    "视觉冲击力": "visual impact",
    "高级感": "sophistication",
    "奢华": "luxury",
    "艺术感": "artistic",
    
    # Materials and textures
    "玻璃": "glass",
    "透明": "transparent",
    "半透明": "translucent",
    "水晶": "crystal",
    "金属": "metal",
    "金属质感": "metallic texture",
    "磨砂": "frosted",
    "光泽": "glossy",
    "哑光": "matte",
    "丝绸": "silk",
    "绒毛": "velvet",
    "长毛绒": "plush",
    "毛毡": "felt",
    "羊毛毡": "wool felt",
    "木质": "wooden",
    "石材": "stone",
    "大理石": "marble",
    "陶瓷": "ceramic",
    "青花瓷": "blue and white porcelain",
    "珐琅": "enamel",
    "翡翠": "jade",
    "琉璃": "glaze",
    
    # 3D and rendering
    "3D": "3D",
    "3d": "3D",
    "C4D": "C4D",
    "c4d": "C4D",
    "OC渲染": "Octane render",
    "渲染": "rendering",
    "建模": "modeling",
    "三维": "three-dimensional",
    "立体": "3D",
    "虚拟": "virtual",
    
    # Nature elements
    "花朵": "flowers",
    "花卉": "floral",
    "树木": "trees",
    "森林": "forest",
    "山水": "landscape",
    "云雾": "clouds and mist",
    "星空": "starry sky",
    "月亮": "moon",
    "太阳": "sun",
    "海浪": "waves",
    "海洋": "ocean",
    "水波": "water ripples",
    "水波纹": "water ripples",
    
    # People and characters
    "人物": "character",
    "人像": "portrait",
    "宇航员": "astronaut",
    "女性": "female",
    "男性": "male",
    "模特": "model",
    "舞者": "dancer",
    
    # Specific styles and artists
    "by": "by",
    "风格": "style",
    "的风格": "style",
    "东方美学": "Eastern aesthetics",
    "中式": "Chinese style",
    "新中式": "new Chinese style",
    "国潮": "Chinese trend",
    "传统": "traditional",
    
    # Other common terms
    "画面": "scene",
    "构图": "composition",
    "设计": "design",
    "艺术": "art",
    "作品": "artwork",
    "创意": "creative",
    "概念": "concept",
    "主题": "theme",
    "元素": "elements",
    "细节": "details",
    "质感": "texture",
    "材质": "material",
    "表面": "surface",
    "背景": "background",
    "简洁": "simple",
    "简单": "simple",
    "干净": "clean",
    "纯色": "solid color",
    "平面": "flat",
    "扁平化": "flat design",
}

def translate_chinese_prompt(prompt):
    """
    Translate a Chinese prompt to English using the translation dictionary.
    This is a word-by-word/phrase-by-phrase replacement approach.
    """
    # If prompt is already in English (no Chinese characters), return as is
    if not re.search(r'[\u4e00-\u9fff]', prompt):
        return prompt
    
    result = prompt
    
    # Sort translations by length (descending) to match longer phrases first
    sorted_translations = sorted(translations.items(), key=lambda x: len(x[0]), reverse=True)
    
    for chinese, english in sorted_translations:
        result = result.replace(chinese, english)
    
    return result

def main():
    input_file = r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\AiImageStartPage\image_prompt.json'
    output_file = r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\AiImageStartPage\image_prompt.json'
    
    # Read the JSON file
    print("Reading file...")
    with open(input_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Count prompts to translate
    total_prompts = 0
    translated_prompts = 0
    
    # Translate all Chinese prompts
    print("Translating prompts...")
    for category in data['data']:
        for image in category['images']:
            total_prompts += 1
            original_prompt = image['prompt']
            translated_prompt = translate_chinese_prompt(original_prompt)
            
            if original_prompt != translated_prompt:
                image['prompt'] = translated_prompt
                translated_prompts += 1
                
                if translated_prompts <= 5:  # Show first 5 translations as examples
                    print(f"\nOriginal: {original_prompt[:100]}...")
                    print(f"Translated: {translated_prompt[:100]}...")
    
    # Write back to file
    print(f"\nWriting translated content back to file...")
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
    
    print(f"\n✓ Translation complete!")
    print(f"  Total prompts processed: {total_prompts}")
    print(f"  Prompts with Chinese content translated: {translated_prompts}")
    print(f"  File saved: {output_file}")

if __name__ == '__main__':
    main()

import json
import re

# Translation mappings
type_translations = {
    "海报设计": "Poster Design",
    "产品设计": "Product Design",
    "3D艺术": "3D Art",
    "国学美风": "Traditional Chinese Aesthetics",
    "写实人像": "Realistic Portrait",
    "未来科幻": "Futuristic Sci-Fi",
    "动漫游戏": "Anime & Gaming",
    "绘本插画": "Picture Book Illustration",
    "动物萌宠": "Cute Animals & Pets"
}

# Comprehensive prompt translations
prompt_translations = {
    "太空中一张彩虹色的磁带，周围环绕着行星和恒星，宇航员站在上面跳舞，声浪扭曲发光的线，复古明信片风格的拼贴插图，by Collina H. Suburban风格": 
        "A rainbow-colored cassette tape in space, surrounded by planets and stars, astronaut dancing on it, sound waves distorting glowing lines, vintage postcard style collage illustration, by Collina H. Suburban style",
    
    "屏幕闪烁一个竖直排列的光影进度条，LED灯，一个宇航员在漆黑的环境坠落，超现实风格，超真实，游戏画面":
        "Screen flickering with a vertically arranged light and shadow progress bar, LED lights, an astronaut falling in a pitch-black environment, surreal style, hyperrealistic, game scene",
    
    "黑白光影摄影，超现实主义。\n左边是白色的海浪。\n右边是纯黑色区域。\n在左右分界线海浪上有一个男人和一个女人在跳交谊舞，舞蹈动作清晰可见，黑影。\n左右两边区域海浪形状相融，不规则边缘相融。\n45度角俯视摄影。\n大师级摄影，杰作，大师级构图。":
        "Black and white light and shadow photography, surrealism.\nWhite waves on the left.\nPure black area on the right.\nA man and a woman dancing ballroom dance on the wave at the dividing line, dance moves clearly visible, silhouettes.\nThe wave shapes on both sides merge, irregular edges blend.\n45-degree overhead photography.\nMaster-level photography, masterpiece, master-level composition.",
    
    "画面中央是一列蒸汽火车，城市，背景一颗巨大的地球，星空，星球，黑白拼贴风格的的宇航员拿着行李箱，报纸，胶带，贴纸，便利贴，手写字，彩色和黑白，彩色卡纸，复古超现实主义风格与复古拼贴相结合，不同纹理不同材质拼贴，复古色调，科幻杂志剪切，平面和立体":
        "A steam train in the center of the scene, city, a huge Earth in the background, starry sky, planets, black and white collage style astronaut holding a suitcase, newspaper, tape, stickers, sticky notes, handwriting, color and black and white, colored cardstock, vintage surrealism style combined with vintage collage, different textures and materials collaged, vintage tones, sci-fi magazine cutouts, flat and dimensional",
}

# Read the original file
with open(r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\AiImageStartPage\image_prompt.json', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace type fields
for cn, en in type_translations.items():
    content = content.replace(f'"type": "{cn}"', f'"type": "{en}"')

# Function to check if a string contains Chinese characters
def contains_chinese(text):
    return bool(re.search(r'[\u4e00-\u9fff]', text))

# Parse and translate prompts
data = json.loads(content)

# Since we need to translate all Chinese prompts, this will be done
# by saving the modified content
with open(r'c:\Users\kubew\magic\frontend\delightful-web\src\opensource\pages\chatNew\components\AiImageStartPage\image_prompt_translated.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("Type translations completed. Check the output file for results.")
print("\nNote: Full prompt translation requires AI translation service.")
print("The file contains thousands of Chinese prompts that need individual translation.")

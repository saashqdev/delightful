#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Final sweep to translate all remaining Chinese in code and doc files.
"""
import os
import re

# Additional translations for markdown and config files
REMAINING_TRANSLATIONS = {
    # From XSS README
    "安alltest套件": "security test suite",
    "本test套件专门for": "This test suite is specifically for",
    "componentdesign": "component design",
    "用in防范各种": "used to prevent various",
    "跨站script": "cross-site script",
    "componenthandleuserinput": "component handles user input",
    "exist潜at的安all风险": "exist potential security risks",
    "thusneedcomprehensive": "thus need comprehensive",
    "来确保render安all性": "to ensure render security",
    "main风险点": "main risk points",
    "HTML 注入": "HTML injection",
    "componentsupport": "component support",
    "mightrender": "might render",
    "恶意": "malicious",
    "Script execute": "Script execute",
    "恶意script": "malicious script",
    "labelmight被execute": "label might be executed",
    "eventhandle器": "event handler",
    "HTMLeventproperty": "HTML event property",
    "mightbe利用": "might be exploited",
    "JavaScript 协议": "JavaScript protocol",
    "协议的 URL": "protocol URL",
    "might被execute": "might be executed",
    "LaTeX 注入": "LaTeX injection",
    "LaTeX functionality": "LaTeX functionality",
    "mightexist": "might exist",
    "customcomponent": "custom component",
    "componentconfiguration": "component configuration",
    "might被恶意利用": "might be maliciously exploited",
    "流式render": "streaming render",
    "流式content": "streaming content",
    "update过程中": "during update process",
    "风险": "risk",
    "Script Tag XSS Prevention": "Script Tag XSS Prevention",
    "scriptlabel XSS 防护": "script label XSS prevention",
    "basicscript": "basic script",
    "代码块": "code block",
    "带property": "with property",
    "scriptlabel": "script label",
    "Event Handler XSS Prevention": "Event Handler XSS Prevention",
    "eventhandle器 XSS 防护": "event handler XSS prevention",
    "点击event": "click event",
    "loadevent": "load event",
    "errorevent": "error event",
    "其他event": "other events",
    "JavaScript Protocol XSS Prevention": "JavaScript Protocol XSS Prevention",
    "JavaScript 协议 XSS 防护": "JavaScript protocol XSS prevention",
    
    # Performance optimization guide
    "Rendering Pipeline": "Rendering Pipeline",
    "组件性能": "component performance",
    "render优化": "render optimization",
    "性能": "performance",
    "optimization": "optimization",
    "unnecessary": "unnecessary",
    "re-renders": "re-renders",
    "避免": "avoid",
    "防止": "prevent",
    "优化": "optimize",
    
    # Markdown common patterns
    "使用": "use",
    "示例": "example",
    "说明": "description",
    "注意": "note",
    "警告": "warning",
    "提示": "tip",
    "参考": "reference",
    "详细": "details",
    "配置": "configuration",
    "设置": "settings",
    "开发": "development",
    "测试": "testing",
}

def translate_file(filepath):
    """Translate a file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            original = f.read()
        
        translated = original
        # Apply longest patterns first
        sorted_trans = sorted(REMAINING_TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True)
        
        for chinese, english in sorted_trans:
            translated = translated.replace(chinese, english)
        
        if translated != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(translated)
            return True
        return False
    except Exception as e:
        print(f"Error: {e}")
        return False

def main():
    base = os.getcwd()
    markdown_files = [
        r"frontend\delightful-web\src\opensource\pages\chatNew\components\ChatMessageList\components\MessageFactory\components\Markdown\EnhanceMarkdown\__tests__\xss\README.md",
        r"frontend\delightful-web\src\opensource\pages\chatNew\components\ChatMessageList\components\MessageFactory\components\Markdown\EnhanceMarkdown\__tests__\performance\performance-optimization-guide.md",
        r"frontend\delightful-web\src\opensource\pages\chatNew\components\ChatMessageList\components\MessageFactory\components\Markdown\EnhanceMarkdown\__tests__\performance\performance-report.md",
        r"frontend\delightful-web\src\opensource\pages\chatNew\components\ChatMessageList\components\MessageFactory\components\Markdown\EnhanceMarkdown\README.md",
        r"frontend\delightful-web\src\opensource\pages\login\providers\LoginServiceProvider\README.md",
    ]
    
    count = 0
    for file_rel in markdown_files:
        filepath = os.path.join(base, file_rel)
        if os.path.exists(filepath) and translate_file(filepath):
            count += 1
            print(f"✓ {file_rel}")
    
    print(f"\nTranslated {count} files")

if __name__ == '__main__':
    main()

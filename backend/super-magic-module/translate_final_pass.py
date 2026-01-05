#!/usr/bin/env python3
"""
Ultimate comprehensive translation - catch all remaining Chinese in comments.
"""

import re
from pathlib import Path

# Comprehensive translation dictionary
FINAL_TRANS = {
    # PHPDoc and comment phrases
    "校验沙箱": "Validate sandbox",
    "从": "from",
    "中获取": "get from",
    "获取": "get",
    "字段": "field",
    "然后": "then",
    "对比": "compare",
    "是否一致": "whether consistent",
    "全面兼容": "fully compatible",
    "格式": "format",
    "按顺序": "in order",
    "尝试": "try",
    "请求头": "request header",
    "请求体": "request body",
    "查询参数": "query parameter",
    "指定": "specified",
    "列表": "list",
    "令牌": "token",
    "添加": "add",
    "放在": "place after",
    "后面": "after",
    "回滚when移除": "rollback when removing",
    "移除": "remove",
    "创建资源分享": "Create resource share",
    "请求上下文": "request context",
    "分享information": "share information",
    "如果参数无效或操作失败则抛出异常": "throws exception if parameters are invalid or operation fails",
    "取消资源分享": "Cancel resource share",
    "分享ID": "share ID",
    "取消结果": "cancellation result",
    "设置用户授权information": "set user authorization information",
    "设置": "set",
    "用户": "user",
    "授权": "authorization",
    
    # Common fragments
    "的": "",
    "。": ". ",
    "，": ", ",
    "：": ": ",
    "；": "; ",
    "（": " (",
    "）": ")",
}

def ultimate_translate(text):
    """Ultimate translation with all patterns."""
    original = text.strip()
    
    # Replace all known phrases (longest first)
    result = original
    for cn, en in sorted(FINAL_TRANS.items(), key=lambda x: len(x[0]), reverse=True):
        result = result.replace(cn, en)
    
    # Clean up spacing
    result = re.sub(r'\s+', ' ', result).strip()
    result = re.sub(r'\s+([,;.!?])', r'\1', result)
    result = re.sub(r'\s+\)', ')', result)
    result = re.sub(r'\(\s+', '(', result)
    
    return result if result != original else original

def process_ultimate(file_path):
    """Ultimate processing of all Chinese text."""
    try:
        content = file_path.read_text(encoding='utf-8')
        if not re.search(r'[\u4e00-\u9fff]', content):
            return False
        
        original = content
        
        # Pattern 1: Single-line PHPDoc /** * Chinese */
        def replace_single_line_phpdoc(match):
            doc_content = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', doc_content):
                translated = ultimate_translate(doc_content)
                return f"/**\n * {translated}\n */"
            return match.group(0)
        
        content = re.sub(r'/\*\*\s*\*\s*([^*]+?)\s*\*/', replace_single_line_phpdoc, content)
        
        # Pattern 2: Multi-line PHPDoc
        def replace_multi_phpdoc(match):
            doc = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', doc):
                lines = doc.split('\n')
                new_lines = []
                for line in lines:
                    if re.search(r'[\u4e00-\u9fff]', line):
                        # Preserve structure
                        match_star = re.match(r'^(\s*\*\s*)(.*)', line)
                        if match_star:
                            prefix = match_star.group(1)
                            text = match_star.group(2)
                            if text.strip():
                                # Check for @param, @return, @throws patterns
                                param_match = re.match(r'(@\w+\s+[\w\\]+\s+)(.+)', text)
                                if param_match:
                                    annotation = param_match.group(1)
                                    description = param_match.group(2)
                                    if re.search(r'[\u4e00-\u9fff]', description):
                                        translated_desc = ultimate_translate(description)
                                        new_lines.append(f"{prefix}{annotation}{translated_desc}")
                                    else:
                                        new_lines.append(line)
                                else:
                                    translated = ultimate_translate(text)
                                    new_lines.append(f"{prefix}{translated}")
                            else:
                                new_lines.append(line)
                        else:
                            new_lines.append(ultimate_translate(line))
                    else:
                        new_lines.append(line)
                return f"/**{chr(10).join(new_lines)}\n */"
            return match.group(0)
        
        content = re.sub(r'/\*\*(.*?)\*/', replace_multi_phpdoc, content, flags=re.DOTALL)
        
        # Pattern 3: Regular line comments with Chinese
        def replace_line_comment(match):
            indent = match.group(1)
            comment = match.group(2)
            if re.search(r'[\u4e00-\u9fff]', comment):
                translated = ultimate_translate(comment)
                return f"{indent}// {translated}"
            return match.group(0)
        
        content = re.sub(r'^(\s*)//\s*(.+?)$', replace_line_comment, content, flags=re.MULTILINE)
        
        # Pattern 4: Block comments
        def replace_block(match):
            block_content = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', block_content):
                translated = ultimate_translate(block_content)
                return f"/*{translated}*/"
            return match.group(0)
        
        content = re.sub(r'/\*([^*]*(?:\*(?!/)[^*]*)*)\*/', replace_block, content)
        
        # Pattern 5: ->comment() with any remaining Chinese
        def replace_comment_func(match):
            quote = match.group(1)
            comment_text = match.group(2)
            if re.search(r'[\u4e00-\u9fff]', comment_text):
                translated = ultimate_translate(comment_text)
                return f"->comment({quote}{translated}{quote})"
            return match.group(0)
        
        content = re.sub(r"->comment\((['\"])([^'\"]+)\1\)", replace_comment_func, content)
        
        if content != original:
            file_path.write_text(content, encoding='utf-8')
            return True
        return False
    except Exception as e:
        print(f"Error: {file_path}: {e}")
        return False

def main():
    """Main entry."""
    base_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module')
    
    # Get all PHP files excluding zh_CN
    all_php = list(base_dir.rglob('*.php'))
    php_files = [f for f in all_php if 'zh_CN' not in str(f) and 'languages' not in str(f.parent.name)]
    
    print(f"Final translation pass on {len(php_files)} files...")
    
    translated = 0
    for file_path in php_files:
        if process_ultimate(file_path):
            translated += 1
            if translated <= 30:
                print(f"  ✓ {file_path.relative_to(base_dir)}")
            elif translated == 31:
                print(f"  ... ({len(php_files) - 30} more files) ...")
    
    print(f"\nFinal pass complete! Translated {translated} files")
    
    # Final count
    print("\nCounting remaining Chinese...")
    remaining = sum(
        len(re.findall(r'[\u4e00-\u9fff]+', f.read_text(encoding='utf-8', errors='ignore')))
        for f in php_files
    )
    print(f"Remaining Chinese strings: {remaining}")

if __name__ == "__main__":
    main()

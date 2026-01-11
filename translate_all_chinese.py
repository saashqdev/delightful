#!/usr/bin/env python3
"""
Comprehensive Chinese to English translation script for the entire repository.
Translates comments, documentation, and strings while preserving code functionality.
"""

import os
import re
from pathlib import Path
from collections import defaultdict
from typing import Dict, List, Tuple

# Comprehensive translation dictionary
TRANSLATIONS = {
    # Common phrases and words
    "Simplified Chinese": "Simplified Chinese",
    "Chinese": "Chinese",
    "English": "English",
    "Chinese and English": "Chinese and English",
    
    # Documentation and comments
    "documentation": "documentation",
    "tutorial": "tutorial",
    "guide": "guide",
    "description": "description",
    "manual": "manual",
    "note": "note",
    "warning": "warning",
    "error": "error",
    "information": "information",
    "success": "success",
    "failed": "failed",
    
    # Technical terms
    "module": "module",
    "class": "class",
    "function": "function",
    "method": "method",
    "parameter": "parameter",
    "return": "return",
    "return value": "return value",
    "exception": "exception",
    "error handling": "error handling",
    "warning message": "warning message",
    
    # Node types and flow-related
    "node": "node",
    "subprocess": "subprocess",
    "loop": "loop",
    "selector": "selector",
    "tool": "tool",
    "code execution": "code execution",
    "HTTP request": "HTTP request",
    "cloud document": "cloud document",
    "document parsing": "document parsing",
    "spreadsheet parsing": "spreadsheet parsing",
    "image generation": "image generation",
    "personnel retrieval": "personnel retrieval",
    "data storage": "data storage",
    "data loading": "data loading",
    "historical message": "historical message",
    "variable saving": "variable saving",
    "vector": "vector",
    "vector deletion": "vector deletion",
    "vector search": "vector search",
    "vector storage": "vector storage",
    "text segmentation": "text segmentation",
    
    # Common operations
    "create": "create",
    "delete": "delete",
    "update": "update",
    "query": "query",
    "search": "search",
    "save": "save",
    "load": "load",
    "initialize": "initialize",
    "cleanup": "cleanup",
    "validate": "validate",
    "check": "check",
    "get": "get",
    "settings": "set",
    
    # UI and components
    "component": "component",
    "interface": "interface",
    "button": "button",
    "input field": "input field",
    "table": "table",
    "list": "list",
    "dialog": "dialog",
    "window": "window",
    "label": "label",
    "tip": "tip",
    "message": "message",
    
    # Agent and flow-related
    "agent": "agent",
    "flow": "flow",
    "workflow": "workflow",
    "task": "task",
    "command": "command",
    "operation": "operation",
    "status": "status",
    "event": "event",
    "listener": "listener",
    "handle": "handle",
    
    # Data types
    "string": "string",
    "number": "number",
    "boolean": "boolean",
    "object": "object",
    "array": "array",
    "date": "date",
    "time": "time",
    "file": "file",
    
    # Common verbs
    "start": "start",
    "end": "end",
    "complete": "complete",
    "continue": "continue",
    "pause": "pause",
    "cancel": "cancel",
    "retry": "retry",
    "reset": "reset",
    "refresh": "refresh",
    "import": "import",
    "export": "export",
    "upload": "upload",
    "download": "download",
    
    # Configuration and settings
    "configuration": "configuration",
    "settings": "settings",
    "option": "option",
    "property": "property",
    "environment": "environment",
    "Environment Variables": "environment variable",
    "permission": "permission",
    "role": "role",
    "user": "user",
    "account": "account",
    
    # Testing and quality
    "test": "test",
    "unit test": "unit test",
    "integration test": "integration test",
    "coverage": "coverage",
    "performance": "performance",
    "optimization": "optimization",
    "debug": "debug",
    "fix": "fix",
    "bug": "bug",
    
    # File and path related
    "directory": "directory",
    "folder": "folder",
    "path": "path",
    "extension": "extension",
    "format": "format",
    "encoding": "encoding",
    "compress": "compress",
    "decompress": "decompress",
    
    # Comments and documentation patterns
    "Home": "home page",
    "Tutorial": "usage tutorial",
    "Development": "development documentation",
    "what is": "what is",
    "name explanation": "name explanation",
    "core features": "core features",
    "best practices": "best practices",
    "in one sentence": "in one sentence",
    "implement": "implement",
    "complex": "complex",
    "version": "version",
    "update": "update",
    "change log": "change log",
    "license": "license",
    "based on": "based on",
    "release": "release",
    "copyright": "copyright",
    
    # Special locale and language terms
    "Simplified Chinese": "Simplified Chinese",
    "Traditional Chinese": "Traditional Chinese",
    "Home": "Home",
    "Tutorial": "Tutorial",
    "Development": "Development",
    "Version Notes": "Version Notes",
    "Version Changelog": "Version Changelog",
    "Configuration": "Configuration",
    "Contributing Guide": "Contributing Guide",
    "Initialization Guide": "Initialization Guide",
    "Environment Variables": "Environment Variables",
    "Permission Configuration": "Permission Configuration",
    "File Driver": "File Driver",
}

# Files to completely skip (typically locale/language files that should remain in Chinese)
SKIP_FILES = {
    'zh_CN.php',
    'zh.ts',
    'zh_CN',
    'README_CN.md',
    'chineseSpacing.ts',
    'addSpaceBetweenChineseAndEnglish',
    'complete_translation.py',  # This is a translation utility file
}

# File extensions to process
PROCESS_EXTENSIONS = {'.ts', '.tsx', '.js', '.jsx', '.py', '.php', '.md', '.txt', '.html', '.vue'}

# Directories to skip
SKIP_DIRS = {
    'node_modules', 'dist', 'build', '.next', 'vendor', '__pycache__', 
    '.git', '.vscode', '.idea', 'coverage', 'out'
}

def should_process_file(filepath: str) -> bool:
    """Check if file should be processed."""
    filename = os.path.basename(filepath)
    
    # Skip specific files
    for skip in SKIP_FILES:
        if skip in filepath:
            return False
    
    # Check extension
    if not any(filepath.endswith(ext) for ext in PROCESS_EXTENSIONS):
        return False
    
    # Skip files in skip directories
    for skip_dir in SKIP_DIRS:
        if f'\\{skip_dir}\\' in filepath or f'/{skip_dir}/' in filepath:
            return False
    
    return True

def translate_text(text: str, translations: Dict[str, str]) -> str:
    """Translate Chinese text using the translation dictionary."""
    result = text
    
    # Sort by length (longest first) to handle compound terms
    sorted_translations = sorted(translations.items(), key=lambda x: len(x[0]), reverse=True)
    
    for chinese, english in sorted_translations:
        # Create regex pattern to match whole words/phrases
        pattern = re.escape(chinese)
        result = re.sub(pattern, english, result)
    
    return result

def process_file(filepath: str, translations: Dict[str, str]) -> Tuple[bool, int]:
    """
    Process a single file and translate Chinese content.
    Returns (was_modified, character_count_translated)
    """
    try:
        # Read file
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            original_content = f.read()
        
        # Check if file has Chinese
        if not re.search(r'[\u4e00-\u9fff]', original_content):
            return False, 0
        
        # Translate content
        translated_content = translate_text(original_content, translations)
        
        # Calculate characters translated
        char_count = len(re.findall(r'[\u4e00-\u9fff]', original_content))
        
        # Write back only if changed
        if translated_content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(translated_content)
            return True, char_count
        
        return False, 0
    
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return False, 0

def main():
    """Main entry point."""
    root_dir = os.getcwd()
    
    print(f"Starting translation in: {root_dir}")
    print(f"Total translation terms: {len(TRANSLATIONS)}\n")
    
    processed_files = 0
    modified_files = 0
    total_chars_translated = 0
    modified_file_list = []
    
    # Walk through all directories
    for root, dirs, files in os.walk(root_dir):
        # Skip unwanted directories
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        
        for filename in files:
            filepath = os.path.join(root, filename)
            
            if should_process_file(filepath):
                processed_files += 1
                was_modified, char_count = process_file(filepath, TRANSLATIONS)
                
                if was_modified:
                    modified_files += 1
                    total_chars_translated += char_count
                    relative_path = os.path.relpath(filepath, root_dir)
                    modified_file_list.append(f"{relative_path} ({char_count} chars)")
                    print(f"✓ Translated: {relative_path}")
    
    # Print summary
    print(f"\n{'='*60}")
    print(f"Translation Summary")
    print(f"{'='*60}")
    print(f"Files processed: {processed_files}")
    print(f"Files modified: {modified_files}")
    print(f"Total Chinese characters translated: {total_chars_translated}")
    print(f"\nModified files:")
    for file_info in sorted(modified_file_list):
        print(f"  {file_info}")

if __name__ == "__main__":
    main()

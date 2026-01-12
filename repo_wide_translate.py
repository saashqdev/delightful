#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob
from pathlib import Path

# Comprehensive Chinese to English translation mapping
comprehensive_translations = {
    # Common Chinese phrases and words
    "Chinese": "Chinese",
    "English": "English",
    "translation": "translation",
    "comment": "comment",
    "description": "description",
    "documentation": "documentation",
    "example": "example",
    "test": "test",
    "unit test": "unit test",
    "integration test": "integration test",
    "configuration": "configuration",
    "set": "settings",
    "option": "option",
    "parameter": "parameter",
    "property": "property",
    "method": "method",
    "function": "function",
    "class": "class",
    "interface": "interface",
    "type": "type",
    "value": "value",
    "data": "data",
    "array": "array",
    "object": "object",
    "string": "string",
    "number": "number",
    "boolean": "boolean",
    "variable": "variable",
    "constant": "constant",
    "state": "state",
    "belong to": "belong to",
    "event": "event",
    "listen": "listen",
    "listener": "listener",
    "callback": "callback",
    "async": "async",
    "sync": "sync",
    "concurrent": "concurrent",
    "sequence": "sequence",
    "parallel": "parallel",
    "render": "render",
    "component": "component",
    "page": "page",
    "view": "view",
    "model": "model",
    "controller": "controller",
    "service": "service",
    "utility": "utility",
    "helper": "helper",
    "plugin": "plugin",
    "extension": "extension",
    "module": "module",
    "package": "package",
    "library": "library",
    "framework": "framework",
    "application": "application",
    "system": "system",
    "user": "user",
    "client": "client",
    "server": "server",
    "database": "database",
    "cache": "cache",
    "storage": "storage",
    "file": "file",
    "directory": "directory",
    "path": "path",
    "network": "network",
    "communication": "communication",
    "connection": "connection",
    "request": "request",
    "response": "response",
    "error": "error",
    "warning": "warning",
    "information": "information",
    "log": "log",
    "debug": "debug",
    "performance": "performance",
    "optimization": "optimization",
    "improvement": "improvement",
    "fix": "fix",
    "solve": "solve",
    "handle": "handle",
    "manage": "manage",
    "operation": "operation",
    "action": "action",
    "behavior": "behavior",
    "functionality": "functionality",
    "feature": "feature",
    "requirement": "requirement",
    "implement": "implement",
    "receive": "receive",
    "send": "send",
    "read": "read",
    "write": "write",
    "delete": "delete",
    "update": "update",
    "create": "create",
    "get": "get",
    "set": "set",
    "initialize": "initialize",
    "destroy": "destroy",
    "clean": "clean",
    "release": "release",
    "allocate": "allocate",
    "save": "save",
    "load": "load",
    "import": "import",
    "export": "export",
    "check": "check",
    "verify": "verify",
    "compare": "compare",
    "sort": "sort",
    "filter": "filter",
    "map": "map",
    "transform": "transform",
    "merge": "merge",
    "split": "split",
    "copy": "copy",
    "paste": "paste",
    "cut": "cut",
    "select": "select",
    "cancel": "cancel",
    "confirm": "confirm",
    "success": "success",
    "failed": "failed",
    "complete": "complete",
    "start": "start",
    "end": "end",
    "pause": "pause",
    "resume": "resume",
    "stop": "stop",
    "continue": "continue",
    "show": "show",
    "hide": "hide",
    "open": "open",
    "close": "close",
    "enable": "enable",
    "disable": "disable",
    "allow": "allow",
    "reject": "deny",
    "accept": "accept",
    "reject": "reject",
    "agree": "agree",
    "disagree": "disagree",
    "is": "is",
    "no": "no",
    "has": "has",
    "no": "no",
    "exist": "exist",
    "not exist": "not exist",
    "true": "true",
    "false": "false",
    "null": "null",
    "undefined": "undefined",
    "normal": "normal",
    "exception": "exception",
    "special": "special",
    "default": "default",
    "custom": "custom",
    "global": "global",
    "local": "local",
    "public": "public",
    "private": "private",
    "protected": "protected",
    "inside": "internal",
    "outside": "external",
    "inside": "inside",
    "outside": "outside",
    "top": "top",
    "bottom": "bottom",
    "left": "left",
    "right": "right",
    "front": "front",
    "back": "back",
    "middle": "middle",
    "around": "around",
    "surrounding": "surrounding",
    "between": "between",
    "outside": "outside",
    "inside": "inside",
    "less than": "less than",
    "greater than": "greater than",
    "equals": "equals",
    "not equals": "not equals",
    "case": "case",
    "sensitive": "sensitive",
    "insensitive": "insensitive",
    "match": "match",
    "not match": "not match",
    "include": "include",
    "exclude": "exclude",
    "start": "start",
    "end": "end",
    "before": "before",
    "after": "after",
    "during": "during",
    "until": "until",
    "since": "since",
    "from": "from",
    "to": "to",
    "through": "through",
    "by": "by",
    "for": "for",
    "and": "and",
    "or": "or",
    "not": "not",
    "if": "if",
    "else": "else",
    "when": "when",
    "while": "while",
    "for": "for",
    "each": "each",
    "all": "all",
    "any": "any",
    "some": "some",
    "multiple": "multiple",
    "single": "single",
    "few": "few",
    "many": "many",
    "few": "few",
    "large amount": "large amount",
    "unlimited": "unlimited",
    "limited": "limited",
    "one": "one",
    "two": "two",
    "three": "three",
    "four": "four",
    "five": "five",
    "six": "six",
    "seven": "seven",
    "eight": "eight",
    "nine": "nine",
    "ten": "ten",
    "hundred": "hundred",
    "thousand": "thousand",
    "ten thousand": "ten thousand",
    "million": "million",
    "billion": "billion",
    
    # Specific code patterns
    "// ": "// ",
    "/** ": "/** ",
    "* ": "* ",
    
    # Common misspellings from previous attempts
    "of data": "of data",
    "of state": "of state",
    "of value": "of value",
    "of property": "of property",
    "of method": "of method",
    "of function": "of function",
    "of type": "of type",
    "of string": "of string",
    "of number": "of number",
    "of boolean": "of boolean",
    "of array": "of array",
    "of object": "of object",
    "of class": "of class",
    "of interface": "of interface",
    "of event": "of event",
    "of callback": "of callback",
    "of listener": "of listener",
    "of state": "of state",
}

def should_translate_file(filepath):
    """Check if file should be translated"""
    extensions = {'.py', '.ts', '.tsx', '.js', '.jsx', '.json', '.md', '.html', 
                  '.css', '.scss', '.less', '.yaml', '.yml', '.xml', '.sh', '.bash'}
    return any(filepath.endswith(ext) for ext in extensions)

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations (longer matches first to avoid partial replacements)
        sorted_translations = sorted(comprehensive_translations.items(), 
                                    key=lambda x: len(x[0]), reverse=True)
        
        for chinese, english in sorted_translations:
            if chinese in content:
                # For single Chinese characters, be more careful
                if len(chinese) == 1:
                    # Don't replace if it's part of a larger word
                    if not re.search(rf'[\u4e00-\u9fff]{re.escape(chinese)}[\u4e00-\u9fff]', content):
                        content = content.replace(chinese, english)
                        change_count += 1
                else:
                    content = content.replace(chinese, english)
                    change_count += 1
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True, change_count
        return False, 0
    except Exception as e:
        return False, 0

def main():
    """Main function - scan entire repo"""
    base_dir = "c:\\Users\\kubew\\magic"
    
    # Ignore directories
    ignore_dirs = {
        'node_modules', '.git', '.vscode', 'dist', 'build', 'coverage',
        '__pycache__', '.pytest_cache', '.eggs', '*.egg-info', 'venv', 'env'
    }
    
    total_files = 0
    translated_files = 0
    total_changes = 0
    
    for root, dirs, files in os.walk(base_dir):
        # Filter out ignored directories
        dirs[:] = [d for d in dirs if d not in ignore_dirs]
        
        for file in files:
            filepath = os.path.join(root, file)
            
            if should_translate_file(filepath):
                total_files += 1
                changed, count = translate_file(filepath)
                if changed:
                    translated_files += 1
                    total_changes += count
                    rel_path = os.path.relpath(filepath, base_dir)
                    print(f"✓ {rel_path} ({count} replacements)")
    
    print(f"\n{'='*60}")
    print(f"✅ Repository-wide translation complete!")
    print(f"{'='*60}")
    print(f"Total files scanned: {total_files}")
    print(f"Files modified: {translated_files}")
    print(f"Total replacements: {total_changes}")

if __name__ == '__main__':
    main()

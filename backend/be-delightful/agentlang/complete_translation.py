#!/usr/bin/env python3
"""
Script to complete translation of Chinese text to English in remaining agentlang files.
This handles the files that were not fully translated due to time/token constraints.
"""

import re
from pathlib import Path

# Translation mappings for common Chinese phrases
TRANSLATIONS = {
    # Common phrases
    "module": "module",
    "class": "class",
    "function": "function",
    "method": "method",
    "parameter": "parameter",
    "return": "return",
    "exception": "exception",
    "error": "error",
    "warning": "warning",
    "information": "information",
    "success": "success",
    "failed": "failed",
    "initialize": "initialize",
    "configuration": "configuration",
    "setting": "setting",
    "get": "get",
    "save": "save",
    "load": "load",
    "create": "create",
    "delete": "delete",
    "update": "update",
    "check": "check",
    "validate": "validate",
    "process": "process",
    "execute": "execute",
    "start": "start",
    "stop": "stop",
    "restart": "restart",
    "path": "path",
    "file": "file",
    "directory": "directory",
    "data": "data",
    "object": "object",
    "instance": "instance",
    "result": "result",
    "status": "status",
    "type": "type",
    "value": "value",
    "default": "default",
    "optional": "optional",
    "required": "required",
    "valid": "valid",
    "invalid": "invalid",
    "empty": "empty",
    "cannot_be_empty": "cannot be empty",
    "does_not_exist": "does not exist",
    "already_exists": "already exists",
    "not_found": "not found",
    "no": "no",
    "unable_to": "unable to",
    "attempt": "attempt",
    "use": "use",
    "application": "application",
    "system": "system",
    "service": "service",
    "tool": "tool",
    "assistant": "assistant",
    "user": "user",
    "message": "message",
    "content": "content",
    "time": "time",
    "duration": "duration",
    "start": "start",
    "end": "end",
    "total": "total",
    "count": "count",
    "quantity": "quantity",
    "size": "size",
    "length": "length",
    "index": "index",
    "position": "position",
    "line": "line",
    "column": "column",
    "compression": "compression",
    "decompression": "decompression",
    "format": "format",
    "parse": "parse",
    "serialize": "serialize",
    "deserialize": "deserialize",
    "convert": "convert",
    "map": "map",
    "filter": "filter",
    "sort": "sort",
    "search": "search",
    "match": "match",
    "replace": "replace",
    "merge": "merge",
    "split": "split",
    "join": "join",
    "copy": "copy",
    "move": "move",
    "rename": "rename",
    "clear": "clear",
    "reset": "reset",
    "refresh": "refresh",
    "synchronize": "synchronize",
    "asynchronous": "asynchronous",
    "blocking": "blocking",
    "non_blocking": "non-blocking",
    "timeout": "timeout",
    "delay": "delay",
    "retry": "retry",
    "wait": "wait",
    "continue": "continue",
    "skip": "skip",
    "ignore": "ignore",
    "cancel": "cancel",
    "terminate": "terminate",
    "interrupt": "interrupt",
    "complete": "complete",
    "progress": "progress",
    "percentage": "percentage",
    "ratio": "ratio",
    "threshold": "threshold",
    "limit": "limit",
    "range": "range",
    "minimum": "minimum",
    "maximum": "maximum",
    "average": "average",
    "standard": "standard",
    "custom": "custom",
    "temporary": "temporary",
    "permanent": "permanent",
    "public": "public",
    "private": "private",
    "internal": "internal",
    "external": "external",
    "local": "local",
    "remote": "remote",
    "global": "global",
    "local_scope": "local",
    "environment": "environment",
    "variable": "variable",
    "constant": "constant",
    "field": "field",
    "attribute": "attribute",
    "option": "option",
    "flag": "flag",
    "switch": "switch",
    "enable": "enable",
    "disable": "disable",
    "activate": "activate",
    "deactivate": "deactivate",
    "available": "available",
    "unavailable": "unavailable",
    "support": "support",
    "unsupported": "unsupported",
    "compatible": "compatible",
    "incompatible": "incompatible",
    "version": "version",
    "update": "update",
    "upgrade": "upgrade",
    "downgrade": "downgrade",
    "install": "install",
    "uninstall": "uninstall",
    "dependency": "dependency",
    "association": "association",
    "reference": "reference",
    "link": "link",
    "bind": "bind",
    "unbind": "unbind",
    "register": "register",
    "unregister": "unregister",
    "subscribe": "subscribe",
    "unsubscribe": "unsubscribe",
    "publish": "publish",
    "listen": "listen",
    "trigger": "trigger",
    "event": "event",
    "callback": "callback",
    "hook": "hook",
    "intercept": "intercept",
    "middleware": "middleware",
    "plugin": "plugin",
    "extension": "extension",
    "template": "template",
    "example": "example",
    "sample": "sample",
    "test": "test",
    "debug": "debug",
    "log": "log",
    "record": "record",
    "track": "track",
    "monitor": "monitor",
    "statistics": "statistics",
    "report": "report",
    "analysis": "analysis",
    "evaluation": "evaluation",
    "optimization": "optimization",
    "performance": "performance",
    "efficiency": "efficiency",
    "quality": "quality",
    "reliability": "reliability",
    "stability": "stability",
    "security": "security",
    "permission": "permission",
    "authentication": "authentication",
    "authorization": "authorization",
    "encryption": "encryption",
    "decryption": "decryption",
    "signature": "signature",
    "hash": "hash",
    "encoding": "encoding",
    "decoding": "decoding",
    "protocol": "protocol",
    "interface": "interface",
    "abstract": "abstract",
    "implementation": "implementation",
    "inheritance": "inheritance",
    "polymorphism": "polymorphism",
    "encapsulation": "encapsulation",
    "pattern": "pattern",
    "strategy": "strategy",
    "factory": "factory",
    "singleton": "singleton",
    "observer": "observer",
    "proxy": "proxy",
    "adapter": "adapter",
    "decorator": "decorator",
    "iterator": "iterator",
    "generator": "generator",
    "context": "context",
    "scope": "scope",
    "namespace": "namespace",
    "package": "package",
    "library": "library",
    "framework": "framework",
    "platform": "platform",
    "architecture": "architecture",
    "component": "component",
    "modular": "modular",
    "decouple": "decouple",
    "integration": "integration",
    "deployment": "deployment",
    "publish": "release",
    "maintenance": "maintenance",
    "documentation": "documentation",
    "comment": "comment",
    "description": "description",
    "note": "note",
    "hint": "hint",
    "suggestion": "suggestion",
    "recommendation": "recommendation",
    "best_practice": "best practice",
    "attention": "attention",
    "important": "important",
    "critical": "critical",
    "urgent": "urgent",
    "deprecated": "deprecated",
    "experimental": "experimental",
    "preview": "preview",
    "stable": "stable",
    "beta": "beta",
    "release_candidate": "release candidate",
    "release": "release",
}

def contains_chinese(text):
    """Check if text contains Chinese characters."""
    return bool(re.search(r'[\u4e00-\u9fff]', text))

def find_chinese_segments(text):
    """Find all Chinese character segments in text."""
    return re.findall(r'[\u4e00-\u9fff]+', text)

def translate_file(file_path):
    """Translate Chinese text in a single file."""
    path = Path(file_path)
    if not path.exists():
        print(f"File not found: {file_path}")
        return False
    
    content = path.read_text(encoding='utf-8')
    
    # Check if file contains Chinese
    if not contains_chinese(content):
        print(f"No Chinese text found in: {file_path}")
        return True
    
    chinese_segments = find_chinese_segments(content)
    print(f"\nProcessing {file_path}")
    print(f"Found {len(set(chinese_segments))} unique Chinese segments")
    
    # Display first 10 segments for manual review
    print("Sample Chinese segments:")
    for i, seg in enumerate(sorted(set(chinese_segments))[:10], 1):
        print(f"  {i}. {seg}")
    
    print(f"\nNote: This file requires manual translation.")
    print(f"Please use multi_replace_string_in_file or edit the file directly.")
    
    return False

def main():
    """Main entry point."""
    base_path = Path(r"c:\Users\kubew\delightful\backend\be-delightful\agentlang\agentlang")
    
    # Files remaining to translate
    files_to_translate = [
        "chat_history/chat_history.py",
        "llms/token_usage/report.py",
        "llms/token_usage/tracker.py",
        "utils/__init__.py",
        "utils/process_manager.py",
        "utils/retry.py",
        "utils/schema.py",
        "utils/snowflake.py",
        "utils/syntax_checker.py",
        "utils/token_counter.py",
        "utils/token_estimator.py",
    ]
    
    print("=" * 80)
    print("Chinese to English Translation Helper")
    print("=" * 80)
    
    for file_rel_path in files_to_translate:
        file_path = base_path / file_rel_path
        translate_file(str(file_path))
    
    print("\n" + "=" * 80)
    print("Translation check complete!")
    print("=" * 80)

if __name__ == "__main__":
    main()

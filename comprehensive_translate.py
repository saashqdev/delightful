#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import glob

# Comprehensive translation mapping
translations = {
    # General comments
    "// handle点击": "// Handle clicking",
    "// need手动": "// Need to manually",
    "// 此处": "// Here",
    "// if": "// If",
    "// 则": "// Then",
    "// when": "// When",
    "// 确保": "// Ensure",
    "// check": "// Check",
    "// get": "// Get",
    "// return": "// Return",
    "// create": "// Create",
    "// set": "// Set",
    "// update": "// Update",
    "// delete": "// Delete",
    "// 添add": "// Add",
    "// 移除": "// Remove",
    "// trigger": "// Trigger",
    "// listen": "// Listen to",
    "// handle": "// Handle",
    "// 判断": "// Determine",
    "// verify": "// Validate",
    "// calculation": "// Calculate",
    "// initialize": "// Initialize",
    "// reset": "// Reset",
    "// 清null": "// Clear",
    "// show": "// Show",
    "// hide": "// Hide",
    "// disable": "// Disable",
    "// enable": "// Enable",
    "// start": "// Start",
    "// end": "// End",
    "// complete": "// Complete",
    "// failed": "// Failed",
    "// success": "// Success",
    "// error": "// Error",
    "// warning": "// Warning",
    "// information": "// Info",
    "// debug": "// Debug",
    
    # File/variable related
    "/** test": "/** Test",
    "/** whenlogrunerrortime": "/** When logs run error",
    "/** log": "/** Logs",
    "/** performance": "/** Performance",
    "/** Canvas": "/** Canvas",
    "/** ReactFlow": "/** ReactFlow",
    "/** 材料": "/** Material",
    "/** 面板": "/** Panel",
    "/** virtual": "/** Virtual",
    "/** group": "/** Group",
    "/** search": "/** Search",
    "/** option卡": "/** Tab",
    "/** 弹出": "/** Popup",
    "/** 交互": "/** Interaction",
    "/** 流程": "/** Flow",
    "/** node": "/** Node",
    "/** 边": "/** Edge",
    "/** data": "/** Data",
    "/** configuration": "/** Config",
    "/** state": "/** State",
    "/** topbottom文": "/** Context",
    "/** 提供者": "/** Provider",
    "/** 钩子": "/** Hook",
    "/** utility": "/** Util",
    "/** 帮助": "/** Helper",
    
    # Complex phrases
    "// 模拟data": "// Mock data",
    "// testdata": "// Test data",
    "// exampledata": "// Example data",
    "// defaultvalue": "// Default value",
    "// 环境variable": "// Environment variable",
    "// localstorage": "// Local storage",
    "// sessionstorage": "// Session storage",
    "// globalstate": "// Global state",
    "// localstate": "// Local state",
    "// componentstate": "// Component state",
    "// pagestate": "// Page state",
    "// applicationstate": "// Application state",
    "// 未implement": "// Not implemented",
    "// 待implement": "// To be implemented",
    "// needoptimization": "// Needs optimization",
    "// 待optimization": "// To be optimized",
    "// 兼容性": "// Compatibility",
    "// 跨浏览器": "// Cross-browser",
    "// response式": "// Responsive",
    "// 无障碍": "// Accessibility",
    "// 国际化": "// Internationalization",
    "// local化": "// Localization",
    
    # Specific translations already done
    "// 点击的yes普通node": "// Clicking a regular node",
    "// if点击的yesloop体内的node": "// If clicking a node inside loop body",
    "// existerror，则定位toerror的nodeid": "// If error exists, locate to error node id",
    "// all都success，则定位tolastnode": "// All success, then locate to last node",
    
    # Common Chinese patterns in code
    "undefined": "undefined",
    "nullvalue": "null",
    "true": "true",
    "false": "false",
    "success": "success",
    "failed": "failed",
    "error": "error",
    "warning": "warning",
    
    # String literals
    '"点击"': '"Click"',
    '"select"': '"Select"',
    '"delete"': '"Delete"',
    '"edit"': '"Edit"',
    '"confirm"': '"Confirm"',
    '"cancel"': '"Cancel"',
    '"save"': '"Save"',
    '"load中"': '"Loading"',
    '"complete"': '"Complete"',
    '"failed"': '"Failed"',
    '"success"': '"Success"',
    '"error"': '"Error"',
    '"warning"': '"Warning"',
}

def clean_translation_key(key):
    """Ensure translation keys don't have problematic escaping"""
    return key

def translate_file(filepath):
    """Translate a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        change_count = 0
        
        # Apply translations (exact match first, then more flexible)
        for chinese, english in translations.items():
            if chinese in content:
                content = content.replace(chinese, english)
                change_count += 1
        
        # Only write if changes were made
        if content != original_content and change_count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✓ {filepath.split(chr(92))[-1]} ({change_count} translations)")
            return True
        return False
    except Exception as e:
        print(f"✗ Error in {filepath}: {e}")
        return False

def main():
    """Main function"""
    base_dir = "c:\\Users\\kubew\\magic\\frontend\\delightful-flow\\src\\DelightfulFlow"
    
    count = 0
    for pattern in ['**/*.ts', '**/*.tsx']:
        for filepath in glob.glob(os.path.join(base_dir, pattern), recursive=True):
            if translate_file(filepath):
                count += 1
    
    print(f"\nTotal files translated: {count}")

if __name__ == '__main__':
    main()

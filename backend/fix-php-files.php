#!/usr/bin/env php
<?php

/**
 * Fix PHP files that have lost their line breaks
 */

function formatPhpFile($file) {
    $content = file_get_contents($file);
    $lines = substr_count($content, "\n");
    $size = filesize($file);
    
    // Only process files that are likely corrupted (few lines but large size)
    if ($lines > 10 || $size < 1000) {
        return false;
    }
    
    echo "Processing: $file (lines: $lines, size: $size)\n";
    
    // Use PHP tokenizer to properly format
    $tokens = token_get_all($content);
    $output = '';
    $indent = 0;
    $lastToken = null;
    
    foreach ($tokens as $i => $token) {
        if (is_array($token)) {
            list($id, $text) = $token;
            
            // Add newlines after certain tokens
            if ($id === T_OPEN_TAG) {
                $output .= $text . "\n";
            } elseif (in_array($id, [T_COMMENT, T_DOC_COMMENT])) {
                $output .= $text . "\n";
            } elseif ($id === T_WHITESPACE) {
                // Skip most whitespace, we'll add our own
                if (strpos($text, "\n") !== false) {
                    $output .= "\n";
                } else {
                    $output .= ' ';
                }
            } else {
                $output .= $text;
            }
            $lastToken = $id;
        } else {
            // String token
            if ($token === '{') {
                $output .= " {\n";
                $indent++;
            } elseif ($token === '}') {
                $indent = max(0, $indent - 1);
                $output = rtrim($output) . "\n" . str_repeat('    ', $indent) . "}\n";
            } elseif ($token === ';') {
                $output .= ";\n";
            } else {
                $output .= $token;
            }
            $lastToken = $token;
        }
    }
    
    // Write back
    file_put_contents($file, $output);
    echo "Fixed: $file\n";
    return true;
}

// Find all PHP files in be-delightful-module
$dir = __DIR__ . '/be-delightful-module';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir)
);

$fixed = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (formatPhpFile($file->getPathname())) {
            $fixed++;
        }
    }
}

echo "\nFixed $fixed files\n";

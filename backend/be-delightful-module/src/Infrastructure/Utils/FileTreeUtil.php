<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

/** * File tree builder utility class. */

class FileTreeUtil 
{
 /** * Assemble file list into tree structure, supports unlimited nesting. * * New version directly uses relative_file_path to build tree structure, supports is_directory field * * @param array $files File list data * @return array Tree structure array */ 
    public 
    static function assembleFilesTree(array $files): array 
{
 if (empty($files)) 
{
 return []; 
}
 // Preprocessing: sort files by sort value usort($files, function ($a, $b) 
{
 // First group by parent directory $aParentId = $a['parent_id'] ?? 0; $bParentId = $b['parent_id'] ?? 0; if ($aParentId !== $bParentId) 
{
 return $aParentId <=> $bParentId; 
}
 // Sort by sort value under the same parent directory $aSortValue = $a['sort'] ?? 0; $bSortValue = $b['sort'] ?? 0; if ($aSortValue === $bSortValue) 
{
 // Sort by file_id when sort values are equal $aFileId = $a['file_id'] ?? 0; $bFileId = $b['file_id'] ?? 0; return $aFileId <=> $bFileId; 
}
 return $aSortValue <=> $bSortValue; 
}
); // Preprocessing: determine type and normalize path for each file $processedFiles = []; foreach ($files as $file) 
{
 $relativePath = $file['relative_file_path'] ?? ''; if (empty($relativePath)) 
{
 continue; // Skip files without relative path 
}
 // Normalize path: remove leading slash, ensure directories end with slash $normalizedPath = ltrim($relativePath, '/'); // Detect if it is a directory $isDirectory = self::detectIsDirectory($file); $processedFiles[] = [ 'original_data' => $file, 'normalized_path' => $normalizedPath, 'is_directory' => $isDirectory, 'path_parts' => $normalizedPath ? explode('/', rtrim($normalizedPath, '/')) : [], ]; 
}
 // Build file tree $root = [ 'type' => 'root', 'is_directory' => true, 'is_hidden' => false, 'children' => [], ]; // Directory mapping for quick directory node lookup $directoryMap = ['' => &$root]; // Step 1: Create all directory nodes foreach ($processedFiles as $processedFile) 
{
 if (! $processedFile['is_directory']) 
{
 continue; 
}
 $pathParts = $processedFile['path_parts']; if (empty($pathParts)) 
{
 continue; 
}
 self::ensureDirectoryPath($directoryMap, $pathParts, $processedFile['original_data']); 
}
 // Step 2: Place all files into corresponding directories foreach ($processedFiles as $processedFile) 
{
 if ($processedFile['is_directory']) 
{
 continue; 
}
 $pathParts = $processedFile['path_parts']; if (empty($pathParts)) 
{
 continue; 
}
 // Filename is the last part of the path $fileName = array_pop($pathParts); // Ensure parent directory exists if (! empty($pathParts)) 
{
 self::ensureDirectoryPath($directoryMap, $pathParts); 
}
 // Create file node $fileNode = $processedFile['original_data']; $fileNode['type'] = 'file'; $fileNode['is_directory'] = false; $fileNode['children'] = []; $fileNode['name'] = $fileName; // Detect if it is a hidden file if (! isset($fileNode['is_hidden'])) 
{
 $fileNode['is_hidden'] = str_starts_with($fileName, '.'); 
}
 // Get parent directory path $parentPath = empty($pathParts) ? '' : implode('/', $pathParts); // Add file to parent directory if (isset($directoryMap[$parentPath])) 
{
 $directoryMap[$parentPath]['children'][] = $fileNode; 
}
 
}
 // Step 3: Sort child nodes of all directories self::sortAllDirectoryChildren($root); return $root['children']; 
}
 /** * Get file tree statistics. * * @param array $tree File tree * @return array Statistics ['directories' => int, 'files' => int, 'total_size' => int] */ 
    public 
    static function getTreeStats(array $tree): array 
{
 $stats = [ 'directories' => 0, 'files' => 0, 'total_size' => 0, ]; self::walkTree($tree, function ($node) use (&$stats) 
{
 if ($node['is_directory']) 
{
 ++$stats['directories'];

}
 else 
{
 ++$stats['files']; $stats['total_size'] += $node['file_size'] ?? 0; 
}
 
}
); return $stats; 
}
 /** * Flatten file tree, return list of all file paths. * * @param array $tree File tree * @param string $basePath Base path * @return array File path list */ 
    public 
    static function flattenTree(array $tree, string $basePath = ''): array 
{
 $paths = []; foreach ($tree as $node) 
{
 $currentPath = empty($basePath) ? ($node['name'] ?? '') : $basePath . '/' . ($node['name'] ?? ''); if (! $node['is_directory']) 
{
 $paths[] = $currentPath; 
}
 else 
{
 if (! empty($node['children'])) 
{
 $childPaths = self::flattenTree($node['children'], $currentPath); $paths = array_merge($paths, $childPaths); 
}
 
}
 
}
 return $paths; 
}
 /** * Find a node in the file tree by path. * * @param array $tree File tree * @param string $path Path to find * @return null|array Found node, or null if not found */ 
    public 
    static function findNodeByPath(array $tree, string $path): ?array 
{
 $pathParts = explode('/', trim($path, '/')); $current = ['children' => $tree]; foreach ($pathParts as $part) 
{
 if (empty($part)) 
{
 continue; 
}
 $found = false; foreach ($current['children'] as $child) 
{
 if (($child['name'] ?? '') === $part) 
{
 $current = $child; $found = true; break; 
}
 
}
 if (! $found) 
{
 return null; 
}
 
}
 return $current; 
}
 /** * Build file tree based on parent_id, supports multiple root node scenarios. * * When selected files are at the same level (same parent_id), but the parent directory * is not selected, this method can automatically recognize these files as root nodes, * solving the tree building problem. * * @param array $files File list, must contain file_id and parent_id fields * @return array Tree structure in the same format as assembleFilesTree */ 
    public 
    static function assembleFilesTreeByParentId(array $files): array 
{
 if (empty($files)) 
{
 return []; 
}
 // 1. Collect all existing file_ids and build file mapping $existingFileIds = []; $fileMap = []; foreach ($files as $file) 
{
 $fileId = $file['file_id'] ?? 0; if ($fileId > 0) 
{
 $existingFileIds[] = $fileId; $fileMap[$fileId] = $file; 
}
 
}
 // 2. Group by parent_id, identify root nodes and child nodes $parentChildMap = []; // parent_id => [child_files] $rootNodes = []; // Root nodes (parent_id not in existing files) foreach ($files as $file) 
{
 $fileId = $file['file_id'] ?? 0; $parentId = $file['parent_id'] ?? 0; if ($parentId <= 0 || ! in_array($parentId, $existingFileIds)) 
{
 // Parent node doesn't exist in current file list, treat as root node $rootNodes[] = $file; 
}
 else 
{
 // Parent node exists, add to parent-child mapping if (! isset($parentChildMap[$parentId])) 
{
 $parentChildMap[$parentId] = []; 
}
 $parentChildMap[$parentId][] = $file; 
}
 
}
 // 3. Recursively build tree structure $result = []; // process root nodes foreach ($rootNodes as $rootFile) 
{
 $result[] = self::buildNodeWithChildren($rootFile, $parentChildMap); 
}
 // 4. Sort root nodes self::sortNodeChildrenArray($result); return $result; 
}
 /** * Determine if directory name is a hidden directory * Hidden directory rule: directory name starts with . * * @param string $dirName Directory name * @return bool true-hidden directory, false-normal directory */ 
    private 
    static function isHiddenDirectory(string $dirName): bool 
{
 return str_starts_with($dirName, '.'); 
}
 /** * Traverse file tree, execute callback function on each node. * * @param array $tree File tree * @param callable $callback Callback function */ 
    private 
    static function walkTree(array $tree, callable $callback): void 
{
 foreach ($tree as $node) 
{
 $callback($node); if (! empty($node['children'])) 
{
 self::walkTree($node['children'], $callback); 
}
 
}
 
}
 /** * Detect if file item is a directory * Prefer using is_directory field, fall back to path analysis. * * @param array $file File data * @return bool true-directory, false-file */ 
    private 
    static function detectIsDirectory(array $file): bool 
{
 // Prefer using is_directory field (new data) if (isset($file['is_directory'])) 
{
 return (bool) $file['is_directory']; 
}
 // Fall back to path analysis (backward compatibility for legacy data) $relativePath = $file['relative_file_path'] ?? ''; // Path ending with slash usually indicates directory if (str_ends_with($relativePath, '/')) 
{
 return true; 
}
 // No file extension and file_size of 0 may indicate directory $fileExtension = $file['file_extension'] ?? ''; $fileSize = $file['file_size'] ?? 0; if (empty($fileExtension) && $fileSize === 0) 
{
 return true; 
}
 return false; 
}
 /** * Ensure directory path exists, create if it doesn't exist. * * @param array &$directoryMap Reference to directory mapping table * @param array $pathParts Path parts array * @param null|array $directoryData Original data for directory (optional) */ 
    private 
    static function ensureDirectoryPath(array &$directoryMap, array $pathParts, ?array $directoryData = null): void 
{
 $currentPath = ''; $parentIsHidden = false; foreach ($pathParts as $index => $dirName) 
{
 if (empty($dirName)) 
{
 continue; 
}
 // Build current path $currentPath = empty($currentPath) ? $dirName : 
{
$currentPath
}
/
{
$dirName
}
 ; // If directory doesn't exist, create it if (! isset($directoryMap[$currentPath])) 
{
 // Determine if it's a hidden directory $isHiddenDir = self::isHiddenDirectory($dirName) || $parentIsHidden; // Create directory node $newDir = [ 'name' => $dirName, 'path' => $currentPath, 'type' => 'directory', 'is_directory' => true, 'is_hidden' => $isHiddenDir, 'children' => [], ]; // If this is the last path part and directory data is provided, merge original data if ($index === count($pathParts) - 1 && $directoryData) 
{
 $newDir = array_merge($directoryData, $newDir); 
}
 // Get parent directory path $parentPath = ''; if ($index > 0) 
{
 $parentParts = array_slice($pathParts, 0, $index); $parentPath = implode('/', $parentParts); 
}
 // Add new directory to parent directory if (isset($directoryMap[$parentPath])) 
{
 $directoryMap[$parentPath]['children'][] = $newDir; // Get reference to the just-added directory $directoryMap[$currentPath] = &$directoryMap[$parentPath]['children'][count($directoryMap[$parentPath]['children']) - 1]; 
}
 
}
 // Update parent hidden status $parentIsHidden = $directoryMap[$currentPath]['is_hidden'] ?? false; 
}
 
}
 /** * Recursively sort all child nodes of directories. */ 
    private 
    static function sortAllDirectoryChildren(array &$directory): void 
{
 if (empty($directory['children'])) 
{
 return; 
}
 // Sort child nodes of current directory usort($directory['children'], function ($a, $b) 
{
 // Directories take priority over files $aIsDir = $a['is_directory'] ?? false; $bIsDir = $b['is_directory'] ?? false; if ($aIsDir !== $bIsDir) 
{
 return $bIsDir <=> $aIsDir; // Directories come first 
}
 // Sort by sort value $aSort = $a['sort'] ?? $a['original_data']['sort'] ?? 0; $bSort = $b['sort'] ?? $b['original_data']['sort'] ?? 0; if ($aSort === $bSort) 
{
 $aFileId = $a['file_id'] ?? $a['original_data']['file_id'] ?? 0; $bFileId = $b['file_id'] ?? $b['original_data']['file_id'] ?? 0; return $aFileId <=> $bFileId; 
}
 return $aSort <=> $bSort; 
}
); // Recursively sort subdirectories foreach ($directory['children'] as &$child) 
{
 if ($child['is_directory'] ?? false) 
{
 self::sortAllDirectoryChildren($child); 
}
 
}
 
}
 /** * Recursively build node and its child nodes. * * @param array $file File data * @param array $parentChildMap Parent-child relationship mapping [parent_id => [children]] * @return array Standardized node data */ 
    private 
    static function buildNodeWithChildren(array $file, array $parentChildMap): array 
{
 // Standardize node format, keep consistent with assembleFilesTree $node = $file; $node['type'] = ($file['is_directory'] ?? false) ? 'directory' : 'file'; $node['children'] = []; // Ensure necessary fields exist if (! isset($node['name'])) 
{
 $node['name'] = $node['file_name'] ?? ''; 
}
 // If there are child nodes, build recursively $fileId = $file['file_id'] ?? 0; if (isset($parentChildMap[$fileId]) && ! empty($parentChildMap[$fileId])) 
{
 foreach ($parentChildMap[$fileId] as $childFile) 
{
 $node['children'][] = self::buildNodeWithChildren($childFile, $parentChildMap); 
}
 // Sort child nodes self::sortNodeChildrenArray($node['children']); 
}
 return $node; 
}
 /** * Sort node array. * * Sorting rules consistent with sortAllDirectoryChildren: * 1. Directories take priority over files * 2. Sort by sort field * 3. When sort is the same, sort by file_id * * @param array &$nodes Reference to node array */ 
    private 
    static function sortNodeChildrenArray(array &$nodes): void 
{
 if (empty($nodes)) 
{
 return; 
}
 usort($nodes, function ($a, $b) 
{
 // Directories take priority over files $aIsDir = $a['is_directory'] ?? false; $bIsDir = $b['is_directory'] ?? false; if ($aIsDir !== $bIsDir) 
{
 return $bIsDir <=> $aIsDir; // Directories come first 
}
 // Sort by sort value $aSort = $a['sort'] ?? 0; $bSort = $b['sort'] ?? 0; if ($aSort === $bSort) 
{
 // When sort values are the same, sort by file_id $aFileId = $a['file_id'] ?? 0; $bFileId = $b['file_id'] ?? 0; return $aFileId <=> $bFileId; 
}
 return $aSort <=> $bSort; 
}
); 
}
 
}
 

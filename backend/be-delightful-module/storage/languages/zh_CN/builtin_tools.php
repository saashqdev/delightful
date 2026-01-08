<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'names' => [
        // File operations
        'list_dir' => 'List Directory',
        'read_files' => 'Read Files',
        'write_file' => 'Write File',
        'edit_file' => 'Edit File',
        'multi_edit_file' => 'Multi Edit File',
        'delete_files' => 'Delete Files',
        'file_search' => 'File Search',
        'grep_search' => 'Content Search',

        // Search and extraction
        'web_search' => 'Web Search',
        'image_search' => 'Image Search',
        'read_webpages_as_markdown' => 'Webpage to Markdown',
        'use_browser' => 'Browser Operations',
        'download_from_urls' => 'Batch Download',
        'download_from_markdown' => 'Download from Markdown',

        // Content processing
        'visual_understanding' => 'Visual Understanding',
        'convert_to_markdown' => 'Convert to Markdown',
        'voice_understanding' => 'Voice Recognition',
        'summarize' => 'Summarize Content',
        'generate_image' => 'Generate Image',
        'create_slide' => 'Create Slide',
        'create_slide_project' => 'Create Slide Project',
        'create_dashboard_project' => 'Create Dashboard Project',
        'update_dashboard_template' => 'Update Dashboard Template',
        'backup_dashboard_template' => 'Backup Dashboard Template',
        'finish_dashboard_task' => 'Finish Dashboard Task',

        // System execution
        'shell_exec' => 'Shell Execute',
        'run_python_snippet' => 'Run Python Snippet',

        // AI collaboration
        'create_memory' => 'Create Memory',
        'update_memory' => 'Update Memory',
        'delete_memory' => 'Delete Memory',
        'finish_task' => 'Finish Task',
        'compact_chat_history' => 'Compact Chat History',
    ],

    'descriptions' => [
        // File operations
        'list_dir' => 'Directory viewing tool that supports recursive display of multi-level directory structures, showing file size, line count, and token count to help quickly understand project file organization and code scale',
        'read_files' => 'Batch file reading tool that reads multiple file contents at once, supporting various formats such as text, PDF, Word, Excel, CSV, and more, greatly improving efficiency when processing multi-file tasks',
        'write_file' => 'File writing tool that writes content to the local file system, supporting creating new files or overwriting existing files. Note the content length limit per operation; for large files, write in steps',
        'edit_file' => 'Precise file editing tool that performs string replacement operations on existing files, supporting strict match validation and replacement count control to ensure editing accuracy',
        'multi_edit_file' => 'Multi-file editing tool that executes multiple find-and-replace operations in a single file. All edits are applied sequentially, either all succeed or all fail, ensuring operation atomicity',
        'delete_files' => 'Delete multiple files tool for batch deleting specified files or directories. Please confirm all file paths before deletion. If any file does not exist, an error will be returned. Can only delete files in the working directory. Supports deleting multiple files simultaneously to improve operational efficiency',
        'file_search' => 'File path search tool that performs quick searches based on fuzzy matching of file paths, suitable for scenarios where part of the file path is known but the exact location is uncertain. Returns a maximum of 10 results',
        'grep_search' => 'File content search tool that uses regular expressions to search for specific patterns in file content, supports file type filtering, displays matching lines with context, and returns up to 20 relevant files',

        // Search and extraction
        'web_search' => 'Internet search tool that supports XML format to configure multiple search requests for parallel processing, supports paginated search and time range filtering. Search results include title, URL, summary, and source website',
        'image_search' => 'Image search tool that searches and intelligently filters high-quality images based on keywords, supports visual understanding analysis and aspect ratio filtering, with automatic deduplication to ensure image quality',
        'read_webpages_as_markdown' => 'Batch webpage reading tool that aggregates and converts multiple webpage contents into a single Markdown document, supporting full content retrieval and summary mode',
        'use_browser' => 'Browser automation tool that provides atomic browser operation capabilities, supporting modular operations such as page navigation, element interaction, and form filling',
        'download_from_urls' => 'Batch URL download tool that supports XML configuration for multiple download tasks, automatically handles redirects. If the target file already exists, it will be automatically overwritten',
        'download_from_markdown' => 'Markdown file batch download tool that extracts image links from Markdown files and downloads them in batches, supporting network URLs and local file copying',

        // Content processing
        'visual_understanding' => 'Visual understanding tool that analyzes and interprets image content, supports multiple formats such as JPEG, PNG, GIF, suitable for scenarios like image recognition and description, chart analysis, text extraction, and multi-image comparison',
        'convert_to_markdown' => 'Document format conversion tool that converts documents to Markdown format and saves them to a specified location. Supports multiple file types: PDF, Word, Excel, PowerPoint, images, Jupyter notebooks, etc.',
        'voice_understanding' => 'Voice recognition tool that converts audio files to text, supports formats like wav, mp3, ogg, m4a, with optional speaker identification feature',
        'summarize' => 'Information refinement tool that enhances text information density, removes redundant content to make it more structured, supports custom refinement requirements and target length settings',
        'generate_image' => 'Image generation and editing tool that supports creating brand new images from text descriptions or modifying existing images. Can customize image dimensions, generation count, and save to specified location, meeting various image creation needs',
        'create_slide' => 'Slide creation tool that generates HTML slides and executes custom JavaScript analysis, supports layout checking and element boundary validation',
        'create_slide_project' => 'Slide project creation tool that automatically creates complete project structure, including presentation controller, configuration files, resource folders, and communication scripts',
        'create_dashboard_project' => 'Dashboard project creation tool that copies complete dashboard framework from template directory, including HTML, CSS, JavaScript, and chart components',
        'update_dashboard_template' => 'Dashboard template update tool that synchronizes dashboard.js, index.css, index.html, and config.js files from template directory to existing project',
        'backup_dashboard_template' => 'Dashboard template backup and recovery tool that restores backup versions of template files for specified projects, swapping current files with backup files',
        'finish_dashboard_task' => 'Dashboard project completion tool that automates map and data source configuration, including GeoJSON download, HTML configuration update, and data file scanning',

        // System execution
        'shell_exec' => 'Shell command execution tool that executes system commands and scripts, supports timeout settings and working directory specification, suitable for scenarios like file operations and process management',
        'run_python_snippet' => 'Python code snippet execution tool suitable for data analysis, processing, transformation, quick calculations, validation, file operations, and processing. Suitable for small to medium Python code snippets (<=200 lines). For complex scripts, persist to a file and then use the shell_exec tool to execute',

        // AI collaboration
        'create_memory' => 'Long-term memory creation tool that stores important memories such as user preferences and project information. Supports two types of memory: user and project, with optional user confirmation',
        'update_memory' => 'Long-term memory update tool that modifies existing memory content or tag information, locating and updating specified memory by memory ID',
        'delete_memory' => 'Long-term memory deletion tool that permanently removes unnecessary memory information by memory ID, used for cleaning outdated or incorrect memory data',
        'finish_task' => 'Task completion tool called when all required tasks are completed, providing final response or pausing the task to give user feedback. After calling, it will enter a stopped state',
        'compact_chat_history' => 'Chat history compression tool used to compress and optimize chat history when conversations become too long. By analyzing conversation processes and generating summaries to reduce context length and improve subsequent conversation efficiency',
    ],
];

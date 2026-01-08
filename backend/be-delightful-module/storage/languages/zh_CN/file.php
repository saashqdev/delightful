<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'file_not_found' => 'File not found',
    'file_exist' => 'File already exists',
    'illegal_file_key' => 'Illegal file key',
    'target_parent_not_directory' => 'Target parent is not a directory',
    'cannot_move_to_subdirectory' => 'Cannot move directory to its subdirectory',
    'file_move_failed' => 'File move failed',
    'file_copy_failed' => 'File copy failed',
    'file_save_failed' => 'File save failed',
    'file_create_failed' => 'File creation failed',
    'file_delete_failed' => 'File deletion failed',
    'file_rename_failed' => 'File rename failed',
    'directory_delete_failed' => 'Directory deletion failed',
    'batch_delete_failed' => 'Batch file deletion failed',
    'permission_denied' => 'File permission denied',
    'files_not_found_or_no_permission' => 'Files not found or no access permission',
    'content_too_large' => 'File content too large',
    'concurrent_modification' => 'File concurrent modification conflict',
    'save_rate_limit' => 'File save rate limit',
    'upload_failed' => 'File upload failed',

    // Batch download related
    'batch_file_ids_required' => 'File ID list cannot be empty',
    'batch_file_ids_invalid' => 'File ID format is invalid',
    'batch_too_many_files' => 'Batch download file count cannot exceed 1000',
    'batch_no_valid_files' => 'No valid files accessible',
    'batch_access_denied' => 'Batch download task access denied',
    'batch_publish_failed' => 'Batch download task publish failed',

    // File conversion related
    'convert_file_ids_required' => 'File ID list cannot be empty',
    'convert_too_many_files' => 'File conversion count cannot exceed 50',
    'convert_no_valid_files' => 'No valid files to convert',
    'convert_access_denied' => 'File conversion task access denied',
    'convert_same_sandbox_required' => 'Files must be in the same sandbox',
    'convert_create_zip_failed' => 'Failed to create ZIP file',
    'convert_no_converted_files' => 'No valid converted files to create ZIP',
    'convert_failed' => 'File conversion failed, please retry',
    'convert_md_to_ppt_not_supported' => 'Converting Markdown to PPT is not supported',

    // File version related
    'cannot_version_directory' => 'Cannot create version for directory',
    'version_create_failed' => 'Failed to create file version',
    'access_denied' => 'Access denied',

    // File replace related
    'file_replace_failed' => 'File replacement failed',
    'cannot_replace_directory' => 'Directory replacement is not supported',
    'source_file_not_found_in_storage' => 'Replacement file does not exist, please retry replacement',
    'target_file_already_exists' => 'Target file name already exists in this directory',
    'file_is_being_edited' => 'File is currently being edited by another user, use force_replace to force replacement',
    'source_file_key_illegal' => 'Source file key is illegal or not within workspace scope',
    'content_field_required' => 'content field is required',

    // Cross-project move related
    'target_parent_not_found' => 'Target parent directory not found',
    'target_parent_not_in_target_project' => 'Target parent directory does not belong to target project',
    'cross_organization_copy_failed' => 'Cross-organization file copy failed',
    'batch_move_failed' => 'Batch move operation failed',
    'batch_copy_failed' => 'Batch copy operation failed',
    'directory_copy_not_supported_yet' => 'Directory copy feature is not yet supported, please use batch copy',
];

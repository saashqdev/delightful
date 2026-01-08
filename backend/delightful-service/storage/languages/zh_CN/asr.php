<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'success' => [
        'success' => 'Success',
    ],
    'request_error' => [
        'invalid_params' => 'Invalid request parameters',
        'no_permission' => 'No access permission',
        'freq_limit' => 'Access frequency limit exceeded',
        'quota_limit' => 'Access quota limit exceeded',
    ],
    'driver_error' => [
        'driver_not_found' => 'ASR driver not found, config type: :config',
    ],
    'server_error' => [
        'server_busy' => 'Server busy',
        'unknown_error' => 'Unknown error',
    ],
    'audio_error' => [
        'audio_too_long' => 'Audio duration too long',
        'audio_too_large' => 'Audio file too large',
        'invalid_audio' => 'Invalid audio format',
        'audio_silent' => 'Audio is silent',
        'analysis_failed' => 'Audio file analysis failed',
        'invalid_parameters' => 'Invalid audio parameters',
    ],
    'recognition_error' => [
        'wait_timeout' => 'Recognition wait timeout',
        'process_timeout' => 'Recognition processing timeout',
        'recognize_error' => 'Recognition error',
    ],
    'connection_error' => [
        'websocket_connection_failed' => 'WebSocket connection failed',
    ],
    'file_error' => [
        'file_not_found' => 'Audio file does not exist',
        'file_open_failed' => 'Cannot open audio file',
        'file_read_failed' => 'Failed to read audio file',
    ],
    'invalid_audio_url' => 'Invalid audio URL format',
    'audio_url_required' => 'Audio URL cannot be empty',
    'processing_error' => [
        'decompression_failed' => 'Decompression failed',
        'json_decode_failed' => 'JSON decode failed',
    ],
    'config_error' => [
        'invalid_config' => 'Invalid configuration',
        'invalid_delightful_id' => 'Invalid delightful id',
        'invalid_language' => 'Unsupported language',
        'unsupported_platform' => 'Unsupported ASR platform: :platform',
    ],
    'uri_error' => [
        'uri_open_failed' => 'Cannot open audio URI',
        'uri_read_failed' => 'Cannot read audio URI',
    ],
    'download' => [
        'success' => 'Successfully obtained download link',
        'file_not_exist' => 'Merged audio file does not exist, please perform voice summary processing first',
        'get_link_failed' => 'Failed to get merged audio file access link',
        'get_link_error' => 'Failed to get download link: :error',
    ],
    'api' => [
        'validation' => [
            'task_key_required' => 'Task key parameter is required',
            'project_id_required' => 'Project ID parameter is required',
            'chat_topic_id_required' => 'Chat topic ID parameter is required',
            'model_id_required' => 'Model ID parameter is required',
            'invalid_recording_type' => 'Invalid recording type: :type, valid values: frontend_recording, file_upload',
            'retry_files_uploaded' => 'Files have been re-uploaded to project workspace',
            'file_required' => 'File parameter is required',
            'task_not_found' => 'Task not found or expired',
            'task_not_exist' => 'Task does not exist or has expired',
            'upload_audio_first' => 'Please upload audio file first',
            'project_not_found' => 'Project does not exist',
            'project_access_denied_organization' => 'Project does not belong to current organization, access denied',
            'project_access_denied_user' => 'No permission to access this project',
            'project_access_validation_failed' => 'Project permission validation failed: :error',
            'note_content_too_long' => 'Note content too long, maximum 10000 characters, current: :length characters',
        ],
        'upload' => [
            'start_log' => 'ASR file upload started',
            'success_log' => 'ASR file upload successful',
            'success_message' => 'File upload successful',
            'failed_log' => 'ASR file upload failed',
            'failed_exception' => 'File upload failed: :error',
        ],
        'token' => [
            'cache_cleared' => 'ASR token cache cleared successfully',
            'cache_not_exist' => 'ASR token cache does not exist',
            'access_token_not_configured' => 'ASR access token not configured',
            'sts_get_failed' => 'STS token retrieval failed: temporary_credential.dir is empty, please check storage service configuration',
            'usage_note' => 'This token is specifically for ASR audio file chunked upload, please upload audio files to the specified directory',
            'reuse_task_log' => 'Reusing task key, refreshing STS token',
        ],
        'speech_recognition' => [
            'task_id_missing' => 'Speech recognition task ID does not exist',
            'request_id_missing' => 'Speech recognition service did not return request ID',
            'submit_failed' => 'Audio conversion task submission failed: :error',
            'silent_audio_error' => 'Silent audio detected, please check if audio file contains valid speech content',
            'internal_server_error' => 'Internal service processing error, status code: :code',
            'unknown_status_error' => 'Speech recognition failed, unknown status code: :code',
        ],
        'directory' => [
            'invalid_asr_path' => 'Directory must contain "/asr/recordings" path',
            'security_path_error' => 'Directory path cannot contain ".." for security reasons',
            'ownership_error' => 'Directory does not belong to current user',
            'invalid_structure' => 'Invalid ASR directory structure',
            'invalid_structure_after_recordings' => 'Invalid directory structure after "/asr/recordings"',
            'user_id_not_found' => 'User ID not found in directory path',
        ],
        'status' => [
            'get_file_list_failed' => 'ASR status query: Failed to get file list',
        ],
        'redis' => [
            'save_task_status_failed' => 'Failed to save task status to Redis',
        ],
        'lock' => [
            'acquire_failed' => 'Failed to acquire lock, another summary task is in progress, please try again later',
            'system_busy' => 'System busy, please try again later',
        ],
    ],

    // Directory related
    'directory' => [
        'recordings_summary_folder' => 'Recording Summary',
    ],

    // File name related
    'file_names' => [
        'recording_prefix' => 'Recording',
        'merged_audio_prefix' => 'Audio File',
        'original_recording' => 'Original Recording File',
        'transcription_prefix' => 'Recording Transcription Result',
        'summary_prefix' => 'Recording Summary',
        'preset_note' => 'Notes',
        'preset_transcript' => 'Streaming Recognition',
        'note_prefix' => 'Recording Notes',
        'note_suffix' => 'Notes', // Used to generate note file names with title: {title}-Notes.{ext}
    ],

    // Markdown content related
    'markdown' => [
        'transcription_title' => 'Recording Transcription Result',
        'transcription_content_title' => 'Transcription Content',
        'summary_title' => 'AI Recording Summary',
        'summary_content_title' => 'AI Summary Content',
        'task_id_label' => 'Task ID',
        'generate_time_label' => 'Generation Time',
    ],

    // Chat message related
    'messages' => [
        'summary_content' => ' Summary Content',
        'summary_content_with_note' => 'Please refer to the recording notes file in the same directory when summarizing the recording, and combine the notes with the recording content to complete the summary.',
        // New prefix/suffix internationalization (without notes)
        'summary_prefix' => 'Please help me convert ',
        'summary_suffix' => ' recording content into a super product',
        // New prefix/suffix internationalization (with notes)
        'summary_prefix_with_note' => 'Please help me convert ',
        'summary_middle_with_note' => ' recording content and ',
        'summary_suffix_with_note' => ' my notes content into a super product',
    ],

    // Exception message internationalization
    'exception' => [
        // API layer exceptions
        'task_key_empty' => 'task_key cannot be empty',
        'topic_id_empty' => 'topic_id cannot be empty',
        'hidden_directory_not_found' => 'Hidden recording directory not found',
        'task_already_completed' => 'Task already completed, cannot continue uploading',
        'sandbox_start_retry_exceeded' => 'Sandbox startup failed too many times, please try again later',

        // Service layer exceptions
        'task_not_exist_get_upload_token' => 'Task does not exist, please call getUploadToken first',
        'file_not_exist' => 'File does not exist: :fileId',
        'file_not_belong_to_project' => 'File does not belong to current project: :fileId',
        'create_preset_file_failed' => 'Failed to create preset file',
        'create_states_directory_failed_project' => 'Failed to create .asr_states directory, project ID: :projectId',
        'create_states_directory_failed_error' => 'Failed to create .asr_states directory: :error',
        'directory_rename_failed' => 'Directory rename failed: :error',
        'batch_update_children_failed' => 'Batch update of child file paths failed: :error',
        'create_audio_file_failed' => 'Failed to create audio file record: :error',
        'update_note_file_failed' => 'Failed to update note file record: :error',
        'audio_file_id_empty' => 'Audio file ID is empty',
        'topic_not_exist' => 'Topic does not exist: :topicId',
        'topic_not_exist_simple' => 'Topic does not exist',
        'user_not_exist' => 'User does not exist',
        'task_not_belong_to_user' => 'Task does not belong to current user',

        // Directory service exceptions
        'create_hidden_directory_failed_project' => 'Unable to create hidden recording directory, project ID: :projectId',
        'create_hidden_directory_failed_error' => 'Failed to create hidden recording directory: :error',
        'create_display_directory_failed_project' => 'Unable to create display recording directory, project ID: :projectId',
        'create_display_directory_failed_error' => 'Failed to create display recording directory: :error',
        'workspace_directory_empty' => 'Workspace directory for project :projectId is empty',

        // Sandbox service exceptions
        'sandbox_task_creation_failed' => 'Failed to create sandbox task: :message',
        'sandbox_cancel_failed' => 'Failed to cancel sandbox task: :message',
        'display_directory_id_not_exist' => 'Display directory ID does not exist, cannot create file record',
        'display_directory_path_not_exist' => 'Display directory path does not exist, cannot create file record',
        'create_file_record_failed_no_query' => 'Failed to create file record and unable to query existing record',
        'create_file_record_failed_error' => 'Failed to create file record: :error',
        'sandbox_id_not_exist' => 'Sandbox ID does not exist, cannot complete recording task',
        'sandbox_merge_failed' => 'Sandbox merge failed: :message',
        'sandbox_merge_timeout' => 'Sandbox merge timeout',
    ],

    // Task status errors
    'task_error' => [
        'task_already_completed' => 'Recording task already completed, cannot continue operation',
        'task_already_canceled' => 'Recording task already canceled, cannot continue operation',
        'task_is_summarizing' => 'Summary in progress, please do not submit repeatedly',
        'task_auto_stopped_by_timeout' => 'Recording automatically stopped due to heartbeat timeout and summary completed',
        'invalid_status_transition' => 'Invalid recording status transition',
        'recording_already_stopped' => 'Recording already stopped, cannot continue operation',
        'upload_not_allowed' => 'Current task status does not allow file upload',
        'status_report_not_allowed' => 'Current task status does not allow status reporting',
        'summary_not_allowed' => 'Current task status does not allow initiating summary',
    ],
];

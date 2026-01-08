<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'general_error' => 'Long-term memory operation failed',
    'prompt_file_not_found' => 'Prompt file not found: :path',
    'not_found' => 'Memory does not exist',
    'creation_failed' => 'Memory creation failed',
    'update_failed' => 'Memory update failed',
    'deletion_failed' => 'Memory deletion failed',
    'enabled_memory_limit_exceeded' => 'Enabled memory count exceeds limit',
    'memory_category_limit_exceeded' => ':category can enable at most :limit items',
    'evaluation' => [
        'llm_request_failed' => 'Memory evaluation request failed',
        'llm_response_parse_failed' => 'Memory evaluation response parsing failed',
        'score_parse_failed' => 'Memory evaluation score parsing failed',
    ],
    'project_not_found' => 'Project does not exist',
    'project_access_denied' => 'No permission to access this project',
    'entity' => [
        'content_too_long' => 'Memory content length cannot exceed 65535 characters',
        'pending_content_too_long' => 'Pending change memory content length cannot exceed 65535 characters',
        'enabled_status_restriction' => 'Only memories with effective status can be enabled or disabled',
        'user_memory_limit_exceeded' => 'User memory count has reached limit (20 items)',
    ],
    'api' => [
        'validation_failed' => 'Parameter validation failed: :errors',
        'memory_not_belong_to_user' => 'Memory does not exist or no permission to access',
        'partial_memory_not_belong_to_user' => 'Some memories do not exist or no permission to access',
        'accept_memories_failed' => 'Batch accept memory suggestions failed: :error',
        'memory_created_successfully' => 'Memory created successfully',
        'memory_updated_successfully' => 'Memory updated successfully',
        'memory_deleted_successfully' => 'Memory deleted successfully',
        'memory_reinforced_successfully' => 'Memory reinforced successfully',
        'memories_batch_reinforced_successfully' => 'Memories batch reinforced successfully',
        'memories_accepted_successfully' => 'Successfully accepted :count memory suggestions',
        'memories_rejected_successfully' => 'Successfully rejected :count memory suggestions',
        'batch_process_memories_failed' => 'Batch process memory suggestions failed',
        'batch_action_memories_failed' => 'Batch :action memory suggestions failed: :error',
        'user_manual_edit_explanation' => 'User manually modified memory content',
        'content_auto_compressed_explanation' => 'Content too long, automatically compressed',
        'parameter_validation_failed' => 'Parameter validation failed: :errors',
        'action_accept' => 'accept',
        'action_reject' => 'reject',
        'content_length_exceeded' => 'Content length cannot exceed 5000 characters',
    ],
];
